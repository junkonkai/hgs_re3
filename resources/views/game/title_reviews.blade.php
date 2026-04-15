@extends('layout')

@section('title', $title->name . ' レビュー一覧')
@section('current-node-title', $title->name . ' レビュー一覧')

@section('current-node-content')
    @if (session('success'))
        <div class="alert alert-success mt-3">
            {!! nl2br(e(session('success'))) !!}
        </div>
    @endif
@endsection

@section('nodes')
    <section class="node" id="title-reviews-list-node">
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
                        @if ($review->has_spoiler)
                            <div class="mb-1 text-xs text-amber-400">【ネタバレあり】</div>
                        @endif
                        <div class="mb-1 text-xs text-slate-400 flex flex-wrap gap-x-3 gap-y-1">
                            <a href="{{ route('Game.TitleReview', ['titleKey' => $title->key, 'showId' => $review->user?->show_id]) }}" data-hgn-scope="full" class="font-medium text-slate-300 hover:text-white">{{ $review->user?->show_id ?? '(不明)' }}</a>
                            @if ($review->play_status === \App\Enums\PlayStatus::Watched)
                                <span class="text-sky-400">{{ $review->play_status->text() }}</span>
                            @else
                                <span>{{ $review->play_status?->text() }}</span>
                            @endif
                            @if ($review->total_score !== null)
                                <span class="font-semibold text-slate-200">{{ $review->total_score }}<span class="font-normal text-slate-400">/100</span></span>
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
                            <a href="{{ route('Game.TitleReview', ['titleKey' => $title->key, 'showId' => $review->user?->show_id]) }}" data-hgn-scope="full">全文を読む</a>
                        </div>
                    </div>
                @endforeach
                @include('common.pager', ['pager' => $pager])
            @endif
        </div>
    </section>

    <section class="node tree-node" id="footer-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
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

            @include('common.shortcut_mynode')
        </div>
    </section>
@endsection
