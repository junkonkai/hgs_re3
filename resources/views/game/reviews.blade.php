@extends('layout')

@section('title', 'レビュー')
@section('current-node-title', 'レビュー')

@section('current-node-content')
    {{-- 並び順タブ --}}
    @php
        $sortOptions = [
            'newest'     => '新着順',
            'score'      => '総合スコア',
            'fear'       => '怖さ',
            'story'      => 'ストーリー',
            'atmosphere' => '雰囲気',
            'gameplay'   => 'ゲーム性',
        ];
    @endphp
    <div class="mb-8 flex flex-wrap gap-1.5" data-sort-tabs>
        @foreach ($sortOptions as $key => $label)
            <a href="{{ route('Game.Reviews', $key !== 'newest' ? ['sort' => $key] : []) }}"
               data-hgn-scope="children"
               class="btn btn-sm btn-default{{ $sort === $key ? ' is-active' : '' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    @if ($titles->isEmpty())
        <p>レビューはまだないようだ。</p>
    @endif
@endsection

@section('nodes')
    @foreach ($titles as $title)
        @php
            $fearMeterEnum = $title->fear_meter !== null
                ? \App\Enums\FearMeter::tryFrom((int) $title->fear_meter)
                : null;
        @endphp
        <section class="node" id="{{ $title->key }}-review-node">
            <div class="node-head">
                <a href="{{ route('Game.TitleReviews', ['titleKey' => $title->key]) }}"
                   class="node-head-text text-xl" data-hgn-scope="full">{{ $title->name }}</a>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic" id="{{ $title->key }}-review-node-content">
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm">
                    {{-- 総合スコア --}}
                    <div class="flex items-baseline gap-1">
                        @if ($title->avg_total_score !== null)
                            <span class="text-3xl font-bold text-slate-100 leading-none">{{ round((float) $title->avg_total_score) }}</span>
                            <span class="text-xs text-slate-500">/ 100</span>
                        @else
                            <span class="text-slate-500">-</span>
                        @endif
                        <span class="text-xs text-slate-400 ml-1">{{ $title->review_count }}件</span>
                    </div>

                    {{-- 怖さメーター --}}
                    @if ($fearMeterEnum !== null)
                        <div class="text-xs text-slate-400 self-end">
                            怖さメーター:
                            <span class="text-slate-200">{{ $fearMeterEnum->text() }}</span>
                        </div>
                    @endif
                </div>

                {{-- 各スコア --}}
                @if ($title->avg_story !== null || $title->avg_atmosphere !== null || $title->avg_gameplay !== null)
                    <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-slate-400">
                        @if ($fearMeterEnum !== null)
                            <span>怖さ: <span class="text-slate-200">{{ number_format((float) $title->fear_meter_avg, 1) * 10 }}/40</span></span>
                        @endif
                        @if ($title->avg_story !== null)
                            <span>ストーリー: <span class="text-slate-300">{{ round((float) $title->avg_story) }}/20</span></span>
                        @endif
                        @if ($title->avg_atmosphere !== null)
                            <span>雰囲気: <span class="text-slate-300">{{ round((float) $title->avg_atmosphere) }}/20</span></span>
                        @endif
                        @if ($title->avg_gameplay !== null)
                            <span>ゲーム性: <span class="text-slate-300">{{ round((float) $title->avg_gameplay) }}/20</span></span>
                        @endif
                        @if ($title->user_score_adjustment !== null)
                            <span>さじ加減: <span class="text-slate-300">{{ round((float) $title->user_score_adjustment) }}/20</span></span>
                        @endif
                    </div>
                @endif

                <div class="mt-2 text-xs text-slate-500">
                    {{ \Carbon\Carbon::parse($title->latest_review_at)->format('Y-m-d') }}
                </div>

                <div class="mt-2 text-xs text-slate-200">
                    <a href="{{ route('Game.TitleReviews', ['titleKey' => $title->key]) }}" data-hgn-scope="full">このタイトルのレビューを見る</a>
                </div>
            </div>

            @if ($loop->last)
            <div class="node-content basic" id="under-pager">
                @include('common.pager', ['pager' => $pager])
            </div>
            @endif
        </section>
    @endforeach

    @include('common.shortcut')
@endsection
