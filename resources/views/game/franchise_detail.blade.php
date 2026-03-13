@extends('layout')

@section('title', $franchise->name . 'フランチャイズ')
@section('current-node-title', $franchise->name . 'フランチャイズ')

@section('current-node-content')
    {!! nl2br($franchise->description) !!}
@endsection

@section('nodes')
    <section class="node tree-node" id="title-lineup-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">タイトルラインナップ</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            @foreach ($franchise->series->sortBy('first_release_int') as $series)
                <section class="node tree-node" id="{{ $series->key }}-tree-node">
                    <div class="node-head">
                        <h3 class="node-head-text">{{ $series->name }}シリーズ</h3>
                        <span class="node-pt">●</span>
                    </div>
                    <div class="node-content tree">
                        @foreach ($series->titles->sortBy('first_release_int') as $title)
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

            @if ($franchise->titles->count() > 0)
                @foreach ($franchise->titles->sortBy('first_release_int') as $title)
                <section class="node basic" id="{{ $title->key }}-link-node">
                    <div class="node-head">
                        <a href="{{ route('Game.TitleDetail', ['titleKey' => $title->key]) }}" class="node-head-text">{{ $title->name }}</a>
                        <span class="node-pt">●</span>
                    </div>
                </section>
                @endforeach
            @endif
        </div>
    </section>


    @if ($franchise->mediaMixGroups->isNotEmpty() || $franchise->mediaMixes->isNotEmpty())

    <section class="node tree-node" id="media-mix-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">メディアミックス</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            @foreach ($franchise->mediaMixGroups->sortBy('sort_order') as $mediaMixGroup)
            <section class="node tree-node" id="media-mix-tree-node">
                <div class="node-head">
                    <h3 class="node-head-text">{{ $mediaMixGroup->name }}</h3>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content tree">
                    @if ($franchise->mediaMixGroups->isNotEmpty())
                        @foreach ($mediaMixGroup->mediaMixes->sortBy('sort_order') as $mediaMix)
                        <section class="node basic" id="{{ $mediaMix->key }}-link-node">
                            <div class="node-head">
                                <a href="{{ route('Game.MediaMixDetail', ['mediaMixKey' => $mediaMix->key]) }}" class="node-head-text">{{ $mediaMix->name }}</a>
                                <span class="node-pt">●</span>
                            </div>
                        </section>
                        @endforeach
                    @endif
                </div>
            </section>
            @endforeach

            @if ($franchise->mediaMixes->isNotEmpty())
                @foreach ($franchise->mediaMixes as $mediaMix)
                <section class="node basic" id="{{ $mediaMix->key }}-link-node">
                    <div class="node-head">
                        <a href="{{ route('Game.MediaMixDetail', ['mediaMixKey' => $mediaMix->key]) }}" class="node-head-text">{{ $mediaMix->name }}</a>
                        <span class="node-pt">●</span>
                    </div>
                </section>
                @endforeach
            @endif
        </div>
    </section>

    @endif

    <section class="node tree-node" id="footer-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node tree-node" id="back-to-franchises-node">
                <div class="node-head">
                    <a href="{{ route('Game.Franchises') }}" class="node-head-text">フランチャイズ</a>
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
                    <a href="{{ route('Admin.Game.Franchise.Detail', $franchise) }}" class="node-head-text" rel="external">管理</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            @endif
        </div>
    </section>
@endsection
