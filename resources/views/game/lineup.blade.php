@extends('layout')

@section('title', 'ホラーゲームラインナップ')
@section('current-node-title', 'ラインナップ')

@section('current-node-content')
<form id="search-form" method="GET" action="{{ route('Game.Lineup') }}" data-child-only="1">
    <div class="flex flex-col gap-3" style="max-width: 500px;">
        {{-- タイトル検索 --}}
        <div class="form-row">
            <input type="text" id="search-input" name="text" value="{{ $text ?? '' }}" placeholder="タイトル名" class="input input-default">
        </div>

        <div class="form-row flex items-center justify-between">
            <span class="text-xs text-muted">詳細検索</span>
            <button
                type="button"
                id="advanced-search-toggle"
                class="text-xs text-muted underline"
            >
                <span id="advanced-search-label">開く</span>
                <span id="advanced-search-icon">▽</span>
            </button>
        </div>

        <div id="advanced-search-wrapper" class="advanced-search-wrapper flex flex-col gap-3">
            {{-- プラットフォーム --}}
            <div class="form-row">
                <select name="platform_id" class="input input-default" style="flex:1;">
                    <option value="0">プラットフォーム（すべて）</option>
                    @foreach ($platforms ?? [] as $platform)
                    <option value="{{ $platform->id }}" @selected(($platformId ?? null) == $platform->id)>
                        {{ $platform->name }}{{ $platform->acronym ? '（' . $platform->acronym . '）' : '' }}
                    </option>
                    @endforeach
                </select>
            </div>

            {{-- メーカー --}}
            <div class="form-row" style="position: relative;">
                <input
                    type="text"
                    id="maker-name-input"
                    name="maker_name"
                    value="{{ $makerName ?? '' }}"
                    placeholder="メーカー名（入力して選択）"
                    class="input input-default"
                    autocomplete="off"
                >
                <input type="hidden" id="maker-id-input" name="maker_id" value="{{ $makerId ?? '' }}">
                <button type="button" id="maker-clear-btn" class="btn btn-default btn-sm" style="{{ empty($makerName ?? '') ? 'display:none;' : '' }}">✕</button>
                <div id="maker-suggestions" class="maker-suggest-list" style="display:none;"></div>
            </div>

            {{-- 怖さメーター --}}
            <div class="form-row items-center gap-2">
                <span class="text-sm text-muted" style="white-space:nowrap;">怖さメーター</span>
                <select name="fear_meter_min" id="fear-meter-min" class="input input-default" style="flex:1;">
                    <option value="">下限なし</option>
                    @for ($i = 0; $i <= 4; $i++)
                    <option value="{{ $i }}" @selected(($fearMeterMin ?? null) !== null && $fearMeterMin == $i)>{{ $i }}</option>
                    @endfor
                </select>
                <span class="text-sm text-muted">〜</span>
                <select name="fear_meter_max" id="fear-meter-max" class="input input-default" style="flex:1;">
                    <option value="">上限なし</option>
                    @for ($i = 0; $i <= 4; $i++)
                    <option value="{{ $i }}" @selected(($fearMeterMax ?? null) !== null && $fearMeterMax == $i)>{{ $i }}</option>
                    @endfor
                </select>
            </div>

            {{-- 発売時期 --}}
            <div class="form-row items-center gap-2">
                <span class="text-sm text-muted" style="white-space:nowrap;">発売年</span>
                <input
                    type="number"
                    name="release_from"
                    value="{{ $releaseFrom ?? '' }}"
                    placeholder="開始年"
                    min="1980"
                    max="{{ date('Y') }}"
                    class="input input-default"
                    style="flex:1; min-width:0;"
                >
                <span class="text-sm text-muted">〜</span>
                <input
                    type="number"
                    name="release_to"
                    value="{{ $releaseTo ?? '' }}"
                    placeholder="終了年"
                    min="1980"
                    max="{{ date('Y') }}"
                    class="input input-default"
                    style="flex:1; min-width:0;"
                >
            </div>
        </div>

        <div class="form-row">
            <button type="submit" class="btn btn-default btn-sm">検索</button>
            <button type="button" id="search-reset-btn" class="btn btn-default btn-sm">リセット</button>
        </div>
    </div>
</form>

<style>
.advanced-search-wrapper {
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}
.advanced-search-wrapper.open {
    max-height: 1000px;
}
.maker-suggest-list {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background-color: #1a1a1a;
    border: 1px solid #616161;
    border-radius: 4px;
    z-index: 100;
    max-height: 200px;
    overflow-y: auto;
}
.maker-suggest-item {
    padding: 8px 10px;
    cursor: pointer;
    font-size: inherit;
}
.maker-suggest-item:hover {
    background-color: #2a2a2a;
}
</style>

<script>
(function () {
    const advancedToggle = document.getElementById('advanced-search-toggle');
    const advancedWrapper = document.getElementById('advanced-search-wrapper');
    const advancedLabel = document.getElementById('advanced-search-label');
    const advancedIcon = document.getElementById('advanced-search-icon');
    if (advancedToggle && advancedWrapper && advancedLabel && advancedIcon) {
        let isOpen = false;

        function updateAdvancedState(open) {
            isOpen = open;
            if (open) {
                advancedWrapper.classList.add('open');
                advancedLabel.textContent = '閉じる';
                advancedIcon.textContent = '△';
                // トランジション完了後にoverflowをvisibleにして<select>ドロップダウンが見切れないようにする
                advancedWrapper.addEventListener('transitionend', function () {
                    advancedWrapper.style.overflow = 'visible';
                }, { once: true });
            } else {
                // 閉じるアニメーション前にoverflowをhiddenに戻す
                advancedWrapper.style.overflow = 'hidden';
                advancedWrapper.classList.remove('open');
                advancedLabel.textContent = '開く';
                advancedIcon.textContent = '▽';
            }
        }

        const advancedDefaultOpen = {{ ($platformId ?? 0) || ($makerName ?? '') || ($fearMeterMin ?? '') || ($fearMeterMax ?? '') || ($releaseFrom ?? '') || ($releaseTo ?? '') ? 'true' : 'false' }};
        if (advancedDefaultOpen) {
            // GETパラメーターありの場合はアニメーションなしで即時展開
            advancedWrapper.style.transition = 'none';
            advancedWrapper.classList.add('open');
            advancedWrapper.style.overflow = 'visible';
            advancedLabel.textContent = '閉じる';
            advancedIcon.textContent = '△';
            isOpen = true;
            requestAnimationFrame(() => { advancedWrapper.style.transition = ''; });
        }

        advancedToggle.addEventListener('click', function () {
            updateAdvancedState(!isOpen);
        });
    }

    const nameInput = document.getElementById('maker-name-input');
    const idInput = document.getElementById('maker-id-input');
    const clearBtn = document.getElementById('maker-clear-btn');
    const suggestions = document.getElementById('maker-suggestions');
    let debounceTimer = null;

    nameInput.addEventListener('input', function () {
        idInput.value = '';
        clearBtn.style.display = 'none';
        clearTimeout(debounceTimer);
        const q = this.value.trim();
        if (q.length === 0) {
            suggestions.style.display = 'none';
            suggestions.innerHTML = '';
            return;
        }
        debounceTimer = setTimeout(() => fetchSuggestions(q), 250);
    });

    const makerSuggestUrl = '{{ route('api.game.maker.suggest') }}';

    function fetchSuggestions(q) {
        fetch(makerSuggestUrl + '?q=' + encodeURIComponent(q))
            .then(res => res.json())
            .then(data => {
                suggestions.innerHTML = '';
                if (!data.makers || data.makers.length === 0) {
                    suggestions.style.display = 'none';
                    return;
                }
                data.makers.forEach(maker => {
                    const item = document.createElement('div');
                    item.className = 'maker-suggest-item';
                    item.textContent = maker.name;
                    item.addEventListener('click', () => selectMaker(maker));
                    suggestions.appendChild(item);
                });
                suggestions.style.display = 'block';
            })
            .catch(() => { suggestions.style.display = 'none'; });
    }

    function selectMaker(maker) {
        nameInput.value = maker.name;
        idInput.value = maker.id;
        suggestions.style.display = 'none';
        clearBtn.style.display = '';
    }

    clearBtn.addEventListener('click', function () {
        nameInput.value = '';
        idInput.value = '';
        this.style.display = 'none';
    });

    const resetBtn = document.getElementById('search-reset-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            document.getElementById('search-input').value = '';
            document.querySelector('[name="platform_id"]').value = '0';
            nameInput.value = '';
            idInput.value = '';
            clearBtn.style.display = 'none';
            document.getElementById('fear-meter-min').value = '';
            document.getElementById('fear-meter-max').value = '';
            document.querySelector('[name="release_from"]').value = '';
            document.querySelector('[name="release_to"]').value = '';
        });
    }

    document.addEventListener('click', function (e) {
        if (!nameInput.contains(e.target) && !suggestions.contains(e.target)) {
            suggestions.style.display = 'none';
        }
    });
})();
</script>
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
                @include('common.pager', ['pager' => $pager])
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
