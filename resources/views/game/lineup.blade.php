@extends('layout')

@section('title', 'ホラーゲームラインナップ')
@section('current-node-title', 'ラインナップ')

@section('nodes')
@isset($franchises)

    @if ($franchises->isNotEmpty())
        @foreach ($franchises as $franchise)
        <section class="node tree-node" id="franchise-{{ $franchise->key }}-link-node">
            <div class="node-head">
                <a href="{{ route('Game.FranchiseDetail', ['franchiseKey' => $franchise->key]) }}" class="node-head-text">{{ $franchise->name }} フランチャイズ</a>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content tree">
                @foreach ($franchise->searchSeries ?? [] as $series)
                <section class="node tree-node">
                    <div class="node-head">
                        <h3 class="node-head-text">{{ $series->name }} シリーズ</h3>
                        <span class="node-pt">●</span>
                    </div>
                    <div class="node-content tree">
                        @foreach ($series->searchTitles ?? [] as $title)
                        <section class="node basic" id="{{ $title->key }}-link-node">
                            <div class="node-head">
                                <a href="{{ route('Game.TitleDetail', ['titleKey' => $title->key]) }}" class="node-head-text">{{ $title->name }}</a>
                                <span class="node-pt">●</span>
                            </div>
                        </section>
                        @endforeach
                    </div>
                </section>
                @endforeach
                @foreach ($franchise->searchTitles ?? [] as $title)
                <section class="node basic" id="{{ $title->key }}-link-node">
                    <div class="node-head">
                        <a href="{{ route('Game.TitleDetail', ['titleKey' => $title->key]) }}" class="node-head-text">{{ $title->name }}</a>
                        <span class="node-pt">●</span>
                    </div>
                </section>
                @endforeach
            </div>

            @if ($loop->last)
            @if (isset($totalPages) && $totalPages > 1)
            <div class="node-content basic mt-10">
                @for ($p = 1; $p <= $totalPages; $p++)
                    @if ($p == $page)
                        <span class="me-2">{{ $p }}</span>
                    @else
                        <a href="{{ route('Game.Lineup', ['page' => $p]) }}" data-hgn-scope="full" class="me-2">{{ $p }}</a>
                    @endif
                @endfor
            </div>
            @endif
            @endif
        </section>
        @endforeach
    @endif

    @if ($franchises->isEmpty())
    <div class="node-content basic">
        表示するフランチャイズがありません。
    </div>
    @endif
@endisset

<section class="node tree-node">
    <div class="node-head">
        <h2 class="node-head-text">近道</h2>
        <span class="node-pt">●</span>
    </div>
    <div class="node-content tree">
        <section class="node basic">
            <div class="node-head">
                <a href="{{ route('Root') }}" class="node-head-text">ルート</a>
                <span class="node-pt main-node-pt">●</span>
            </div>
        </section>
    </div>

    @include('common.shortcut_mynode')
</section>
@endsection
