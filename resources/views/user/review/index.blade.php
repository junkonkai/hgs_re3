@extends('layout')

@section('title', 'マイレビュー')
@section('current-node-title', 'マイレビュー')

@section('current-node-content')
@if (session('success'))
    <div class="alert alert-success mt-3 relative pr-10">
        <button type="button" class="absolute top-0 right-0 p-2 border-0 bg-transparent cursor-pointer" style="line-height: 1;" onclick="this.closest('.alert').style.display='none'" aria-label="閉じる"><i class="bi bi-x"></i></button>
        {!! nl2br(e(session('success'))) !!}
    </div>
@endif
@if ($reviews->isEmpty())
    <p>
        まだレビューを投稿していないようだ。<br>
        <a href="{{ route('Game.Lineup') }}" data-hgn-scope="full">ラインナップ</a>からタイトルを探して、レビューを書いてみよう。
    </p>
@endif
@endsection

@section('nodes')
    @foreach ($reviews as $review)
        @php $fearMeter = $fearMeters[$review->game_title_id] ?? null; @endphp
        <section class="node" id="review-{{ $review->game_title_id }}-node">
            <div class="node-head">
                <span class="node-head-text relative">
                    @if (in_array($review->game_title_id, $draftTitleIds))
                        <span class="absolute -top-1 -left-1 translate-y-[-100%] text-[10px] leading-none px-1 py-0.5 rounded bg-yellow-600 text-white">下書き</span>
                    @endif
                    <a href="{{ route('Game.TitleReview', ['titleKey' => $review->gameTitle->key, 'reviewKey' => $review->key]) }}" data-hgn-scope="full">{{ $review->gameTitle->name }}</a>
                </span>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
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
                    <div class="mt-2 text-xs text-amber-400">【ネタバレあり】</div>
                @endif
                @if ($review->body)
                    <div class="mt-1 text-sm leading-relaxed text-slate-100">{!! nl2br(e($review->body)) !!}</div>
                @endif

                {{-- リンク --}}
                <div class="mt-2 flex gap-4 text-xs">
                    <a href="{{ route('Game.TitleReview', ['titleKey' => $review->gameTitle->key, 'reviewKey' => $review->key]) }}" data-hgn-scope="full"><i class="bi bi-file-text"></i> 表示</a>
                    <a href="{{ route('User.Review.Form', ['titleKey' => $review->gameTitle->key]) }}" data-hgn-scope="full"><i class="bi bi-pencil"></i> 編集</a>
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
