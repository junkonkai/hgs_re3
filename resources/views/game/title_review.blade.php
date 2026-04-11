@extends('layout')

@section('title', $title->name . ' のレビュー — ' . $reviewUser->show_id)
@section('current-node-title', $title->name . ' のレビュー — ' . $reviewUser->show_id)

@php
    $_siteName   = 'ホラーゲームネットワーク(α)';
    $_ogpTitle   = $title->name . ' のレビュー — ' . $reviewUser->show_id;
    $_ogpUrl     = route('Game.TitleReview', ['titleKey' => $title->key, 'showId' => $reviewUser->show_id]);
    $_hasOgpFile = !$review->is_deleted && !$review->is_hidden && $review->ogp_image_path;
    $_ogpImage   = $_hasOgpFile
        ? asset(substr($review->ogp_image_path, 7))  // 'public/' の 7 文字を除去
        : asset('img/ogp.png');
    if (!$review->is_deleted && !$review->is_hidden && !$review->has_spoiler && $review->body) {
        $_ogpDescription = mb_strimwidth($review->body, 0, 120, '…');
    } else {
        $_ogpDescription = $title->name . ' のレビュー（' . $reviewUser->show_id . '）';
    }
@endphp
@section('ogp')
<meta property="og:title" content="{{ $_ogpTitle }}">
<meta property="og:description" content="{{ $_ogpDescription }}">
<meta property="og:url" content="{{ $_ogpUrl }}">
<meta property="og:type" content="article">
<meta property="og:site_name" content="{{ $_siteName }}">
<meta property="og:image" content="{{ $_ogpImage }}">
<meta name="twitter:card" content="{{ $_hasOgpFile ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $_ogpTitle }}">
<meta name="twitter:description" content="{{ $_ogpDescription }}">
<meta name="twitter:image" content="{{ $_ogpImage }}">
@endsection

@section('current-node-content')
    @if ($review->is_deleted)
        <div class="alert alert-warning mt-3">
            このレビューは削除されました。
        </div>
    @elseif ($review->is_hidden)
        <div class="alert alert-warning mt-3">
            このレビューは非表示になっています。
        </div>
    @else
        <div class="mt-3 space-y-3">
            {{-- メタ情報 --}}
            <div class="text-sm text-slate-400 flex flex-wrap gap-x-4 gap-y-1">
                <span>{{ $review->play_status?->text() }}</span>
                @if ($review->play_time)
                    <span>{{ $review->play_time->text() }}</span>
                @endif
                @if ($review->play_status === \App\Enums\PlayStatus::Watched)
                    <span class="text-sky-400">配信・動画での視聴に基づくレビュー</span>
                @endif
                @if ($review->has_spoiler)
                    <span class="text-amber-400">【ネタバレあり】</span>
                @endif
                <span>{{ $review->updated_at->format('Y-m-d') }}</span>
            </div>

            {{-- プレイ環境 --}}
            @if ($review->packages->isNotEmpty())
                <div class="text-sm text-slate-400">
                    プレイ環境:
                    @foreach ($review->packages as $pkg)
                        <span class="text-slate-300">{{ $pkg->gamePackage?->platform?->acronym ?? '?' }}{{ $loop->last ? '' : '、' }}</span>
                    @endforeach
                </div>
            @endif

            {{-- ホラー種別タグ --}}
            @if ($review->horrorTypeTags->isNotEmpty())
                <div class="flex flex-wrap gap-1">
                    @foreach ($review->horrorTypeTags as $tag)
                        <span class="text-xs px-1.5 py-0.5 rounded bg-slate-700 text-slate-300">{{ $tag->tag->text() }}</span>
                    @endforeach
                </div>
            @endif

            {{-- スコア --}}
            @if ($review->total_score !== null)
                <div class="space-y-1">
                    <div class="text-2xl font-bold text-slate-100">
                        {{ $review->total_score }}<span class="text-sm font-normal text-slate-400"> / 100</span>
                    </div>
                    <div class="text-xs text-slate-400 flex flex-wrap gap-x-4 gap-y-1">
                        @if ($review->score_story !== null)
                            <span>ストーリー: {{ $review->score_story }}/4</span>
                        @endif
                        @if ($review->score_atmosphere !== null)
                            <span>雰囲気・演出: {{ $review->score_atmosphere }}/4</span>
                        @endif
                        @if ($review->score_gameplay !== null)
                            <span>ゲーム性: {{ $review->score_gameplay }}/4</span>
                        @endif
                        @if ($review->user_score_adjustment !== null && $review->user_score_adjustment !== 0)
                            <span>ユーザー調整: {{ $review->user_score_adjustment > 0 ? '+' : '' }}{{ $review->user_score_adjustment }}</span>
                        @endif
                    </div>
                </div>
            @endif

            {{-- 本文 --}}
            @if ($review->has_spoiler)
                <details class="text-sm">
                    <summary class="cursor-pointer text-slate-400 hover:text-slate-200">本文を表示（ネタバレあり）</summary>
                    <div class="mt-3 leading-relaxed text-slate-100">{!! nl2br(e($review->body)) !!}</div>
                </details>
            @else
                <div class="text-sm leading-relaxed text-slate-100">{!! nl2br(e($review->body)) !!}</div>
            @endif

            {{-- いいね・通報 --}}
            <div class="mt-4 flex items-center gap-6 text-sm">
                @auth
                    <form method="POST"
                          action="{{ route('Game.TitleReview.Like', ['titleKey' => $title->key, 'reviewId' => $review->id]) }}"
                          class="review-reaction-form inline-flex items-center mb-0"
                          data-component-use="1"
                          data-reaction-kind="like"
                          data-done="{{ $userLiked ? '1' : '0' }}"
                          data-like-url="{{ route('Game.TitleReview.Like', ['titleKey' => $title->key, 'reviewId' => $review->id]) }}"
                          data-unlike-url="{{ route('Game.TitleReview.UnlikePost', ['titleKey' => $title->key, 'reviewId' => $review->id]) }}">
                        @csrf
                        <button type="submit" class="inline-flex h-6 items-center gap-1 leading-none text-slate-400 transition-colors hover:text-sky-400" title="いいね">
                            <i class="bi bi-hand-thumbs-up"></i> <span class="js-like-count">{{ $review->likes_count }}</span>
                        </button>
                    </form>
                @else
                    <span class="inline-flex h-6 items-center gap-1 leading-none text-slate-400">
                        <i class="bi bi-hand-thumbs-up"></i> {{ $review->likes_count }}
                    </span>
                @endauth

                @auth
                    @if ($userReported)
                        <span class="inline-flex h-6 items-center gap-1 leading-none text-slate-500">
                            <i class="bi bi-flag-fill"></i> 通報済み
                        </span>
                    @else
                        <form method="POST"
                              action="{{ route('Game.TitleReview.Report', ['titleKey' => $title->key, 'reviewId' => $review->id]) }}"
                              class="review-reaction-form inline-flex items-center mb-0"
                              data-component-use="1"
                              data-reaction-kind="report"
                              data-done="0">
                            @csrf
                            <button type="submit" class="inline-flex h-6 items-center gap-1 leading-none text-slate-400 transition-colors hover:text-rose-400" title="通報">
                                <i class="bi bi-flag"></i> 通報
                            </button>
                        </form>
                    @endif
                @endauth
            </div>

            {{-- SNS 共有 --}}
            @php
                $_shareText = $title->name . ' のレビューを書きました！';
            @endphp
            <div class="mt-3 flex items-center gap-4 text-sm">
                <span class="text-slate-500">シェア：</span>
                <a href="https://twitter.com/intent/tweet?text={{ urlencode($_shareText) }}&url={{ urlencode($_ogpUrl) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1 text-slate-400 transition-colors hover:text-sky-400">
                    <i class="bi bi-twitter-x"></i> X
                </a>
                <a href="https://social-plugins.line.me/lineit/share?url={{ urlencode($_ogpUrl) }}"
                   target="_blank"
                   class="inline-flex items-center gap-1 text-slate-400 transition-colors hover:text-green-400">
                    LINE
                </a>
            </div>
        </div>
    @endif
@endsection

@section('nodes')
    <section class="node tree-node" id="footer-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node tree-node" id="reviews-link-node">
                <div class="node-head">
                    <a href="{{ route('Game.TitleReviews', ['titleKey' => $title->key]) }}" class="node-head-text" data-hgn-scope="full">{{ $title->name }} レビュー一覧</a>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content tree">
                    <section class="node tree-node" id="title-link-node">
                        <div class="node-head">
                            <a href="{{ route('Game.TitleDetail', ['titleKey' => $title->key]) }}" class="node-head-text" data-hgn-scope="full">{{ $title->name }}</a>
                            <span class="node-pt">●</span>
                        </div>
                        <div class="node-content tree">
                            <section class="node tree-node" id="franchise-link-node">
                                <div class="node-head">
                                    <a href="{{ route('Game.FranchiseDetail', ['franchiseKey' => $franchise->key]) }}" class="node-head-text" data-hgn-scope="full">{{ $franchise->name }}フランチャイズ</a>
                                    <span class="node-pt">●</span>
                                </div>
                                <div class="node-content tree">
                                    <section class="node tree-node" id="lineup-link-node">
                                        <div class="node-head">
                                            <a href="{{ route('Game.Lineup') }}" class="node-head-text" data-hgn-scope="full">ラインナップ</a>
                                            <span class="node-pt">●</span>
                                        </div>
                                        <div class="node-content tree">
                                            <section class="node basic" id="root-link-node">
                                                <div class="node-head">
                                                    <a href="{{ route('Root') }}" class="node-head-text" data-hgn-scope="full">ルート</a>
                                                    <span class="node-pt">●</span>
                                                </div>
                                            </section>
                                        </div>
                                    </section>
                                </div>
                            </section>
                        </div>
                    </section>
                </div>
            </section>

            @include('common.shortcut_mynode')
        </div>
    </section>
@endsection
