<?php

namespace App\Http\Controllers;

use App\Http\Requests\AccountRegisterRequest;
use App\Http\Requests\CompleteRegisterRequest;
use App\Http\Requests\AccountPasswordResetRequest;
use App\Http\Requests\AccountPasswordResetCompleteRequest;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use App\Models\User;
use App\Models\SocialAccount;
use App\Enums\UserRole;
use App\Enums\SocialAccountProvider;
use App\Mail\RegistrationInvitation;
use App\Mail\PasswordReset as PasswordResetMail;
use App\Models\TemporaryRegistration;
use App\Models\PasswordReset as PasswordResetModel;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Http\Exceptions\HttpResponseException;
use Carbon\Carbon;
use Laravel\Socialite\Facades\Socialite;

class AccountController extends Controller
{
    /**
     * ログイン画面表示
     *
     * @return JsonResponse|Application|Factory|View|RedirectResponse
     */
    public function login(): JsonResponse|Application|Factory|View|RedirectResponse
    {
        // 既にログインしている場合はトップページにリダイレクト
        if (Auth::check()) {
            return redirect()->route('Root');
        }

        $colorState = $this->getColorState();

        return $this->tree(view('account.login', compact('colorState')), options: ['url' => route('Account.Login')]);
    }

    /**
     * 認証処理
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function auth(Request $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');
        $rememberMe = $request->input('remember_me', 0);

        $user = User::where('email', $credentials['email'])->first();
        if ($user && $user->password === null) {
            return back()->withInput()->withErrors([
                'login' => 'このアカウントはGitHubでログインしてください。',
            ]);
        }
        if ($user && $user->withdrawn_at && Hash::check($credentials['password'], $user->password)) {
            return back()->withInput()->withErrors([
                'login' => 'メールアドレスまたはパスワードが正しくありません。',
            ]);
        }

        if (Auth::guard('web')->attempt($credentials, $rememberMe == 1)) {
            // 認証に成功したときの処理
            $request->session()->regenerate();
            return redirect()->intended(route('User.MyNode.Top'));
        } else {
            // 認証に失敗したときの処理
            return back()->withInput()->withErrors(['login' => 'メールアドレスまたはパスワードが正しくありません。']);
        }
    }

    /**
     * ログアウト処理
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('Root');
    }

    /**
     * 新規登録画面表示
     *
     * @return JsonResponse|Application|Factory|View|RedirectResponse
     */
    public function register(): JsonResponse|Application|Factory|View|RedirectResponse
    {
        // 既にログインしている場合はトップページにリダイレクト
        if (Auth::check()) {
            return redirect()->route('Root');
        }

        $colorState = $this->getColorState();

        return $this->tree(view('account.register', compact('colorState')), options: ['url' => route('Account.Register')]);
    }

    /**
     * 仮登録処理（メールアドレスのみ）
     *
     * @param Request $request
     * @return JsonResponse|Application|Factory|View|RedirectResponse
     */
    public function store(AccountRegisterRequest $request): JsonResponse|Application|Factory|View|RedirectResponse
    {
        $validated = $request->validated();
        unset($validated['name']);

        $email = $validated['email'];

        // 既存の仮登録レコードを確認
        $existingRegistration = TemporaryRegistration::where('email', $email)->first();

        if ($existingRegistration) {
            // 有効期限内の場合
            if (!$existingRegistration->isExpired()) {
                // 再送回数が2回未満の場合
                if ($existingRegistration->resend_count < 2) {
                    // トークンを再生成
                    $token = Str::random(64);

                    // 再送回数をインクリメントし、有効期限を1時間後に更新
                    $existingRegistration->update([
                        'token' => $token,
                        'expires_at' => now()->addHour(),
                        'resend_count' => $existingRegistration->resend_count + 1,
                    ]);

                    $this->dispatchRegistrationInvitation($email, $token);

                    return $this->tree(view('account.register-pending'));
                } else {
                    // 再送回数が2回以上の場合、再送不可
                    return back()->withInput()->withErrors([
                        'email' => '再送回数の上限に達しました。有効期限が切れるまでお待ちください。'
                    ]);
                }
            } else {
                // 有効期限切れの場合は削除して新規作成
                $existingRegistration->delete();
            }
        }

        // トークンを生成
        $token = Str::random(64);

        // 仮登録レコードを作成（有効期限は1時間、再送回数は0）
        TemporaryRegistration::create([
            'email' => $email,
            'token' => $token,
            'expires_at' => now()->addHour(),
            'resend_count' => 0,
        ]);

        $this->dispatchRegistrationInvitation($email, $token);

        return $this->tree(view('account.register-pending'));
    }

    /**
     * 登録完了画面表示（トークンから）
     *
     * @param string $token
     * @return JsonResponse|Application|Factory|View|RedirectResponse
     */
    public function showCompleteRegistration(string $token): JsonResponse|Application|Factory|View|RedirectResponse
    {
        // 既にログインしている場合はトップページにリダイレクト
        if (Auth::check()) {
            return redirect()->route('Root');
        }

        $temporaryRegistration = TemporaryRegistration::where('token', $token)->first();

        if (!$temporaryRegistration) {
            return redirect()->route('Account.Register')->with('error', '無効な登録リンクです。');
        }

        if ($temporaryRegistration->isExpired()) {
            $temporaryRegistration->delete();
            return redirect()->route('Account.Register')->with('error', '登録リンクの有効期限が切れています。再度登録してください。');
        }

        $colorState = $this->getColorState();

        return $this->tree(view('account.complete-register', [
            'token' => $token,
            'email' => $temporaryRegistration->email,
            'colorState' => $colorState ?? '',
        ]));
    }

    /**
     * 登録完了処理
     *
     * @param CompleteRegisterRequest $request
     * @param string $token
     * @return JsonResponse|Application|Factory|View|RedirectResponse
     */
    public function completeRegistration(CompleteRegisterRequest $request, string $token): JsonResponse|Application|Factory|View|RedirectResponse
    {
        $temporaryRegistration = TemporaryRegistration::where('token', $token)->first();

        if (!$temporaryRegistration) {
            return redirect()->route('Account.Register')->with('error', '無効な登録リンクです。');
        }

        if ($temporaryRegistration->isExpired()) {
            $temporaryRegistration->delete();
            return redirect()->route('Account.Register')->with('error', '登録リンクの有効期限が切れています。再度登録してください。');
        }

        $validated = $request->validated();

        // show_idが重複しないように生成
        do {
            $showId = Str::random(8);
        } while (User::where('show_id', $showId)->exists());

        // プライバシーポリシーの改定バージョンを取得
        // 新規登録時は最新のプライバシーポリシーに同意しているものとみなす
        $privacyPolicyRevisionVer = Carbon::parse(HgnController::PRIVACY_POLICY_REVISION_DATE)->format('Ymd');

        // ユーザー作成
        $user = User::create([
            'show_id' => $showId,
            'name' => $validated['name'],
            'email' => $temporaryRegistration->email,
            'password' => Hash::make($validated['password']),
            'role' => UserRole::USER->value,
            'hgs12_user' => 0,
            'sign_up_at' => now(),
            'privacy_policy_accepted_version' => $privacyPolicyRevisionVer,
        ]);

        // 仮登録レコードを削除
        $temporaryRegistration->delete();

        return redirect()->route('Account.Login')->with('success', '登録が完了しました。ログインしてください。');
    }

    
    /**
     * 仮登録メール送信処理を非同期で実行する
     *
     * @param string $email
     * @param string $token
     * @return void
     */
    private function dispatchRegistrationInvitation(string $email, string $token): void
    {
        $registrationUrl = route('Account.Register.Complete', ['token' => $token]);

        Bus::dispatchAfterResponse(function () use ($email, $registrationUrl) {
            try {
                Mail::to($email)->send(new RegistrationInvitation($email, $registrationUrl));
            } catch (\Exception $e) {
                report($e);
            }
        });
    }

    /**
     * GitHub OAuth リダイレクト
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGitHub()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        $driver = Socialite::driver('github');

        return $driver->scopes(['user', 'user:email'])->redirect();
    }

    /**
     * GitHub OAuth コールバック
     *
     * @return RedirectResponse|Application|Factory|View
     */
    public function handleGitHubCallback(): RedirectResponse|Application|Factory|View
    {
        try {
            $githubUser = Socialite::driver('github')->user();
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('Account.Login')->with('error', 'GitHub認証に失敗しました。再度お試しください。');
        }

        $providerUserId = (string) $githubUser->getId();
        $email = $githubUser->getEmail();
        $name = $githubUser->getName() ?: '';
        $accessToken = $githubUser->token;

        // アカウント連携モード（ログイン済みユーザーが連携追加）
        if (Auth::check() && session('social_link_intent')) {
            session()->forget('social_link_intent');

            /** @var \App\Models\User $user */
            $user = Auth::user();

            $existingSocialAccount = SocialAccount::where('provider', SocialAccountProvider::GitHub)
                ->where('provider_user_id', $providerUserId)
                ->first();

            if ($existingSocialAccount) {
                if ($existingSocialAccount->user_id === $user->id) {
                    $existingSocialAccount->update([
                        'access_token' => $accessToken,
                        'email' => $email,
                    ]);

                    return redirect()->route('User.MyNode.SocialAccounts')->with('success', 'GitHub連携を更新しました。');
                }

                return redirect()->route('User.MyNode.SocialAccounts')->with('error', 'このGitHubアカウントは別のユーザーに連携されています。');
            }

            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => SocialAccountProvider::GitHub,
                'provider_user_id' => $providerUserId,
                'email' => $email,
                'access_token' => $accessToken,
            ]);

            return redirect()->route('User.MyNode.SocialAccounts')->with('success', 'GitHubと連携しました。');
        }

        // 既存の連携を検索
        $socialAccount = SocialAccount::where('provider', SocialAccountProvider::GitHub)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($socialAccount) {
            $user = $socialAccount->user;
            $socialAccount->update([
                'access_token' => $accessToken,
                'email' => $email,
            ]);
        } else {
            // メールが取得できない場合は登録不可
            if (empty($email)) {
                return redirect()->route('Account.Register')->with('error', 'GitHubでメールアドレスを公開するか、通常の新規登録をご利用ください。');
            }

            // 同一メールの既存ユーザーを検索（退会済みは除外）
            $user = User::where('email', $email)->whereNull('withdrawn_at')->first();

            if ($user) {
                // 既存ユーザーに連携を追加
                SocialAccount::create([
                    'user_id' => $user->id,
                    'provider' => SocialAccountProvider::GitHub,
                    'provider_user_id' => $providerUserId,
                    'email' => $email,
                    'access_token' => $accessToken,
                ]);
            } else {
                // 新規ユーザー作成
                do {
                    $showId = Str::random(8);
                } while (User::where('show_id', $showId)->exists());

                $privacyPolicyRevisionVer = Carbon::parse(HgnController::PRIVACY_POLICY_REVISION_DATE)->format('Ymd');
                $displayName = $name ?: $githubUser->getNickname() ?: '';

                $user = User::create([
                    'show_id' => $showId,
                    'name' => $displayName,
                    'email' => $email,
                    'password' => null,
                    'role' => UserRole::USER->value,
                    'hgs12_user' => 0,
                    'sign_up_at' => now(),
                    'privacy_policy_accepted_version' => $privacyPolicyRevisionVer,
                ]);

                SocialAccount::create([
                    'user_id' => $user->id,
                    'provider' => SocialAccountProvider::GitHub,
                    'provider_user_id' => $providerUserId,
                    'email' => $email,
                    'access_token' => $accessToken,
                ]);
            }
        }

        if ($user->withdrawn_at) {
            return redirect()->route('Account.Login')->with('error', '退会済みのアカウントです。');
        }

        Auth::guard('web')->login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('User.MyNode.Top'));
    }

    /**
     * X (Twitter) OAuth リダイレクト
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToX()
    {
        /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
        return Socialite::driver('twitter-oauth-2')->redirect();
    }

    /**
     * X (Twitter) OAuth コールバック
     *
     * @return RedirectResponse|Application|Factory|View
     */
    public function handleXCallback(): RedirectResponse|Application|Factory|View
    {
        try {
            $xUser = Socialite::driver('twitter-oauth-2')->user();
        } catch (\Throwable $e) {
            report($e);
            return redirect()->route('Account.Login')->with('error', 'X認証に失敗しました。再度お試しください。');
        }

        $providerUserId = (string) $xUser->getId();
        $email = $xUser->getEmail() ?: null;
        $name = $xUser->getName() ?: $xUser->getNickname() ?: '';
        $accessToken = $xUser->token;

        // アカウント連携モード（ログイン済みユーザーが連携追加）
        if (Auth::check() && session('social_link_intent')) {
            session()->forget('social_link_intent');

            /** @var \App\Models\User $user */
            $user = Auth::user();

            $existingSocialAccount = SocialAccount::where('provider', SocialAccountProvider::X)
                ->where('provider_user_id', $providerUserId)
                ->first();

            if ($existingSocialAccount) {
                if ($existingSocialAccount->user_id === $user->id) {
                    $existingSocialAccount->update([
                        'access_token' => $accessToken,
                        'email' => $email,
                    ]);

                    return redirect()->route('User.MyNode.SocialAccounts')->with('success', 'X連携を更新しました。');
                }

                return redirect()->route('User.MyNode.SocialAccounts')->with('error', 'このXアカウントは別のユーザーに連携されています。');
            }

            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => SocialAccountProvider::X,
                'provider_user_id' => $providerUserId,
                'email' => $email,
                'access_token' => $accessToken,
            ]);

            return redirect()->route('User.MyNode.SocialAccounts')->with('success', 'Xと連携しました。');
        }

        // 既存の連携を検索
        $socialAccount = SocialAccount::where('provider', SocialAccountProvider::X)
            ->where('provider_user_id', $providerUserId)
            ->first();

        if ($socialAccount) {
            $user = $socialAccount->user;
            $socialAccount->update([
                'access_token' => $accessToken,
                'email' => $email,
            ]);
        } else {
            // メールがある場合は同一メールの既存ユーザーを検索（退会済みは除外）
            $user = null;
            if (!empty($email)) {
                $user = User::where('email', $email)->whereNull('withdrawn_at')->first();
            }

            if ($user) {
                // 既存ユーザーに連携を追加
                SocialAccount::create([
                    'user_id' => $user->id,
                    'provider' => SocialAccountProvider::X,
                    'provider_user_id' => $providerUserId,
                    'email' => $email,
                    'access_token' => $accessToken,
                ]);
            } else {
                // 新規ユーザー作成
                do {
                    $showId = Str::random(8);
                } while (User::where('show_id', $showId)->exists());

                $privacyPolicyRevisionVer = Carbon::parse(HgnController::PRIVACY_POLICY_REVISION_DATE)->format('Ymd');

                $user = User::create([
                    'show_id' => $showId,
                    'name' => $name,
                    'email' => $email,
                    'password' => null,
                    'role' => UserRole::USER->value,
                    'hgs12_user' => 0,
                    'sign_up_at' => now(),
                    'privacy_policy_accepted_version' => $privacyPolicyRevisionVer,
                ]);

                SocialAccount::create([
                    'user_id' => $user->id,
                    'provider' => SocialAccountProvider::X,
                    'provider_user_id' => $providerUserId,
                    'email' => $email,
                    'access_token' => $accessToken,
                ]);
            }
        }

        if ($user->withdrawn_at) {
            return redirect()->route('Account.Login')->with('error', '退会済みのアカウントです。');
        }

        Auth::guard('web')->login($user, true);
        request()->session()->regenerate();

        return redirect()->intended(route('User.MyNode.Top'));
    }

    /**
     * パスワードリセット申請画面表示
     *
     * @return JsonResponse|Application|Factory|View|RedirectResponse
     */
    public function showPasswordReset(): JsonResponse|Application|Factory|View|RedirectResponse
    {
        // 既にログインしている場合はトップページにリダイレクト
        if (Auth::check()) {
            return redirect()->route('Root');
        }

        $colorState = $this->getColorState();

        return $this->tree(view('account.password-reset', compact('colorState')), options: ['url' => route('Account.PasswordReset')]);
    }

    /**
     * パスワードリセット申請処理（メール送信）
     *
     * @param AccountPasswordResetRequest $request
     * @return JsonResponse|Application|Factory|View|RedirectResponse
     */
    public function storePasswordReset(AccountPasswordResetRequest $request): JsonResponse|Application|Factory|View|RedirectResponse
    {
        $validated = $request->validated();
        $email = $validated['email'];

        // ユーザーが存在するか確認（退会済みユーザーは除外）
        $user = User::where('email', $email)->whereNull('withdrawn_at')->first();

        // セキュリティ上の理由で、ユーザーが存在しない場合でも成功メッセージを表示
        if (!$user) {
            return $this->tree(view('account.password-reset-sent'));
        }

        // 既存のパスワードリセットレコードを確認
        $existingPasswordReset = PasswordResetModel::where('email', $email)->first();

        if ($existingPasswordReset) {
            // 有効期限内の場合は既存のトークンを使用
            if (!$existingPasswordReset->isExpired()) {
                $token = $existingPasswordReset->token;
            } else {
                // 有効期限切れの場合は削除して新規作成
                $existingPasswordReset->delete();
                $token = Str::random(64);
                PasswordResetModel::create([
                    'email' => $email,
                    'token' => $token,
                    'expires_at' => now()->addMinutes(15),
                ]);
            }
        } else {
            // 新規作成
            $token = Str::random(64);
            PasswordResetModel::create([
                'email' => $email,
                'token' => $token,
                'expires_at' => now()->addMinutes(15),
            ]);
        }

        $this->dispatchPasswordReset($email, $token);

        return $this->tree(view('account.password-reset-sent'));
    }

    /**
     * パスワードリセット画面表示（トークンから）
     *
     * @param string $token
     * @return JsonResponse|Application|Factory|View|RedirectResponse
     */
    public function showPasswordResetComplete(string $token): JsonResponse|Application|Factory|View|RedirectResponse
    {
        // 既にログインしている場合はトップページにリダイレクト
        if (Auth::check()) {
            return redirect()->route('Root');
        }

        $passwordReset = PasswordResetModel::where('token', $token)->first();

        if (!$passwordReset) {
            return redirect()->route('Account.PasswordReset')->with('error', '無効なパスワードリセットリンクです。');
        }

        if ($passwordReset->isExpired()) {
            $passwordReset->delete();
            return redirect()->route('Account.PasswordReset')->with('error', 'パスワードリセットリンクの有効期限が切れています。再度申請してください。');
        }

        $email = $passwordReset->email;
        $colorState = $this->getColorState();

        return $this->tree(view('account.password-reset-complete', compact('token', 'email', 'colorState')));
    }

    /**
     * パスワードリセット処理
     *
     * @param AccountPasswordResetCompleteRequest $request
     * @param string $token
     * @return JsonResponse|Application|Factory|View|RedirectResponse
     */
    public function completePasswordReset(AccountPasswordResetCompleteRequest $request, string $token): JsonResponse|Application|Factory|View|RedirectResponse
    {
        $passwordReset = PasswordResetModel::where('token', $token)->first();

        if (!$passwordReset) {
            return redirect()->route('Account.PasswordReset')->with('error', '無効なパスワードリセットリンクです。');
        }

        if ($passwordReset->isExpired()) {
            $passwordReset->delete();
            return redirect()->route('Account.PasswordReset')->with('error', 'パスワードリセットリンクの有効期限が切れています。再度申請してください。');
        }

        try {
            $validated = $request->validated();
        } catch (HttpResponseException $e) {
            // バリデーションエラーがある場合、$this->tree()で返す
            $errors = new ViewErrorBag();
            $errors->put('default', $request->errors());
            $email = $passwordReset->email;
            $colorState = $this->getColorState();
            return $this->tree(view('account.password-reset-complete', compact('token', 'email', 'colorState'))->with('errors', $errors));
        }

        // ユーザーを取得
        $user = User::where('email', $passwordReset->email)->whereNull('withdrawn_at')->first();

        if (!$user) {
            $passwordReset->delete();
            return redirect()->route('Account.PasswordReset')->with('error', '対象のユーザーが見つかりません。');
        }

        // パスワードを更新
        $user->password = Hash::make($validated['password']);
        $user->setRememberToken(Str::random(60));
        $user->save();

        // パスワードリセットレコードを削除
        $passwordReset->delete();

        return redirect()->route('Account.Login')->with('success', 'パスワードを変更しました。ログインしてください。');
    }

    /**
     * パスワードリセットメール送信処理を非同期で実行する
     *
     * @param string $email
     * @param string $token
     * @return void
     */
    private function dispatchPasswordReset(string $email, string $token): void
    {
        $resetUrl = route('Account.PasswordReset.Complete', ['token' => $token]);

        Bus::dispatchAfterResponse(function () use ($email, $resetUrl) {
            try {
                Mail::to($email)->send(new PasswordResetMail($email, $resetUrl));
            } catch (\Exception $e) {
                report($e);
            }
        });
    }
}

