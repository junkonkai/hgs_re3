@extends('layout')

@section('title', 'レビュー一覧')
@section('current-node-title', 'レビュー一覧')

@section('nodes')
    <section class="node" id="reviews-list-node">
        <div class="node-head">
            <h2 class="node-head-text">レビュー一覧</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            @if ($reviews->isEmpty())
                <p>レビューはまだないようだ。</p>
            @else
                @foreach ($reviews as $review)
                    <div class="py-4 border-b border-white/10 last:border-b-0">
                        <div class="mb-1 text-xs text-slate-400 flex flex-wrap gap-x-3 gap-y-1">
                            <a href="{{ route('Game.TitleDetail', ['titleKey' => $review->gameTitle?->key]) }}" data-hgn-scope="full" class="font-medium text-slate-200 hover:text-white">{{ $review->gameTitle?->name ?? '(不明)' }}</a>
                            <a href="{{ route('Game.TitleReview', ['titleKey' => $review->gameTitle?->key, 'showId' => $review->user?->show_id]) }}" data-hgn-scope="full" class="text-slate-300 hover:text-white">{{ $review->user?->name ?? '(不明)' }}</a>
                            <span>{{ $review->play_status?->text() }}</span>
                            @if ($review->play_time)
                                <span>{{ $review->play_time->text() }}</span>
                            @endif
                            @if ($review->total_score !== null)
                                <span class="font-semibold text-slate-200">{{ $review->total_score }}<span class="font-normal text-slate-400">/100</span></span>
                            @endif
                            @if ($review->has_spoiler)
                                <span class="text-amber-400">【ネタバレあり】</span>
                            @endif
                            @if ($review->play_status === \App\Enums\PlayStatus::Watched)
                                <span class="text-sky-400">配信・動画視聴</span>
                            @endif
                            <span class="text-slate-500">{{ $review->updated_at->format('Y-m-d') }}</span>
                        </div>
                        @if ($review->horrorTypeTags->isNotEmpty())
                            <div class="mb-2 flex flex-wrap gap-1">
                                @foreach ($review->horrorTypeTags as $tag)
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-slate-700 text-slate-300">{{ $tag->tag->text() }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if ($review->has_spoiler)
                            <details class="text-sm">
                                <summary class="cursor-pointer text-slate-400 hover:text-slate-200">本文を表示（ネタバレあり）</summary>
                                <div class="mt-2 leading-relaxed text-slate-100">{!! nl2br(e($review->body)) !!}</div>
                            </details>
                        @else
                            <div class="text-sm leading-relaxed text-slate-100">{!! nl2br(e(mb_strimwidth($review->body, 0, 200, '…'))) !!}</div>
                        @endif
                        <div class="mt-1 text-xs">
                            <a href="{{ route('Game.TitleReview', ['titleKey' => $review->gameTitle?->key, 'showId' => $review->user?->show_id]) }}" data-hgn-scope="full">全文を読む</a>
                        </div>
                    </div>
                @endforeach
                @include('common.pager', ['pager' => $pager])
            @endif
        </div>
    </section>

    <section class="node basic" id="root-shortcut-node">
        <div class="node-head">
            <a href="{{ route('Root') }}" class="node-head-text" data-hgn-scope="full">ルート</a>
            <span class="node-pt">●</span>
        </div>
    </section>
@endsection
