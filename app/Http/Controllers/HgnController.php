<?php

namespace App\Http\Controllers;

use App\Http\Requests\HgnPrivacyPolicyAcceptRequest;
use App\Models\Information;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class HgnController extends Controller
{
    /**
     * プライバシーポリシーの最終改定日
     */
    public const PRIVACY_POLICY_REVISION_DATE = '2025-11-18';

    /**
     * トップページ
     *
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function root(): JsonResponse|Application|Factory|View
    {
        Log::error('テストエラー');
        $infoList = Information::select(['id', 'head'])
            ->where('open_at', '<', now())
            ->where('close_at', '>=', now())
            ->orderBy('priority', 'desc')
            ->orderBy('open_at', 'desc')
            ->limit(3)
            ->get();

        return $this->tree(view('root', compact('infoList')), ['url' => route('Root'), 'csrfToken' => csrf_token()]);
    }

    /**
     * お知らせ一覧
     *
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function infomations(): JsonResponse|Application|Factory|View
    {
        $informations = Information::where('open_at', '<=', now())
            ->where('close_at', '>=', now())
            ->orderBy('priority', 'desc')
            ->orderBy('open_at', 'desc')
            ->get();

        return $this->tree(view('infomations', compact('informations')));
    }

    /**
     * お知らせ
     *
     * @param Information $info
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function infomationDetail(Information $info): JsonResponse|Application|Factory|View
    {
        return $this->tree(view('infomation_detail', compact('info')));
    }

    /**
     * 当サイトについて
     *
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function about(): JsonResponse|Application|Factory|View
    {
        return $this->tree(view('about', [
            'ogpTitle' => 'このサイトについて',
            'ogpDescription' => 'ホラーゲーム好きのためのコミュニティサイトです。レビューや二次創作など、みなさんの「好き」を共有し、より深くホラーゲームを楽しんでほしいという想いで運営しています。',
        ]));
    }

    /**
     * プライバシーポリシー
     *
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function privacyPolicy(): JsonResponse|Application|Factory|View
    {
        $privacyPolicyRevisionDate = Carbon::parse(self::PRIVACY_POLICY_REVISION_DATE);
        $privacyPolicyVersion = (int)$privacyPolicyRevisionDate->format('Ymd');
        
        $needsAcceptance = false;
        if (Auth::check()) {
            $user = Auth::user();
            $acceptedVersion = $user->privacy_policy_accepted_version ?? 0;
            $needsAcceptance = $acceptedVersion < $privacyPolicyVersion;
        }
        
        return $this->tree(view('privacy_policy', compact('privacyPolicyRevisionDate', 'privacyPolicyVersion', 'needsAcceptance')));
    }

    /**
     * プライバシーポリシー承認
     *
     * @param HgnPrivacyPolicyAcceptRequest $request
     * @return RedirectResponse
     */
    public function acceptPrivacyPolicy(HgnPrivacyPolicyAcceptRequest $request): RedirectResponse
    {
        if (!Auth::check()) {
            return redirect()->route('PrivacyPolicy');
        }

        $user = Auth::user();
        $privacyPolicyRevisionDate = Carbon::parse(self::PRIVACY_POLICY_REVISION_DATE);
        $privacyPolicyVersion = (int)$privacyPolicyRevisionDate->format('Ymd');

        $user->privacy_policy_accepted_version = $privacyPolicyVersion;
        $user->save();

        return redirect()->route('PrivacyPolicy')->with('success', 'プライバシーポリシーを確認しました。');
    }

    /**
     * ロゴ
     *
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function logo(): JsonResponse|Application|Factory|View
    {
        return $this->tree(view('logo'));
    }
}
