@extends('layout')

@section('title', 'お気に入りタイトル')
@section('current-node-title', 'お気に入りタイトル')

@section('current-node-content')

@if ($favoriteTitles->isEmpty())
    <p>お気に入りに登録されているタイトルはありません。</p>
@else
    <p>お気に入りに登録されているタイトル一覧です。</p>
@endif

@endsection

@section('nodes')
    @if ($favoriteTitles->isNotEmpty())
        <section class="node tree-node" id="favorite-titles-tree-node">
            <div class="node-head">
                <h2 class="node-head-text">お気に入りタイトル</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content tree">
                @foreach ($favoriteTitles as $title)
                <section class="node basic" id="favorite-title-{{ $title->id }}-link-node">
                    <div class="node-head">
                        <a href="{{ route('Game.TitleDetail', ['titleKey' => $title->key]) }}" class="node-head-text">{{ $title->name }}</a>
                        <span class="node-pt">●</span>
                    </div>
                </section>
                @endforeach
            </div>
        </section>
    @endif

    @include('common.shortcut')
@endsection

