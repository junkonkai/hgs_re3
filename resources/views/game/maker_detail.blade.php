@extends('layout')

@section('title', $maker->name)
@section('current-node-title', $maker->name)

@section('current-node-content')
    <blockquote class="description">
        {!! nl2br($maker->description); !!}
        @if ($maker->description_source !== null)
            <footer>
                — <cite>{!! $maker->description_source !!}</cite>
            </footer>
        @endif
    </blockquote>
@endsection


@section('nodes')


    @if ($titles->count() > 0)
        <section class="node tree-node" id="title-tree-node">
            <div class="node-head">
                <h2 class="node-head-text">タイトル</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content tree">
                @foreach ($titles as $title)
                    <section class="node basic" id="{{ $title->key }}-link-node">
                        <div class="node-head">
                            <a href="{{ route('Game.TitleDetail', ['titleKey' => $title->key]) }}" class="node-head-text">{{ $title->name }}</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                @endforeach
            </div>
        </section>
    @endif

    <section class="node tree-node" id="footer-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node tree-node" id="back-to-platforms-node">
                <div class="node-head">
                    <a href="{{ route('Game.Maker') }}" class="node-head-text">ゲームメーカー</a>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content tree">
                    <section class="node basic" id="back-to-root-node">
                        <div class="node-head">
                            <a href="{{ route('Root') }}" class="node-head-text">ルート</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                </div>
            </section>
        
            @include('common.shortcut_mynode')
        
            @if (is_admin_user())
            <section class="node basic">
                <div class="node-head">
                    <a href="{{ route('Admin.Game.Maker.Detail', $maker) }}" class="node-head-text" rel="external">管理</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            @endif
        </div>
    </section>
@endsection
