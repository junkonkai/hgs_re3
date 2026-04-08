<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmTotpRequest;
use App\Models\TwoFactorAuthCode;
use App\Services\TwoFactorRecoveryCodeService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class LoginSettingsController extends Controller
{
    /**
     * ログイン設定画面表示
     */
    public function show(): JsonResponse|Application|Factory|View
    {
        /** @var User $user */
        $user = Auth::user();
        $colorState = $this->getColorState();

        return $this->tree(view('user.my_node.login_settings', compact('user', 'colorState')));
    }

    /**
     * 2段階認証設定の更新（メール用）
     */
    public function update2fa(Request $request, TwoFactorRecoveryCodeService $recoveryService): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $method = $request->input('two_factor_method');

        // メール2段階認証を有効にしようとしているがメールアドレス未設定
        if ($method === 'email' && empty($user->email)) {
            return back()->with('error', 'メールアドレスが設定されていないため、メール2段階認証を有効にできません。');
        }

        // null（無効）または 'email' のみ受け付ける
        if ($method !== null && $method !== 'email') {
            return back()->with('error', '無効な設定値です。');
        }

        // TOTPから切り替える場合はシークレットをクリア
        if ($user->hasTwoFactorTotp()) {
            $user->two_factor_secret = null;
        }

        $user->two_factor_method = $method;
        $user->save();

        if ($method === 'email') {
            // リカバリーコードを生成してセッションに格納
            $plainCodes = $recoveryService->generate($user);
            session(['recovery_codes_to_show' => $plainCodes]);

            return redirect()->route('User.MyNode.LoginSettings.RecoveryCodes');
        }

        return back()->with('success', '2段階認証を無効にしました。');
    }

    /**
     * TOTP設定画面表示（QRコード生成）
     */
    public function setupTotp(): JsonResponse|Application|Factory|View|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (empty($user->email)) {
            return redirect()->route('User.MyNode.LoginSettings')->with('error', 'メールアドレスが設定されていないため、Authenticator認証を設定できません。');
        }

        $google2fa = new Google2FA();

        // セッションに仮シークレットがなければ新規生成
        if (!session()->has('totp_pending_secret')) {
            session(['totp_pending_secret' => $google2fa->generateSecretKey()]);
        }

        $secret = session('totp_pending_secret');

        $otpauthUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email ?? $user->name,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrCodeBase64 = base64_encode($writer->writeString($otpauthUrl));

        $colorState = $this->getColorState();

        return $this->tree(
            view('user.my_node.totp_setup', compact('secret', 'qrCodeBase64', 'colorState')),
            options: ['components' => ['OtpInput' => []]]
        );
    }

    /**
     * TOTPコード確認・設定確定
     */
    public function confirmTotp(ConfirmTotpRequest $request, TwoFactorRecoveryCodeService $recoveryService): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (empty($user->email)) {
            return redirect()->route('User.MyNode.LoginSettings')->with('error', 'メールアドレスが設定されていないため、Authenticator認証を設定できません。');
        }

        $secret = session('totp_pending_secret');

        if (!$secret) {
            return redirect()->route('User.MyNode.LoginSettings')->with('error', 'セッションが切れました。再度やり直してください。');
        }

        $google2fa = new Google2FA();

        if (!$google2fa->verifyKey($secret, $request->input('code'), 1)) {
            return back()->withErrors(['code' => '認証コードが正しくありません。アプリの表示を確認してください。']);
        }

        // メール2FAが有効な場合は既存コードを削除
        if ($user->hasTwoFactorEmail()) {
            TwoFactorAuthCode::where('user_id', $user->id)->delete();
        }

        $user->two_factor_method = 'totp';
        $user->two_factor_secret = $secret;
        $user->save();

        session()->forget('totp_pending_secret');

        // リカバリーコードを生成してセッションに格納
        $plainCodes = $recoveryService->generate($user);
        session(['recovery_codes_to_show' => $plainCodes]);

        return redirect()->route('User.MyNode.LoginSettings.RecoveryCodes');
    }

    /**
     * TOTP無効化
     */
    public function disableTotp(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->hasTwoFactorTotp()) {
            return back()->with('error', 'Authenticator認証は有効になっていません。');
        }

        $user->two_factor_method = null;
        $user->two_factor_secret = null;
        $user->save();

        return back()->with('success', 'Authenticatorアプリによる2段階認証を無効にしました。');
    }

    /**
     * リカバリーコード表示（セッションから一度だけ）
     */
    public function showRecoveryCodes(): JsonResponse|Application|Factory|View|RedirectResponse
    {
        $plainCodes = session('recovery_codes_to_show');

        if (!$plainCodes) {
            return redirect()->route('User.MyNode.LoginSettings')->with('error', 'リカバリーコードは既に表示済みです。再発行が必要な場合はログイン設定から行ってください。');
        }

        // 表示したらセッションから削除（一度だけ表示）
        session()->forget('recovery_codes_to_show');

        $colorState = $this->getColorState();

        return $this->tree(view('user.my_node.recovery_codes', compact('plainCodes', 'colorState')));
    }

    /**
     * リカバリーコード再発行処理
     */
    public function regenerateRecoveryCodes(TwoFactorRecoveryCodeService $recoveryService): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->hasTwoFactorEmail() && !$user->hasTwoFactorTotp()) {
            return redirect()->route('User.MyNode.LoginSettings')->with('error', '2段階認証が有効ではありません。');
        }

        $plainCodes = $recoveryService->generate($user);
        session(['recovery_codes_to_show' => $plainCodes]);

        return redirect()->route('User.MyNode.LoginSettings.RecoveryCodes');
    }
}
