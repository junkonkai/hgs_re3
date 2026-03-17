@extends('layout')

@section('title', $platform->name)
@section('current-node-title', $platform->name)

@section('current-node-content')
    <blockquote class="description">
        {!! nl2br($platform->description); !!}
        @if ($platform->description_source !== null)
            <footer>
                — <cite>{!! $platform->description_source !!}</cite>
            </footer>
        @endif
    </blockquote>
@endsection


@section('nodes')

    @if ($platform->relatedProducts->count() > 0)
        <section class="node tree-node" id="hardware-tree-node">
            <div class="node-head">
                <h2 class="node-head-text">ハードウェア</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content tree">
                @foreach ($platform->relatedProducts as $rp)
                    <section class="node basic" id="{{ $rp->key }}-link-node">
                        <div class="node-head">
                            <h2 class="node-head-text">{{ $rp->name }}</h2>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                @endforeach
            </div>
        </section>
    @endif

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
                    <a href="{{ route('Game.Platform') }}" class="node-head-text">プラットフォーム</a>
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
                    <a href="{{ route('Admin.Game.Platform.Detail', $platform) }}" class="node-head-text" rel="external">管理</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            @endif
        </div>
    </section>
@endsection
