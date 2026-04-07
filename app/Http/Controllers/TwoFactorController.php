<?php

namespace App\Http\Controllers;

use App\Mail\TwoFactorAuthCode as TwoFactorAuthCodeMail;
use App\Models\TwoFactorAuthCode;
use App\Models\User;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorController extends Controller
{
    // 失敗上限回数
    private const MAX_FAILED_ATTEMPTS = 5;

    // アカウントロック時間（分）
    private const LOCKOUT_MINUTES = 30;

    /**
     * 2段階認証コード入力画面表示
     */
    public function show(Request $request): JsonResponse|Application|Factory|View|RedirectResponse
    {
        if (!$request->session()->has('two_factor_pending_user_id')) {
            return redirect()->route('Account.Login');
        }

        $userId = $request->session()->get('two_factor_pending_user_id');

        // アカウントロック中は入力画面自体を見せない
        if (RateLimiter::tooManyAttempts($this->lockoutKey($userId), 1)) {
            $seconds = RateLimiter::availableIn($this->lockoutKey($userId));
            $minutes = ceil($seconds / 60);
            $request->session()->forget(['two_factor_pending_user_id', 'two_factor_remember_me', 'two_factor_method']);
            return redirect()->route('Account.Login')->with('error', "ログイン試行回数が上限に達しました。{$minutes}分後に再試行してください。");
        }

        $twoFactorMethod = $request->session()->get('two_factor_method', 'email');
        $colorState = $this->getColorState();

        return $this->tree(
            view('account.two-factor', compact('colorState', 'twoFactorMethod')),
            options: [
                'url' => route('TwoFactor.Show'),
                'components' => ['OtpInput' => []],
            ]
        );
    }

    /**
     * 2段階認証コード検証・ログイン完了
     */
    public function verify(Request $request): RedirectResponse
    {
        if (!$request->session()->has('two_factor_pending_user_id')) {
            return redirect()->route('Account.Login');
        }

        $userId = $request->session()->get('two_factor_pending_user_id');
        $rememberMe = $request->session()->get('two_factor_remember_me', false);
        $twoFactorMethod = $request->session()->get('two_factor_method', 'email');

        // アカウントロック中
        if (RateLimiter::tooManyAttempts($this->lockoutKey($userId), 1)) {
            $seconds = RateLimiter::availableIn($this->lockoutKey($userId));
            $minutes = ceil($seconds / 60);
            $request->session()->forget(['two_factor_pending_user_id', 'two_factor_remember_me', 'two_factor_method']);
            return redirect()->route('Account.Login')->with('error', "ログイン試行回数が上限に達しました。{$minutes}分後に再試行してください。");
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget(['two_factor_pending_user_id', 'two_factor_remember_me', 'two_factor_method']);
            return redirect()->route('Account.Login')->with('error', 'セッションが無効です。再度ログインしてください。');
        }

        $inputCode = $request->input('code', '');

        if ($twoFactorMethod === 'totp') {
            return $this->verifyTotp($request, $user, $inputCode, $rememberMe, $userId);
        }

        return $this->verifyEmail($request, $user, $inputCode, $rememberMe, $userId);
    }

    /**
     * メール認証コードの検証
     */
    private function verifyEmail(Request $request, User $user, string $inputCode, bool $rememberMe, int $userId): RedirectResponse
    {
        $record = TwoFactorAuthCode::where('user_id', $userId)->first();

        // レコードなし・有効期限切れ
        if (!$record || $record->isExpired()) {
            return back()->withErrors(['code' => '認証コードの有効期限が切れています。再送信してください。']);
        }

        // コードがロック済み（失敗5回超）
        if ($record->isLocked()) {
            return back()->withErrors(['code' => '認証コードが無効になりました。再送信してください。']);
        }

        // コード照合（SHA-256）
        if (!hash_equals($record->code, hash('sha256', $inputCode))) {
            $record->increment('failed_attempts');

            // 5回失敗でアカウントロック＆コード無効化
            if ($record->failed_attempts >= self::MAX_FAILED_ATTEMPTS) {
                RateLimiter::hit($this->lockoutKey($userId), self::LOCKOUT_MINUTES * 60);
                $record->delete();
                $request->session()->forget(['two_factor_pending_user_id', 'two_factor_remember_me', 'two_factor_method']);
                return redirect()->route('Account.Login')->with('error', 'コード入力に5回失敗しました。' . self::LOCKOUT_MINUTES . '分間ログインできません。');
            }

            $remaining = self::MAX_FAILED_ATTEMPTS - $record->failed_attempts;
            return back()->withErrors(['code' => "認証コードが正しくありません。あと{$remaining}回失敗するとロックされます。"]);
        }

        // 認証成功
        $record->delete();
        $this->loginUser($request, $user, $rememberMe, $userId);

        return redirect()->intended(route('User.MyNode.Top'));
    }

    /**
     * TOTPコードの検証
     */
    private function verifyTotp(Request $request, User $user, string $inputCode, bool $rememberMe, int $userId): RedirectResponse
    {
        if (empty($user->two_factor_secret)) {
            $request->session()->forget(['two_factor_pending_user_id', 'two_factor_remember_me', 'two_factor_method']);
            return redirect()->route('Account.Login')->with('error', 'Authenticator設定が見つかりません。再度ログインしてください。');
        }

        $google2fa = new Google2FA();

        if (!$google2fa->verifyKey($user->two_factor_secret, $inputCode, 1)) {
            // 失敗カウント（TTLをロック時間と同じ30分に設定）
            RateLimiter::hit($this->lockoutKey($userId) . '_totp_attempt', self::LOCKOUT_MINUTES * 60);

            $attempts = RateLimiter::attempts($this->lockoutKey($userId) . '_totp_attempt');

            if ($attempts >= self::MAX_FAILED_ATTEMPTS) {
                RateLimiter::hit($this->lockoutKey($userId), self::LOCKOUT_MINUTES * 60);
                $request->session()->forget(['two_factor_pending_user_id', 'two_factor_remember_me', 'two_factor_method']);
                return redirect()->route('Account.Login')->with('error', 'コード入力に5回失敗しました。' . self::LOCKOUT_MINUTES . '分間ログインできません。');
            }

            $remaining = self::MAX_FAILED_ATTEMPTS - $attempts;
            return back()->withErrors(['code' => "認証コードが正しくありません。あと{$remaining}回失敗するとロックされます。"]);
        }

        // 認証成功
        RateLimiter::clear($this->lockoutKey($userId) . '_totp_attempt');
        $this->loginUser($request, $user, $rememberMe, $userId);

        return redirect()->intended(route('User.MyNode.Top'));
    }

    /**
     * セッションクリア＆ログイン共通処理
     */
    private function loginUser(Request $request, User $user, bool $rememberMe, int $userId): void
    {
        RateLimiter::clear($this->lockoutKey($userId));
        $request->session()->forget(['two_factor_pending_user_id', 'two_factor_remember_me', 'two_factor_method']);

        Auth::guard('web')->login($user, $rememberMe);
        $request->session()->regenerate();
    }

    /**
     * 認証コードの再送信（メール用のみ）
     */
    public function resend(Request $request): RedirectResponse
    {
        if (!$request->session()->has('two_factor_pending_user_id')) {
            return redirect()->route('Account.Login');
        }

        $userId = $request->session()->get('two_factor_pending_user_id');
        $twoFactorMethod = $request->session()->get('two_factor_method', 'email');

        // TOTPは再送信不要
        if ($twoFactorMethod === 'totp') {
            return back()->with('error', 'Authenticatorアプリのコードをご確認ください。');
        }

        // アカウントロック中
        if (RateLimiter::tooManyAttempts($this->lockoutKey($userId), 1)) {
            $seconds = RateLimiter::availableIn($this->lockoutKey($userId));
            $minutes = ceil($seconds / 60);
            $request->session()->forget(['two_factor_pending_user_id', 'two_factor_remember_me', 'two_factor_method']);
            return redirect()->route('Account.Login')->with('error', "ログイン試行回数が上限に達しました。{$minutes}分後に再試行してください。");
        }

        $user = User::find($userId);
        if (!$user) {
            $request->session()->forget(['two_factor_pending_user_id', 'two_factor_remember_me', 'two_factor_method']);
            return redirect()->route('Account.Login')->with('error', 'セッションが無効です。再度ログインしてください。');
        }

        $record = TwoFactorAuthCode::where('user_id', $userId)->first();

        // 有効期限内かつ再送上限に達している場合は拒否
        if ($record && !$record->isExpired() && !$record->canResend()) {
            return back()->with('error', '再送信の上限に達しました。有効期限が切れるまでお待ちください。');
        }

        // 既存レコードの再送回数を引き継ぐ
        $resendCount = ($record && !$record->isExpired()) ? $record->resend_count + 1 : 0;

        // 新しいコードを発行
        $plainCode = $this->generateCode();
        TwoFactorAuthCode::where('user_id', $userId)->delete();
        TwoFactorAuthCode::create([
            'user_id'         => $userId,
            'code'            => hash('sha256', $plainCode),
            'expires_at'      => now()->addMinutes(15),
            'failed_attempts' => 0,
            'resend_count'    => $resendCount,
        ]);

        $this->dispatchTwoFactorMail($user->email, $plainCode);

        return back()->with('success', '認証コードを再送信しました。');
    }

    /**
     * 6桁のランダム認証コードを生成
     */
    public static function generateCode(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * アカウントロックのRateLimiterキー
     */
    private function lockoutKey(int $userId): string
    {
        return "2fa_lockout:{$userId}";
    }

    /**
     * 2段階認証メール送信を非同期で実行
     */
    public static function dispatchTwoFactorMail(string $email, string $plainCode): void
    {
        Bus::dispatchAfterResponse(function () use ($email, $plainCode) {
            try {
                Mail::to($email)->send(new TwoFactorAuthCodeMail($plainCode));
            } catch (\Exception $e) {
                report($e);
            }
        });
    }
}
