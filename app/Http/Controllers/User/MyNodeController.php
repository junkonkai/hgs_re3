<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\HgnController;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Http\Controllers\Controller;
use App\Http\Requests\MyNodeEmailUpdateRequest;
use App\Http\Requests\MyNodePasswordSetRequest;
use App\Http\Requests\MyNodePasswordUpdateRequest;
use App\Http\Requests\MyNodeProfileUpdateRequest;
use App\Http\Requests\MyNodeWithdrawStoreRequest;
use App\Mail\EmailChangeVerification;
use App\Enums\SocialAccountProvider;
use App\Models\EmailChangeRequest;
use App\Models\SocialAccount;
use App\Models\User;
use Laravel\Socialite\Facades\Socialite;

class MyNodeController extends Controller
{
    /**
     * マイノードトップ表示
     *
     * @param Request $request
     * @return JsonResponse|Application|Factory|View
     */
    public function top(Request $request): JsonResponse|Application|Factory|View
    {
        $user = Auth::user();
        
        $privacyPolicyRevisionDate = Carbon::parse(HgnController::PRIVACY_POLICY_REVISION_DATE);
        $privacyPolicyVersion = (int)$privacyPolicyRevisionDate->format('Ymd');
        
        $acceptedVersion = $user->privacy_policy_accepted_version ?? 0;
        $needsAcceptance = $acceptedVersion < $privacyPolicyVersion;
        
        $request->session()->regenerateToken();

        return $this->tree(
            view('user.my_node.top', compact('user', 'needsAcceptance')), 
            options: [
                'url' => route('User.MyNode.Top'),
                'csrfToken' => csrf_token(),
            ]
        );
    }

    /**
     * プロフィール編集画面表示
     *
     * @return JsonResponse|Application|Factory|View
     */
    public function profile(): JsonResponse|Application|Factory|View
    {
        $user = Auth::user();
        $colorState = $this->getColorState();

        return $this->tree(view('user.my_node.profile', compact('user', 'colorState')));
    }

    /**
     * プロフィール更新処理
     *
     * @param MyNodeProfileUpdateRequest $request
     * @return RedirectResponse
     */
    public function profileUpdate(MyNodeProfileUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validated();

        $user->name = $validated['name'];
        $user->show_id = $validated['show_id'];
        $user->save();

        return redirect()->route('User.MyNode.Top')->with('success', 'プロフィールを更新しました。');
    }

    /**
     * メールアドレス変更画面表示
     * 
     * @return JsonResponse|Application|Factory|View
     */
    public function email(): JsonResponse|Application|Factory|View
    {
        $user = Auth::user();
        $colorState = $this->getColorState();

        return $this->tree(view('user.my_node.email', compact('user', 'colorState')));
    }

    /**
     * メールアドレス変更処理（確認メール送信）
     *
     * @param MyNodeEmailUpdateRequest $request
     * @return RedirectResponse
     */
    public function emailUpdate(MyNodeEmailUpdateRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validated();
        $newEmail = $validated['new_email'];

        EmailChangeRequest::where('user_id', $user->id)->delete();
        EmailChangeRequest::where('new_email', $newEmail)->delete();

        $token = Str::random(64);
        $expiresAt = now()->addMinutes(15);

        EmailChangeRequest::create([
            'user_id' => $user->id,
            'new_email' => $newEmail,
            'token' => $token,
            'expires_at' => $expiresAt,
        ]);

        $verificationUrl = route('User.MyNode.Email.Verify', ['token' => $token]);

        $this->dispatchEmailChangeVerification($user, $newEmail, $verificationUrl);

        return redirect()->route('User.MyNode.Top')->with('success', '確認メールを送信しました。メールをご確認ください。');
    }

    /**
     * メールアドレス変更確定
     *
     * @param Request $request
     * @param string $token
     * @return RedirectResponse
     */
    public function emailVerify(Request $request, string $token): RedirectResponse
    {
        $emailChangeRequest = EmailChangeRequest::where('token', $token)->first();

        if (!$emailChangeRequest) {
            return redirect()->route('Account.Login')->with('error', '無効なURLです。');
        }

        if ($emailChangeRequest->isExpired()) {
            $emailChangeRequest->delete();
            return redirect()->route('Account.Login')->with('error', 'メールアドレス変更の有効期限が切れています。');
        }

        /** @var User|null $user */
        $user = $emailChangeRequest->user;
        if (!$user) {
            $emailChangeRequest->delete();
            return redirect()->route('Account.Login')->with('error', '対象のユーザーが見つかりません。');
        }

        $newEmail = $emailChangeRequest->new_email;
        if (User::where('email', $newEmail)->where('id', '!=', $user->id)->exists()) {
            $emailChangeRequest->delete();
            return redirect()->route('Account.Login')->with('error', 'すでに使用されているメールアドレスです。');
        }

        $user->email = $newEmail;
        $user->save();

        $emailChangeRequest->delete();

        Auth::guard('web')->login($user);
        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('User.MyNode.Top')->with('success', 'メールアドレスを変更しました。');
    }

    /**
     * パスワード変更画面表示
     *
     * @return JsonResponse|Application|Factory|View
     */
    public function password(): JsonResponse|Application|Factory|View
    {
        /** @var User $user */
        $user = Auth::user();
        $colorState = $this->getColorState();

        if ($user->needsPasswordSet()) {
            return $this->tree(view('user.my_node.password_set', compact('user', 'colorState')));
        }

        return $this->tree(view('user.my_node.password', compact('user', 'colorState')));
    }

    /**
     * パスワード設定処理（OAuthユーザー向け・現在のパスワード不要）
     *
     * @param MyNodePasswordSetRequest $request
     * @return RedirectResponse
     */
    public function passwordSetUpdate(MyNodePasswordSetRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user->needsPasswordSet()) {
            return redirect()->route('User.MyNode.Password');
        }

        $validated = $request->validated();
        $user->password = Hash::make($validated['password']);
        $user->setRememberToken(Str::random(60));
        $user->save();

        $request->session()->regenerateToken();

        return redirect()->route('User.MyNode.Top')->with('success', 'パスワードを設定しました。');
    }

    /**
     * パスワード変更処理
     *
     * @param MyNodePasswordUpdateRequest $request
     * @return RedirectResponse
     */
    public function passwordUpdate(MyNodePasswordUpdateRequest $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validated();
        $user->password = Hash::make($validated['password']);
        $user->setRememberToken(Str::random(60));
        $user->save();

        $request->session()->regenerateToken();

        return redirect()->route('User.MyNode.Top')->with('success', 'パスワードを変更しました。');
    }

    /**
     * アカウント連携一覧画面表示
     *
     * @return JsonResponse|Application|Factory|View
     */
    public function socialAccounts(): JsonResponse|Application|Factory|View
    {
        /** @var User $user */
        $user = Auth::user();
        $user->load('socialAccounts');
        $colorState = $this->getColorState();
        $url = route('User.MyNode.SocialAccounts');

        return $this->tree(view('user.my_node.social_accounts', compact('user', 'colorState')), options: ['url' => $url]);
    }

    /**
     * アカウント連携開始（GitHubへリダイレクト）
     *
     * @param Request $request
     * @param string $provider
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToLinkProvider(Request $request, string $provider)
    {
        $request->session()->put('social_link_intent', true);

        if ($provider === 'github') {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            $driver = Socialite::driver('github');
            return $driver->scopes(['user', 'user:email'])->redirect();
        }

        if ($provider === 'x') {
            /** @var \Laravel\Socialite\Two\AbstractProvider $driver */
            return Socialite::driver('twitter-oauth-2')->redirect();
        }

        $request->session()->forget('social_link_intent');
        return redirect()->route('User.MyNode.SocialAccounts')->with('error', 'この連携は現在サポートされていません。');
    }

    /**
     * アカウント連携解除
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function unlinkSocialAccount(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $providerValue = (int) $request->input('provider');
        $provider = SocialAccountProvider::tryFrom($providerValue);

        if (!$provider) {
            return redirect()->route('User.MyNode.SocialAccounts')->with('error', '無効なプロバイダーです。');
        }

        $socialAccount = SocialAccount::where('user_id', $user->id)
            ->where('provider', $provider)
            ->first();

        if (!$socialAccount) {
            return redirect()->route('User.MyNode.SocialAccounts')->with('error', '連携情報が見つかりません。');
        }

        $socialAccountCount = $user->socialAccounts()->count();

        if ($socialAccountCount <= 1 && $user->password === null) {
            return redirect()->route('User.MyNode.SocialAccounts')->with('error', 'パスワードを設定してから連携解除するか、他の連携方法が残る状態で解除してください。');
        }

        $socialAccount->delete();

        return redirect()->route('User.MyNode.SocialAccounts')->with('success', '連携を解除しました。');
    }

    /**
     * 退会画面表示
     *
     * @return JsonResponse|Application|Factory|View
     */
    public function withdraw(): JsonResponse|Application|Factory|View
    {
        $user = Auth::user();
        $colorState = $this->getColorState();

        return $this->tree(view('user.my_node.withdraw', compact('user', 'colorState')));
    }

    /**
     * 退会処理
     *
     * @param MyNodeWithdrawStoreRequest $request
     * @return RedirectResponse
     */
    public function withdrawStore(MyNodeWithdrawStoreRequest $request): RedirectResponse
    {
        $user = Auth::user();

        $user->withdrawn_at = now();
        $user->save();

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('Account.Login')->with('success', "退会が完了しました。\r\nご利用ありがとうございました。");
    }

    /**
     * メール変更確認メール送信を非同期で実行
     * 
     * @param User $user
     * @param string $newEmail
     * @param string $verificationUrl
     * @return void
     */
    private function dispatchEmailChangeVerification(User $user, string $newEmail, string $verificationUrl): void
    {
        Bus::dispatchAfterResponse(function () use ($user, $newEmail, $verificationUrl) {
            try {
                Mail::to($newEmail)->send(new EmailChangeVerification($user, $newEmail, $verificationUrl));
            } catch (\Exception $e) {
                report($e);
            }
        });
    }
}

