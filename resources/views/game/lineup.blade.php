@extends('layout')

@section('title', 'ホラーゲームラインナップ')
@section('current-node-title', 'ラインナップ')

@section('nodes')
@isset($franchises)
    <section class="node {{ $franchises->isNotEmpty() ? 'tree-node' : '' }}" id="lineup-node">
        <div class="node-head">
            <h2 class="node-head-text">ラインナップ</h2>
            <span class="node-pt">●</span>
        </div>
        @if ($franchises->isNotEmpty())
        <div class="node-content tree">
            @include('game.lineup_nodes', ['franchises' => $franchises])
        </div>
        @endif
        @if (isset($totalPages) && $totalPages > 1)
        <div class="node-content basic">
            @for ($p = 1; $p <= $totalPages; $p++)
                @if ($p == $page)
                    <span class="me-2">{{ $p }}</span>
                @else
                    <a href="{{ route('Game.Lineup', ['page' => $p]) }}" rel="internal-node" class="me-2">{{ $p }}</a>
                @endif
            @endfor
        </div>
        @endif
        @if ($franchises->isEmpty())
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
