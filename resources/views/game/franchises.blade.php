@extends('layout')

@section('title', 'フランチャイズ')
@section('current-node-title', 'フランチャイズ')

@section('nodes')
    @foreach ($prefixes as $prefix => $words)
        @php $prefixFranchises = $franchisesByPrefix[$prefix] ?? []; @endphp
        <section class="node tree-node accordion" id="{{ $prefix }}-tree" data-accordion-group="acc1" data-accordion-type="auto-close">
            <div class="node-head">
                <button class="node-head-text" type="button" aria-expanded="false" aria-controls="acc1-a" id="acc-btn-a">{{ $words[0] }}</button>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content behind">
                @foreach ($prefixFranchises as $franchise)
                    @if ($loop->iteration > 3)
                        @break
                    @endif
                    <div class="behind-node">
                        <span class="node-pt">●</span><span>{{ $franchise->name }}</span>
                    </div>
                @endforeach
            </div>
            <div class="node-content tree" id="acc1-a">
                @foreach ($prefixFranchises as $franchise)
                <section class="node basic" id="search-node">
                    <div class="node-head">
                        <a href="{{ route('Game.FranchiseDetail', ['franchiseKey' => $franchise->key]) }}" class="node-head-text">
                            {{ $franchise->name }}
                            @if ($franchise->rating == \App\Enums\Rating::R18A)
                                &nbsp;🔞
                            @endif
                        </a>
                        <span class="node-pt">●</span>
                    </div>
                    @if ($isOver18 || $franchise->rating != \App\Enums\Rating::R18A)
                    <div class="node-content basic">
                        {!! $franchise->description !!}
                    </div>
                    @endif
                </section>
                @endforeach
            </div>
        </section>
    @endforeach

    <section class="node tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node basic">
                <div class="node-head">
                    <a href="{{ route('Root') }}" class="node-head-text">ルート</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
        
            @include('common.shortcut_mynode')
        
            @if (is_admin_user())
            <section class="node basic">
                <div class="node-head">
                    <a href="{{ route('Admin.Game.Franchise') }}" class="node-head-text" rel="external">管理</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            @endif
        </div>
    </section>
@endsection
