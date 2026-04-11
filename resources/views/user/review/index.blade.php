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
    @if ($reviews->isNotEmpty())
        <section class="node" id="review-list-node">
            <div class="node-head">
                <h2 class="node-head-text">レビュー一覧</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                <table class="border border-gray-500 border-collapse w-full">
                    @foreach ($reviews as $review)
                        <tr>
                            <td class="border border-gray-500 px-3 py-2">
                                <a href="{{ route('Game.TitleDetail', ['titleKey' => $review->gameTitle->key]) }}" data-hgn-scope="full">{{ $review->gameTitle->name }}</a>
                                @if (in_array($review->game_title_id, $draftTitleIds))
                                    <span class="ml-2 text-xs text-yellow-400">下書きあり</span>
                                @endif
                            </td>
                            <td class="border border-gray-500 px-3 py-2 text-sm text-slate-300">
                                {{ $review->play_status?->text() ?? '' }}
                            </td>
                            <td class="border border-gray-500 px-3 py-2 text-sm">
                                @if ($review->total_score !== null)
                                    <span class="font-semibold">{{ $review->total_score }}</span><span class="text-slate-400 text-xs"> / 100</span>
                                @else
                                    <span class="text-slate-500">スコアなし</span>
                                @endif
                            </td>
                            <td class="border border-gray-500 px-3 py-2">
                                <a href="{{ route('User.Review.Form', ['titleKey' => $review->gameTitle->key]) }}" data-hgn-scope="full"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </table>
                @include('common.pager', ['pager' => $pager])
            </div>
        </section>
    @endif

    @include('common.shortcut')
@endsection
