@extends('layout')

@section('title', 'ホラーゲームラインナップ')
@section('current-node-title', 'ラインナップ')

@section('nodes')
@isset($franchises)
    <section class="node {{ $franchises->isNotEmpty() ? 'tree-node' : '' }}">
        <div class="node-head">
            <h2 class="node-head-text">ラインナップ</h2>
            <span class="node-pt">●</span>
        </div>
        @if ($franchises->isNotEmpty())
        <div class="node-content tree">
            @foreach ($franchises as $franchise)
            <section class="node link-tree-node" id="franchise-{{ $franchise->key }}-link-node">
                <div class="node-head">
                    <a href="{{ route('Game.FranchiseDetail', ['franchiseKey' => $franchise->key]) }}" class="node-head-text">{{ $franchise->name }} フランチャイズ</a>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content tree">
                    @foreach ($franchise->searchSeries ?? [] as $series)
                    <section class="node tree-node">
                        <div class="node-head">
                            <h3 class="node-head-text text-muted">{{ $series->name }} シリーズ</h3>
                            <span class="node-pt">●</span>
                        </div>
                        <div class="node-content tree">
                            @foreach ($series->searchTitles ?? [] as $title)
                            <section class="node link-node" id="{{ $title->key }}-link-node">
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
                    <section class="node link-node" id="{{ $title->key }}-link-node">
                        <div class="node-head">
                            <a href="{{ route('Game.TitleDetail', ['titleKey' => $title->key]) }}" class="node-head-text">{{ $title->name }}</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                    @endforeach
                </div>
            </section>
            @endforeach
        </div>
        @else
        <div class="node-content basic">
            表示するフランチャイズがありません。
        </div>
        @endif
    </section>
@endisset

<section class="node tree-node">
    <div class="node-head">
        <h2 class="node-head-text">近道</h2>
        <span class="node-pt">●</span>
    </div>
    <div class="node-content tree">
        <section class="node link-node">
            <div class="node-head">
                <a href="{{ route('Root') }}" class="node-head-text">ルート</a>
                <span class="node-pt main-node-pt">●</span>
            </div>
        </section>
    </div>

    @include('common.shortcut_mynode')
</section>
@endsection
