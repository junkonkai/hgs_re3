@extends('layout')

@section('title', 'レビュー')
@section('current-node-title', 'レビュー')

@section('nodes')
    <section class="node" id="reviews-list-node">
        <div class="node-head">
            <h2 class="node-head-text">レビュー一覧</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
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
            <div class="mb-4 flex flex-wrap gap-1.5">
                @foreach ($sortOptions as $key => $label)
                    <a href="{{ route('Game.Reviews', $key !== 'newest' ? ['sort' => $key] : []) }}"
                       data-hgn-scope="full"
                       class="text-xs px-2.5 py-1 rounded border transition-colors
                              {{ $sort === $key
                                  ? 'bg-slate-200 text-slate-900 border-slate-200'
                                  : 'border-white/20 text-slate-400 hover:text-white hover:border-white/40' }}">
                        {{ $label }}
                    </a>
                @endforeach
            </div>

            @if ($titles->isEmpty())
                <p>レビューはまだないようだ。</p>
            @else
                @foreach ($titles as $title)
                    @php
                        $fearMeterEnum = $title->fear_meter !== null
                            ? \App\Enums\FearMeter::tryFrom((int) $title->fear_meter)
                            : null;
                    @endphp
                    <div class="py-4 border-b border-white/10 last:border-b-0">
                        <div class="flex flex-wrap items-baseline justify-between gap-x-3 gap-y-1 mb-2">
                            <a href="{{ route('Game.TitleReviews', ['titleKey' => $title->key]) }}"
                               data-hgn-scope="full"
                               class="font-semibold text-slate-100 hover:text-white">{{ $title->name }}</a>
                            <span class="text-xs text-slate-500 shrink-0">
                                {{ \Carbon\Carbon::parse($title->latest_review_at)->format('Y-m-d') }}
                            </span>
                        </div>

                        <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm">
                            {{-- 総合スコア --}}
                            <div class="flex items-baseline gap-1">
                                @if ($title->avg_total_score !== null)
                                    <span class="text-2xl font-bold text-slate-100 leading-none">{{ number_format((float) $title->avg_total_score, 1) }}</span>
                                    <span class="text-xs text-slate-500">/ 100</span>
                                @else
                                    <span class="text-slate-500">-</span>
                                @endif
                                <span class="text-xs text-slate-400 ml-1">{{ $title->review_count }}件</span>
                            </div>

                            {{-- 怖さメーター --}}
                            @if ($fearMeterEnum !== null)
                                <div class="text-xs text-slate-400">
                                    怖さ:
                                    <span class="text-slate-200">{{ number_format((float) $title->fear_meter_avg, 1) }}/4</span>
                                    <span class="text-slate-500">（{{ $fearMeterEnum->text() }}）</span>
                                </div>
                            @endif
                        </div>

                        {{-- 各スコア --}}
                        @if ($title->avg_story !== null || $title->avg_atmosphere !== null || $title->avg_gameplay !== null)
                            <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-slate-400">
                                @if ($title->avg_story !== null)
                                    <span>ストーリー: <span class="text-slate-300">{{ number_format((float) $title->avg_story, 1) }}/4</span></span>
                                @endif
                                @if ($title->avg_atmosphere !== null)
                                    <span>雰囲気: <span class="text-slate-300">{{ number_format((float) $title->avg_atmosphere, 1) }}/4</span></span>
                                @endif
                                @if ($title->avg_gameplay !== null)
                                    <span>ゲーム性: <span class="text-slate-300">{{ number_format((float) $title->avg_gameplay, 1) }}/4</span></span>
                                @endif
                            </div>
                        @endif

                        <div class="mt-2 text-xs">
                            <a href="{{ route('Game.TitleReviews', ['titleKey' => $title->key]) }}"
                               data-hgn-scope="full">レビューを読む →</a>
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
