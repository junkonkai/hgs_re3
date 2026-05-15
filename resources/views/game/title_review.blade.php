@extends('layout')

@section('title', $title->name . ' ' . $reviewUser->name . 'さんのレビュー')
@section('current-node-title'){{ $title->name }}<br>{{ $reviewUser->name }}さんのレビュー@endsection

@php
    $_siteName   = 'ホラーゲームネットワーク(β)';
    $_ogpTitle   = $title->name . ' ' . $reviewUser->name . 'さんのレビュー';
    $_ogpUrl     = route('Game.TitleReview', ['titleKey' => $title->key, 'reviewKey' => $review->key]);
    $_hasOgpFile = !$review->is_deleted && !$review->is_hidden && $review->ogp_image_filename;
    $_ogpImage   = $_hasOgpFile
        ? url('img/review/' . $review->ogp_image_filename)
        : url('img/ogp.png');
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
    @if (session('success'))
        <div class="alert alert-success mt-3 relative pr-10">
            <button type="button" class="absolute top-0 right-0 p-2 border-0 bg-transparent cursor-pointer" style="line-height: 1;" onclick="this.closest('.alert').style.display='none'" aria-label="閉じる"><i class="bi bi-x"></i></button>
            {!! nl2br(e(session('success'))) !!}
        </div>
    @endif
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
                </div>
                @if ($fearMeter !== null || $review->score_story !== null || $review->score_atmosphere !== null || $review->score_gameplay !== null || $review->user_score_adjustment !== null)
                    <div class="mt-3 space-y-1">
                        @if ($fearMeter !== null)
                            <div class="text-sm text-slate-400">怖さ: <span class="text-slate-200">{{ (int) $fearMeter->fear_meter->value * 10 }}/40</span></div>
                        @endif
                        @if ($review->score_story !== null || $review->score_atmosphere !== null || $review->score_gameplay !== null || $review->user_score_adjustment !== null)
                            <div class="flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-slate-400">
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
                @endif
            </div>
        </section>

        {{-- 怖さメーター --}}
        <section class="node basic" id="review-fear-meter-node">
            <div class="node-head">
                <h2 class="node-head-text">怖さメーター</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                @if ($fearMeter !== null)
                    @php
                        $fearMeterMax = 4;
                        $fearMeterPercent = ($fearMeter->fear_meter->value / $fearMeterMax) * 100;
                    @endphp
                    <div class="space-y-1">
                        <div class="h-3 w-48 overflow-hidden rounded-full bg-slate-700/60">
                            <div
                                class="h-full bg-gradient-to-r from-slate-800 via-sky-600 to-indigo-500"
                                style="width: {{ $fearMeterPercent }}%;"
                            ></div>
                        </div>
                        <div class="text-sm text-slate-200">
                            <span class="font-semibold">{{ $fearMeter->fear_meter->value }} / {{ $fearMeterMax }}</span>
                            <span class="text-slate-400">（{{ $fearMeter->fear_meter->text() }}）</span>
                        </div>
                    </div>
                    @if ($fearMeterComment)
                        <div class="mt-3 text-sm leading-relaxed text-slate-100">{!! nl2br(e($fearMeterComment)) !!}</div>
                    @endif
                @else
                    <span class="text-slate-500">怖さメーターは未入力のようだ</span>
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
                        <div>
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
        @if ($title->packageGroups->isNotEmpty())
            @php
                $checkedPackageIds = $review->packages->pluck('game_package_id')->all();
            @endphp
            <section class="node basic" id="review-packages-node">
                <div class="node-head">
                    <h2 class="node-head-text">プレイ環境</h2>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content basic">
                    @foreach ($title->packageGroups->sortByDesc('sort_order') as $pkgGroup)
                        <div class="mb-3">
                            <p class="mb-1 text-slate-400">{{ $pkgGroup->name }}</p>
                            <div class="packages-readonly flex flex-wrap gap-3 pointer-events-none">
                                @foreach ($pkgGroup->packages->sortBy([['sort_order', 'desc'], ['game_platform_id', 'desc'], ['default_img_type', 'desc']]) as $package)
                                    <label class="flex items-center gap-2 cursor-default">
                                        <input
                                            type="checkbox"
                                            value="{{ $package->id }}"
                                            {{ in_array($package->id, $checkedPackageIds) ? 'checked' : '' }}
                                        >
                                        {{ $package->platform->acronym }}&nbsp;{!! $package->node_name !!}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

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
                <div class="flex items-center gap-10 text-sm">
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

        {{-- 投稿情報 --}}
        <section class="node basic" id="review-meta-node">
            <div class="node-head">
                <h2 class="node-head-text">投稿情報</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic space-y-3 text-sm text-slate-400">
                {{-- いいね --}}
                <div class="flex items-center text-sm">
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
                        @else
                            <span class="inline-flex h-6 items-center gap-1 leading-none">
                                <i class="bi bi-hand-thumbs-up"></i> {{ $review->likes_count }}
                            </span>
                        @endif
                    @else
                        <span class="inline-flex h-6 items-center gap-1 leading-none">
                            <i class="bi bi-hand-thumbs-up"></i> {{ $review->likes_count }}
                        </span>
                    @endauth
                </div>

                {{-- 通報モーダル --}}
                @auth
                    @if (!$userReported && Auth::id() !== $review->user_id)
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

                {{-- 更新日時・編集リンク・通報 --}}
                <div>
                    <div>更新日時：{{ $review->updated_at->format('Y-m-d H:i') }}</div>
                    @auth
                        @if (Auth::id() === $review->user_id)
                            <div class="mt-3">
                                <a href="{{ route('User.Review.Form', ['titleKey' => $title->key]) }}"
                                   class="inline-flex items-center gap-1 text-slate-400 transition-colors hover:text-slate-200"
                                   data-hgn-scope="full"><i class="bi bi-pencil"></i> 編集する（削除もこちらから）</a>
                            </div>
                        @else
                            @if ($userReported)
                                <div class="mt-3">
                                    <span class="inline-flex h-6 items-center gap-1 leading-none text-slate-500">
                                        <i class="bi bi-flag-fill"></i> 通報済み
                                    </span>
                                </div>
                            @else
                                <div class="mt-3">
                                    <button type="button"
                                            class="js-review-report-open inline-flex h-6 items-center gap-1 leading-none text-slate-400 transition-colors hover:text-rose-400"
                                            data-modal-id="review-report-modal-{{ $review->id }}"
                                            title="通報">
                                        <i class="bi bi-flag"></i> 通報
                                    </button>
                                </div>
                            @endif
                        @endif
                    @endauth
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
