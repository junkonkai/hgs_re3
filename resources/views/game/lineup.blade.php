@extends('layout')

@section('title', 'ホラーゲームラインナップ')
@section('current-node-title', 'ラインナップ')
@section('current-node-content')
<form id="lineup-search-form" method="GET" action="{{ route('Game.Lineup') }}" data-child-only="1">
    <div>
        {{-- タイトル検索 --}}
        <div class="form-row">
            <label for="search-input" class="lineup-search-form__label">タイトル</label>
            <input type="text" id="search-input" name="text" value="{{ $text ?? '' }}" placeholder="タイトル名" class="input input-default">
        </div>

        <div class="form-row lineup-search-form__toggle-row">
            <span class="lineup-search-form__label">詳細検索</span>
            <button type="button" id="advanced-search-toggle" class="lineup-search-form__label">
                <span id="advanced-search-label">開く</span>
                <span id="advanced-search-icon">▽</span>
            </button>
        </div>

        <div id="advanced-search-wrapper" class="advanced-search-wrapper">
            <div class="advanced-search-wrapper__inner">
            {{-- プラットフォーム --}}
            <div class="form-row">
                <label for="lineup-platform-id" class="lineup-search-form__label">プラットフォーム</label>
                <select id="lineup-platform-id" name="platform_id" class="input input-default">
                    <option value="0">すべて</option>
                    @foreach ($platforms ?? [] as $platform)
                    <option value="{{ $platform->id }}" @selected(($platformId ?? null) == $platform->id)>
                        {{ $platform->name }}{{ $platform->acronym ? '（' . $platform->acronym . '）' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- メーカー --}}
            <div class="form-row lineup-search-form__maker-row">
                <label for="maker-name-input" class="lineup-search-form__label">メーカー</label>
                <div class="lineup-search-form__maker-input-wrap">
                    <input
                        type="text"
                        id="maker-name-input"
                        name="maker_name"
                        value="{{ $makerName ?? '' }}"
                        placeholder="入力して選択"
                        class="input input-default"
                        autocomplete="off"
                    >
                    <button type="button" id="maker-clear-btn" class="btn btn-default btn-sm" style="{{ empty($makerName ?? '') ? 'display:none;' : '' }}">✕</button>
                </div>
                <input type="hidden" id="maker-id-input" name="maker_id" value="{{ $makerId ?? '' }}">
                <div id="maker-suggestions" class="maker-suggest-list"></div>
            </div>

            {{-- 怖さメーター --}}
            <div class="form-row">
                <span class="lineup-search-form__label">怖さメーター</span>
                <div class="lineup-search-form__range-row">
                    <select name="fear_meter_min" id="fear-meter-min" class="input input-default">
                        <option value="">下限なし</option>
                        @for ($i = 0; $i <= 4; $i++)
                        <option value="{{ $i }}" @selected(($fearMeterMin ?? null) !== null && $fearMeterMin == $i)>{{ $i }}</option>
                        @endfor
                    </select>
                    <span class="lineup-search-form__label">〜</span>
                    <select name="fear_meter_max" id="fear-meter-max" class="input input-default">
                        <option value="">上限なし</option>
                        @for ($i = 0; $i <= 4; $i++)
                        <option value="{{ $i }}" @selected(($fearMeterMax ?? null) !== null && $fearMeterMax == $i)>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
            </div>

            {{-- 発売年 --}}
            <div class="form-row">
                <span class="lineup-search-form__label">発売年</span>
                <div class="lineup-search-form__range-row">
                    <input
                        type="number"
                        name="release_from"
                        value="{{ $releaseFrom ?? '' }}"
                        placeholder="開始年"
                        min="1980"
                        max="{{ date('Y') }}"
                        class="input input-default"
                    >
                    <span class="lineup-search-form__label">〜</span>
                    <input
                        type="number"
                        name="release_to"
                        value="{{ $releaseTo ?? '' }}"
                        placeholder="終了年"
                        min="1980"
                        max="{{ date('Y') }}"
                        class="input input-default"
                    >
                </div>
            </div>
            </div>
        </div>

        <div class="form-row lineup-search-form__buttons-row">
            <button type="submit" class="btn btn-default btn-sm">検索</button>
            <button type="button" id="search-reset-btn" class="btn btn-default btn-sm">リセット</button>
        </div>
    </div>
</form>
@endsection

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
    @isset($pager)
    <div class="node-content basic">
        @include('common.pager', ['pager' => $pager])
    </div>
    @endisset
    @endif
</section>
@endforeach
@endif

@if ($franchises->isEmpty())
<div class="node-content basic">
この検索条件では、何も見つからないようだ。
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
