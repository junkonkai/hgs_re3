@extends('layout')

@section('title', $title->name . ' レビュー一覧')
@section('current-node-title', $title->name . ' レビュー一覧')

@section('current-node-content')
    @if (session('success'))
        <div class="alert alert-success mt-3">
            {!! nl2br(e(session('success'))) !!}
        </div>
    @endif

    @php
        $reviewStat   = $title->reviewStatistic;
        $fearMeterStat = $title->fearMeterStatistic;
    @endphp

    @if ($reviewStat !== null || $fearMeterStat !== null)
        <h4 class="mt-3 mb-2">総合評価</h4>
        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm">
            {{-- 総合スコア --}}
            <div class="flex items-baseline gap-1">
                @if ($reviewStat?->avg_total_score !== null)
                    <span class="text-2xl font-bold text-slate-100 leading-none">{{ round((float) $reviewStat->avg_total_score) }}</span>
                    <span class="text-xs text-slate-500">/ 100</span>
                @else
                    <span class="text-slate-500">-</span>
                @endif
                @if ($reviewStat !== null)
                    <span class="text-xs text-slate-400 ml-1">{{ $reviewStat->review_count }}件</span>
                @endif
            </div>

            {{-- 怖さメーター --}}
            @if ($fearMeterStat !== null)
                <div class="text-xs text-slate-400 self-end">
                    怖さメーター:
                    <span class="text-slate-200">{{ $fearMeterStat->fear_meter->text() }}</span>
                </div>
            @endif
        </div>

        <div class="mt-1.5 pb-4 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-slate-400">
            @if ($fearMeterStat !== null)
                <span>怖さ: <span class="text-slate-200">{{ number_format((float) $fearMeterStat->average_rating * 10, 1) }}/40</span></span>
            @endif
            @if ($reviewStat?->avg_story !== null)
                <span>ストーリー: <span class="text-slate-300">{{ round((float) $reviewStat->avg_story) }}/20</span></span>
            @endif
            @if ($reviewStat?->avg_atmosphere !== null)
                <span>雰囲気: <span class="text-slate-300">{{ round((float) $reviewStat->avg_atmosphere) }}/20</span></span>
            @endif
            @if ($reviewStat?->avg_gameplay !== null)
                <span>ゲーム性: <span class="text-slate-300">{{ round((float) $reviewStat->avg_gameplay) }}/20</span></span>
            @endif
        </div>
    @endif

    @if ($reviews->isEmpty())
        <p class="mt-3">レビューはまだないようだ。</p>
    @endif
@endsection

@section('nodes')
    @foreach ($reviews as $review)
        <section class="node" id="review-{{ $review->user?->show_id }}-node">
            <div class="node-head">
                <span class="node-head-text">{{ $review->user?->name ?? '(不明)' }}さんのレビュー</span>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                @php $fearMeter = $fearMeters[$review->user_id] ?? null; @endphp
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm">
                    {{-- 総合スコア --}}
                    <div class="flex items-baseline gap-1">
                        @if ($review->total_score !== null)
                            <span class="text-2xl font-bold text-slate-100 leading-none">{{ $review->total_score }}</span>
                            <span class="text-xs text-slate-500">/ 100</span>
                        @else
                            <span class="text-slate-500">-</span>
                        @endif
                    </div>

                    {{-- 怖さメーター --}}
                    @if ($fearMeter !== null)
                        <div class="text-xs text-slate-400 self-end">
                            怖さメーター: <span class="text-slate-200">{{ $fearMeter->fear_meter->text() }}</span>
                        </div>
                    @endif
                </div>

                {{-- 各スコア --}}
                <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-slate-400">
                    @if ($fearMeter !== null)
                        <span>怖さ: <span class="text-slate-200">{{ $fearMeter->fear_meter->value * 10 }}/40</span></span>
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
                        <span>さじ加減: <span class="text-slate-300">{{ $review->user_score_adjustment > 0 ? '+' : '' }}{{ $review->user_score_adjustment }}/20</span></span>
                    @endif
                </div>

                {{-- ホラータグ --}}
                @if ($review->horrorTypeTags->isNotEmpty())
                    <div class="mt-2 flex flex-wrap gap-1">
                        @foreach ($review->horrorTypeTags as $tag)
                            <span class="text-xs px-1.5 py-0.5 rounded bg-slate-700 text-slate-300">{{ $tag->tag->text() }}</span>
                        @endforeach
                    </div>
                @endif

                {{-- プレイ状況・更新日 --}}
                <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-slate-400">
                    @if ($review->play_status !== null)
                        @if ($review->play_status === \App\Enums\PlayStatus::Watched)
                            <span class="text-sky-400">{{ $review->play_status->text() }}</span>
                        @else
                            <span>{{ $review->play_status->text() }}</span>
                        @endif
                    @endif
                    <span class="text-slate-500">{{ $review->updated_at->format('Y-m-d') }}</span>
                </div>

                {{-- 本文 --}}
                @if ($review->has_spoiler)
                    <div class="mt-2 text-sm text-slate-200">ネタバレがあるようだ。全文を読むで表示できる。</div>
                @else
                    <div class="mt-2 text-sm leading-relaxed text-slate-100">{!! nl2br(e(mb_strimwidth($review->body, 0, 200, '…'))) !!}</div>
                @endif
                <div class="mt-1 text-xs">
                    <a href="{{ route('Game.TitleReview', ['titleKey' => $title->key, 'reviewKey' => $review->key]) }}" data-hgn-scope="full">全文を読む</a>
                </div>
            </div>

            @if ($loop->last)
            <div class="node-content basic" id="under-pager">
                @include('common.pager', ['pager' => $pager])
            </div>
            @endif
        </section>
    @endforeach

    <section class="node tree-node" id="footer-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node basic" id="reviews-link-node">
                <div class="node-head">
                    <a href="{{ route('Game.Reviews') }}" class="node-head-text" data-hgn-scope="full">レビュー</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
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
