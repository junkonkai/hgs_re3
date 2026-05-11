@extends('layout')

@section('title', $title->name . ' ' . $reviewUser->name . 'さんのレビュー')
@section('current-node-title'){{ $title->name }}<br>{{ $reviewUser->name }}さんのレビュー@endsection

@php
    $_siteName   = 'ホラーゲームネットワーク(α)';
    $_ogpTitle   = $title->name . ' ' . $reviewUser->name . 'さんのレビュー';
    $_ogpUrl     = route('Game.TitleReview', ['titleKey' => $title->key, 'reviewKey' => $review->key]);
    $_hasOgpFile = !$review->is_deleted && !$review->is_hidden && $review->ogp_image_filename;
    $_ogpImage   = $_hasOgpFile
        ? asset('img/review/' . $review->ogp_image_filename)
        : asset('img/ogp.png');
    if (!$review->is_deleted && !$review->is_hidden && !$review->has_spoiler && $review->body) {
        $_ogpDescription = mb_strimwidth($review->body, 0, 120, '…');
    } else {
        $_ogpDescription = $title->name . ' ' . $reviewUser->name . 'さんのレビュー';
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
    @endif
@endsection

@section('nodes')
    @if (!$review->is_deleted && !$review->is_hidden)
        {{-- スコア --}}
        <section class="node basic" id="review-score-node">
            <div class="node-head">
                <h2 class="node-head-text">スコア</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm">
                    <div class="flex items-baseline gap-1">
                        @if ($review->total_score !== null)
                            <span class="text-3xl font-bold text-slate-100 leading-none">{{ $review->total_score }}</span>
                            <span class="text-xs text-slate-500">/ 100</span>
                        @else
                            <span class="text-slate-500">-</span>
                        @endif
                    </div>
                    @if ($fearMeter !== null)
                        <div class="text-xs text-slate-400 self-end">
                            怖さメーター:
                            <span class="text-slate-200">{{ $fearMeter->fear_meter->text() }}</span>
                        </div>
                    @endif
                </div>
                @if ($fearMeter !== null || $review->score_story !== null || $review->score_atmosphere !== null || $review->score_gameplay !== null || $review->user_score_adjustment !== null)
                    <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-slate-400">
                        @if ($fearMeter !== null)
                            <span>怖さ: <span class="text-slate-200">{{ (int) $fearMeter->fear_meter->value * 10 }}/40</span></span>
                        @endif
                        @if ($review->score_story !== null)
                            <span>ストーリー: <span class="text-slate-300">{{ $review->score_story }}/20</span></span>
                        @endif
                        @if ($review->score_atmosphere !== null)
                            <span>雰囲気: <span class="text-slate-300">{{ $review->score_atmosphere }}/20</span></span>
                        @endif
                        @if ($review->score_gameplay !== null)
                            <span>ゲーム性: <span class="text-slate-300">{{ $review->score_gameplay }}/20</span></span>
                        @endif
                        @if ($review->user_score_adjustment !== null)
                            <span>さじ加減: <span class="text-slate-300">{{ ($review->user_score_adjustment > 0 ? '+' : '') . $review->user_score_adjustment }}/20</span></span>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        {{-- 怖さコメント --}}
        <section class="node basic" id="review-fear-comment-node">
            <div class="node-head">
                <h2 class="node-head-text">怖さコメント</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                @if ($fearMeterComment)
                    <div class="text-sm leading-relaxed text-slate-100">{!! nl2br(e($fearMeterComment)) !!}</div>
                @else
                    <span class="text-slate-500">怖さコメントはないようだ</span>
                @endif
            </div>
        </section>

        {{-- コメント --}}
        <section class="node basic" id="review-comment-node">
            <div class="node-head">
                <h2 class="node-head-text">コメント</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                @if ($review->body)
                    @if ($review->has_spoiler)
                        <div data-spoiler="hidden">
                            <span class="text-xs text-amber-400 mb-3">【ネタバレがあるようだ】</span>
                            <button type="button" class="js-spoiler-btn ml-3 cursor-pointer text-xs text-slate-400 hover:text-slate-200">表示する</button>
                            <div class="js-spoiler-content text-sm leading-relaxed text-slate-100 transition-opacity duration-300 mt-3" style="opacity: 0.1; user-select: none;">
                                {!! nl2br(e($review->body)) !!}
                            </div>
                        </div>
                    @else
                        <div class="text-sm leading-relaxed text-slate-100">{!! nl2br(e($review->body)) !!}</div>
                    @endif
                @else
                    <span class="text-slate-500">コメントはないようだ</span>
                @endif

                {{-- いいね・通報 --}}
                <div class="mt-4 flex items-center text-sm">
                    @auth
                        @if (Auth::id() !== $review->user_id)
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
                        @endif
                    @else
                        <span class="inline-flex h-6 items-center gap-1 leading-none text-slate-400">
                            <i class="bi bi-hand-thumbs-up"></i> {{ $review->likes_count }}
                        </span>
                    @endauth

                    @auth
                        @if (Auth::id() !== $review->user_id)
                            @if ($userReported)
                                <span class="ml-auto inline-flex h-6 items-center gap-1 leading-none text-slate-500">
                                    <i class="bi bi-flag-fill"></i> 通報済み
                                </span>
                            @else
                                <button type="button"
                                        class="js-review-report-open ml-auto inline-flex h-6 items-center gap-1 leading-none text-slate-400 transition-colors hover:text-rose-400"
                                        data-modal-id="review-report-modal-{{ $review->id }}"
                                        title="通報">
                                    <i class="bi bi-flag"></i> 通報
                                </button>
                            @endif
                        @endif
                    @endauth
                </div>

                {{-- 通報モーダル --}}
                @auth
                    @if (!$userReported)
                        @php
                            $_reportReasons = ['スパム・宣伝', '荒らし・嫌がらせ', 'ネタバレが記載されていない', '不適切な内容', 'その他'];
                        @endphp
                        <dialog id="review-report-modal-{{ $review->id }}" class="js-review-report-modal rounded-lg bg-slate-800 text-white p-0 w-full max-w-md border border-slate-600">
                            <div class="p-6">
                                <h2 class="text-base font-bold mb-4">通報</h2>
                                <form method="POST"
                                      action="{{ route('Game.TitleReview.Report', ['titleKey' => $title->key, 'reviewId' => $review->id]) }}"
                                      class="review-reaction-form mb-0"
                                      data-component-use="1"
                                      data-reaction-kind="report"
                                      data-done="0"
                                      data-modal-id="review-report-modal-{{ $review->id }}">
                                    @csrf
                                    <div class="mb-4">
                                        <p class="text-sm font-semibold mb-2">通報理由（任意・複数選択可）</p>
                                        <div class="space-y-2">
                                            @foreach ($_reportReasons as $reason)
                                                <label class="flex items-center gap-2 text-sm cursor-pointer">
                                                    <input type="checkbox" name="reason_types[]" value="{{ $reason }}" class="accent-rose-400">
                                                    {{ $reason }}
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="mb-5">
                                        <label class="block text-sm font-semibold mb-2" for="report-note-{{ $review->id }}">詳細（任意）</label>
                                        <textarea id="report-note-{{ $review->id }}" name="reason_note" class="form-control w-full" rows="3" maxlength="255" placeholder="詳細を入力..."></textarea>
                                    </div>
                                    <div class="flex gap-3 justify-end">
                                        <button type="button" class="js-report-modal-cancel btn btn-sm btn-default">キャンセル</button>
                                        <button type="submit" class="btn btn-sm btn-danger">通報する</button>
                                    </div>
                                </form>
                            </div>
                        </dialog>
                    @endif
                @endauth
            </div>
        </section>

        {{-- プレイ状況 --}}
        <section class="node basic" id="review-play-status-node">
            <div class="node-head">
                <h2 class="node-head-text">プレイ状況</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                @if ($review->play_status !== null)
                    @if ($review->play_status === \App\Enums\PlayStatus::Watched)
                        <span class="text-sky-400">{{ $review->play_status->text() }}</span>
                    @else
                        <span class="text-slate-300">{{ $review->play_status->text() }}</span>
                    @endif
                @else
                    <span class="text-slate-500">-</span>
                @endif
            </div>
        </section>

        {{-- プレイ環境 --}}
        <section class="node basic" id="review-packages-node">
            <div class="node-head">
                <h2 class="node-head-text">プレイ環境</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                @if ($review->packages->isNotEmpty())
                    <div class="flex flex-wrap gap-2 text-sm text-slate-300">
                        @foreach ($review->packages as $pkg)
                            <span>{{ $pkg->gamePackage->platform->acronym }}&nbsp;{{ $pkg->gamePackage->node_name }}</span>
                        @endforeach
                    </div>
                @else
                    <span class="text-slate-500">-</span>
                @endif
            </div>
        </section>

        {{-- 更新日時 --}}
        <section class="node basic" id="review-updated-at-node">
            <div class="node-head">
                <h2 class="node-head-text">更新日時</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                <div class="flex items-center gap-3 text-sm text-slate-400">
                    <span>{{ $review->updated_at->format('Y-m-d H:i') }}</span>
                    @auth
                        @if (Auth::id() === $review->user_id)
                            <a href="{{ route('User.Review.Form', ['titleKey' => $title->key]) }}"
                               class="inline-flex items-center gap-1 text-slate-400 transition-colors hover:text-slate-200"
                               data-hgn-scope="full">
                                <i class="bi bi-pencil"></i> 編集
                            </a>
                        @endif
                    @endauth
                </div>
            </div>
        </section>

        {{-- シェア --}}
        @php
            $_shareText = Auth::id() === $reviewUser->id
                ? $title->name . ' のレビューを書きました！'
                : $title->name . ' のレビュー（' . $reviewUser->show_id . '）';
        @endphp
        <section class="node basic" id="review-share-node">
            <div class="node-head">
                <h2 class="node-head-text">シェア</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                <div class="flex items-center gap-4 text-sm">
                    <a href="https://twitter.com/intent/tweet?text={{ urlencode($_shareText) }}&url={{ urlencode($_ogpUrl) }}"
                       target="_blank"
                       class="inline-flex items-center gap-1 text-slate-400 transition-colors hover:text-sky-400">
                        <i class="bi bi-twitter-x"></i>
                    </a>
                    <a href="https://social-plugins.line.me/lineit/share?url={{ urlencode($_ogpUrl) }}"
                       target="_blank"
                       class="inline-flex items-center gap-1 text-slate-400 transition-colors hover:text-green-400">
                        LINE
                    </a>
                </div>
            </div>
        </section>
    @endif

    {{-- 近道 --}}
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
                    <section class="node basic" id="review-link-node">
                        <div class="node-head">
                            <a href="{{ route('Game.Reviews') }}" class="node-head-text" data-hgn-scope="full">レビュー</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                </div>
            </section>

            @include('common.shortcut_mynode')
        </div>
    </section>
@endsection
