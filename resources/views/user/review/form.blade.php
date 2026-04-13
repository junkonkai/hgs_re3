@extends('layout')

@section('title', 'レビュー投稿')
@section('current-node-title', 'レビュー投稿')

@section('nodes')
    <section class="node" id="review-form-node">
        <div class="node-head">
            <h2 class="node-head-text">{{ $title->name }} のレビュー</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            @if (session('success'))
                <div class="alert alert-success mt-3">
                    {!! nl2br(e(session('success'))) !!}
                </div>
            @endif

            @if (session('warning'))
                <div class="alert alert-warning mt-3">
                    {!! nl2br(e(session('warning'))) !!}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger mt-3">
                    <ul class="mb-0 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                // 怖さメーター
                $fearMeterCases = \App\Enums\FearMeter::cases();
                $fearMeterValues = array_map(fn ($c) => $c->value, $fearMeterCases);
                $fearMeterMin = min($fearMeterValues);
                $fearMeterMax = max($fearMeterValues);
                $fearMeterRange = max(1, $fearMeterMax - $fearMeterMin);
                $fearMeterTexts = [];
                foreach ($fearMeterCases as $c) {
                    $fearMeterTexts[$c->value] = $c->text();
                }
                $fearMeterOld = old('fear_meter');
                if (is_numeric($fearMeterOld)) {
                    $fearMeterInitial = (int) $fearMeterOld;
                } elseif (isset($fearMeter)) {
                    $fearMeterInitial = $fearMeter->fear_meter->value;
                } else {
                    $fearMeterInitial = 2;
                }
                $fearMeterInitial = max($fearMeterMin, min($fearMeterMax, $fearMeterInitial));
                $fearMeterInitialPercent = (($fearMeterInitial - $fearMeterMin) / $fearMeterRange) * 100;

                // 初期値（優先順: old() > 下書き > 公開済みレビュー > デフォルト）
                $initialPlayStatus = old('play_status') ?? $draft?->play_status?->value ?? $review?->play_status?->value ?? '';
                $initialPlayTime   = old('play_time')   ?? $draft?->play_time?->value   ?? $review?->play_time?->value   ?? '';
                $initialBody       = old('body')        ?? $draft?->body                ?? $review?->body                ?? '';

                $hasSpoilerOld = old('has_spoiler');
                if ($hasSpoilerOld !== null) {
                    $initialHasSpoiler = (bool) $hasSpoilerOld;
                } elseif ($draft !== null) {
                    $initialHasSpoiler = $draft->has_spoiler;
                } elseif ($review !== null) {
                    $initialHasSpoiler = $review->has_spoiler;
                } else {
                    $initialHasSpoiler = false;
                }

                $initialScoreStory      = old('score_story')      !== null ? old('score_story')      : ($draft?->score_story      ?? $review?->score_story      ?? 2);
                $initialScoreAtmosphere = old('score_atmosphere')  !== null ? old('score_atmosphere')  : ($draft?->score_atmosphere ?? $review?->score_atmosphere ?? 2);
                $initialScoreGameplay   = old('score_gameplay')    !== null ? old('score_gameplay')    : ($draft?->score_gameplay   ?? $review?->score_gameplay   ?? 2);
                $initialAdjustment      = old('user_score_adjustment') !== null ? old('user_score_adjustment') : ($draft?->user_score_adjustment ?? $review?->user_score_adjustment ?? 0);

                $oldPackages = old('packages');
                if ($oldPackages !== null) {
                    $initialPackageIds = array_map('intval', (array) $oldPackages);
                } elseif ($draft !== null) {
                    $initialPackageIds = $draft->packages->pluck('game_package_id')->toArray();
                } elseif ($review !== null) {
                    $initialPackageIds = $review->packages->pluck('game_package_id')->toArray();
                } else {
                    $initialPackageIds = [];
                }

                $oldTags = old('horror_type_tags');
                if ($oldTags !== null) {
                    $initialTags = (array) $oldTags;
                } elseif ($draft !== null) {
                    $initialTags = $draft->horrorTypeTags->map(fn ($t) => $t->tag->value)->toArray();
                } elseif ($review !== null) {
                    $initialTags = $review->horrorTypeTags->map(fn ($t) => $t->tag->value)->toArray();
                } else {
                    $initialTags = [];
                }

                $scoreOptions = ['0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4'];
            @endphp

            @if ($draft !== null)
                <div class="alert alert-warning mt-3 mb-4">
                    下書きがあります。公開するには「公開する」ボタンを押してください。
                </div>
            @endif

            <form
                action="{{ route('User.Review.Publish') }}"
                method="POST"
                class="js-review-form"
            >
                @csrf
                <input type="hidden" name="title_key" value="{{ $title->key }}">

                {{-- 怖さメーター --}}
                <div class="form-group mb-5">
                    <label class="mb-2 block font-semibold">怖さメーター <span class="text-red-400 text-xs">必須</span></label>
                    <input type="hidden" class="js-fear-meter-value" name="fear_meter" value="{{ $fearMeterInitial }}" required>
                    <div
                        class="js-fear-meter-input"
                        data-fear-meter-min="{{ $fearMeterMin }}"
                        data-fear-meter-max="{{ $fearMeterMax }}"
                        data-fear-meter-texts='@json($fearMeterTexts)'
                    >
                        <div class="flex flex-nowrap items-center gap-3">
                            <button
                                type="button"
                                class="btn btn-secondary btn-sm js-fear-meter-decrease shrink-0"
                                aria-label="怖さメーターを下げる"
                            ><span class="text-lg leading-none">-</span></button>
                            <div class="flex-1 min-w-0 max-w-xs">
                                <div class="h-3 overflow-hidden rounded-full bg-slate-700/60">
                                    <div
                                        class="h-full bg-gradient-to-r from-slate-800 via-sky-600 to-indigo-500 transition-all duration-200 js-fear-meter-bar-fill"
                                        style="width: {{ $fearMeterInitialPercent }}%;"
                                    ></div>
                                </div>
                                <div class="mt-2 text-center text-sm text-slate-200" aria-live="polite">
                                    <span class="font-semibold js-fear-meter-value-label">{{ $fearMeterInitial }}</span>
                                    :
                                    <span class="js-fear-meter-text">{{ $fearMeterTexts[$fearMeterInitial] ?? '' }}</span>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="btn btn-secondary btn-sm js-fear-meter-increase shrink-0"
                                aria-label="怖さメーターを上げる"
                            ><span class="text-lg leading-none">+</span></button>
                        </div>
                    </div>
                </div>

                {{-- プレイ状況 --}}
                <div class="form-group mb-4">
                    <label class="mb-2 block font-semibold">プレイ状況 <span class="text-red-400 text-xs">必須</span></label>
                    <div class="flex flex-wrap gap-4">
                        @foreach (\App\Enums\PlayStatus::cases() as $case)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    name="play_status"
                                    value="{{ $case->value }}"
                                    {{ $initialPlayStatus === $case->value ? 'checked' : '' }}
                                >
                                {{ $case->text() }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- プレイ時間 --}}
                <div class="form-group mb-4">
                    <label for="play_time" class="mb-2 block font-semibold">プレイ時間（任意）</label>
                    <select id="play_time" name="play_time" class="form-control" style="width: auto;">
                        <option value="">選択しない</option>
                        @foreach (\App\Enums\PlayTime::cases() as $case)
                            <option value="{{ $case->value }}" {{ $initialPlayTime === $case->value ? 'selected' : '' }}>
                                {{ $case->text() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- プレイ環境 --}}
                @if ($packages->isNotEmpty())
                    <div class="form-group mb-4">
                        <label class="mb-2 block font-semibold">プレイ環境（任意・複数選択可）</label>
                        <div class="flex flex-wrap gap-3">
                            @foreach ($packages as $package)
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="checkbox"
                                        name="packages[]"
                                        value="{{ $package->id }}"
                                        {{ in_array($package->id, $initialPackageIds) ? 'checked' : '' }}
                                    >
                                    {{ $package->getNameWithPlatform() }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- スコア入力 --}}
                <div class="form-group mb-4">
                    <label class="mb-2 block font-semibold">評価（各0〜4）</label>
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <span class="w-28 text-sm text-slate-300 shrink-0">ストーリー</span>
                            <select name="score_story" class="form-control js-review-score-select" style="width: auto;">
                                @foreach ($scoreOptions as $val => $label)
                                    <option value="{{ $val }}" {{ (string) $initialScoreStory === (string) $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-28 text-sm text-slate-300 shrink-0">雰囲気・演出</span>
                            <select name="score_atmosphere" class="form-control js-review-score-select" style="width: auto;">
                                @foreach ($scoreOptions as $val => $label)
                                    <option value="{{ $val }}" {{ (string) $initialScoreAtmosphere === (string) $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="w-28 text-sm text-slate-300 shrink-0">ゲーム性</span>
                            <select name="score_gameplay" class="form-control js-review-score-select" style="width: auto;">
                                @foreach ($scoreOptions as $val => $label)
                                    <option value="{{ $val }}" {{ (string) $initialScoreGameplay === (string) $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- スコア調整 --}}
                <div class="form-group mb-4">
                    <label for="user_score_adjustment" class="mb-2 block font-semibold">スコア調整（任意・−20〜+20）</label>
                    <input
                        type="number"
                        id="user_score_adjustment"
                        name="user_score_adjustment"
                        class="form-control js-review-adjustment"
                        min="-20"
                        max="20"
                        value="{{ $initialAdjustment }}"
                        style="width: 100px;"
                    >
                </div>

                {{-- 総合スコアプレビュー --}}
                <div class="mb-2 p-3 bg-slate-800/60 rounded-lg inline-flex items-center gap-3">
                    <span class="text-sm text-slate-300">総合スコア：</span>
                    <span class="text-2xl font-bold text-sky-300 js-review-total-score">0</span>
                    <span class="text-sm text-slate-400">/ 100</span>
                </div>
                <p class="mb-5 text-xs text-slate-500">怖さメーター×10 ＋ ストーリー×5 ＋ 雰囲気・演出×5 ＋ ゲーム性×5 ＋ スコア調整（0〜100の範囲）</p>

                {{-- ホラー種別タグ --}}
                <div class="form-group mb-4">
                    <label class="mb-2 block font-semibold">ホラー種別タグ（任意・複数選択可）</label>
                    <div class="flex flex-wrap gap-3">
                        @foreach (\App\Enums\HorrorTypeTag::cases() as $case)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="checkbox"
                                    name="horror_type_tags[]"
                                    value="{{ $case->value }}"
                                    {{ in_array($case->value, $initialTags) ? 'checked' : '' }}
                                >
                                {{ $case->text() }}
                            </label>
                        @endforeach
                    </div>
                </div>

                {{-- 本文 --}}
                <div class="form-group mb-4">
                    <label for="body" class="mb-2 block font-semibold">レビュー本文 <span class="text-gray-400 text-xs">2000文字まで</span></label>
                    <textarea
                        id="body"
                        name="body"
                        maxlength="2000"
                        rows="10"
                        style="width: 100%;"
                        placeholder="このゲームのレビューを書いてください"
                    >{{ $initialBody }}</textarea>
                </div>

                {{-- ネタバレフラグ --}}
                <div class="form-group mb-5">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="has_spoiler"
                            value="1"
                            {{ $initialHasSpoiler ? 'checked' : '' }}
                        >
                        ネタバレを含む
                    </label>
                </div>

                {{-- ボタン --}}
                <p class="text-xs text-slate-400 mb-3">
                    レビューのURLにはあなたのユーザーID（<code class="text-slate-300">{{ Auth::user()->show_id }}</code>）が含まれます。<br>
                    アカウント設定でユーザーIDを変更すると、このレビューのURLも変わります。
                </p>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="submit" class="btn btn-success">公開する</button>
                    <button
                        type="button"
                        class="btn btn-secondary js-review-draft-save"
                        data-draft-url="{{ route('User.Review.Draft.Save') }}"
                    >下書き保存</button>
                </div>
            </form>

            {{-- 下書き破棄 --}}
            @if ($draft !== null)
                <form action="{{ route('User.Review.Draft.Discard') }}" method="POST" class="mt-4">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="title_key" value="{{ $title->key }}">
                    <button
                        type="submit"
                        class="btn btn-warning btn-sm"
                        onclick="return confirm('下書きを破棄します。よろしいですか？')"
                    >下書きを破棄</button>
                </form>
            @endif

            {{-- レビュー削除 --}}
            @if ($review !== null)
                @php
                    $fearMeterExists = $fearMeter !== null;
                    $deleteConfirmMessage = $fearMeterExists
                        ? "レビューを削除します。\n怖さメーターも一緒に削除しますか？\n（OKで両方削除、キャンセルでレビューのみ削除）"
                        : 'レビューを削除します。よろしいですか？';
                @endphp
                <form action="{{ route('User.Review.Destroy') }}" method="POST" class="mt-4" id="review-delete-form">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="title_key" value="{{ $title->key }}">
                    <input type="hidden" name="also_delete_fear_meter" value="0" id="also-delete-fear-meter-input">
                    @if ($fearMeterExists)
                        <button
                            type="button"
                            class="btn btn-danger btn-sm"
                            onclick="
                                var result = confirm('レビューを削除します。よろしいですか？\n（怖さメーターも一緒に削除する場合は次の確認でOKを押してください）');
                                if (!result) return;
                                var alsoFear = confirm('怖さメーターも一緒に削除しますか？\nOK: 両方削除　　キャンセル: レビューのみ削除');
                                document.getElementById('also-delete-fear-meter-input').value = alsoFear ? '1' : '0';
                                document.getElementById('review-delete-form').submit();
                            "
                        >レビューを削除</button>
                    @else
                        <button
                            type="submit"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('レビューを削除します。よろしいですか？')"
                        >レビューを削除</button>
                    @endif
                </form>
            @endif
        </div>
    </section>
    @include('common.shortcut', ['shortcutRoute' => $shortcutRoute, 'myNodeShortcutRoute' => $myNodeShortcutRoute])
@endsection
