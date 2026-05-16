@extends('layout')

@section('title', $title->name . ' レビュー投稿')
@section('current-node-title', $title->name . ' レビュー投稿')

@section('current-node-content')
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
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endsection

@section('nodes')
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
        } elseif (isset($fearMeterDraft)) {
            $fearMeterInitial = $fearMeterDraft->fear_meter;
        } elseif (isset($fearMeter)) {
            $fearMeterInitial = $fearMeter->fear_meter->value;
        } else {
            $fearMeterInitial = 2;
        }
        $initialFearMeterComment = old('fear_meter_comment') ?? $fearMeterDraft?->comment ?? $fearMeterLogComment ?? '';
        $fearMeterInitial = max($fearMeterMin, min($fearMeterMax, $fearMeterInitial));
        $fearMeterInitialPercent = (($fearMeterInitial - $fearMeterMin) / $fearMeterRange) * 100;

        // 初期値（優先順: old() > 下書き > 公開済みレビュー > デフォルト）
        $initialPlayStatus = old('play_status') ?? $draft?->play_status?->value ?? $review?->play_status?->value ?? '';
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

        $initialScoreStory      = old('score_story')      !== null ? old('score_story')      : ($draft?->score_story      ?? $review?->score_story      ?? 10);
        $initialScoreAtmosphere = old('score_atmosphere')  !== null ? old('score_atmosphere')  : ($draft?->score_atmosphere ?? $review?->score_atmosphere ?? 10);
        $initialScoreGameplay   = old('score_gameplay')    !== null ? old('score_gameplay')    : ($draft?->score_gameplay   ?? $review?->score_gameplay   ?? 10);
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

        $scoreOptions = ['0' => '0', '1' => '1', '2' => '2', '3' => '3', '4' => '4'];
        $fearMeterExists = isset($fearMeter) && $fearMeter !== null;
    @endphp

    <section class="node tree-node" id="review-form-node">
        <div class="node-head">
            <h2 class="node-head-text">レビュー</h2>
            <span class="node-pt">●</span>
        </div>

        {{-- 下書き警告・下書き破棄 --}}
        @if ($draft !== null)
            <div class="node-content basic">
                <div class="alert alert-warning mb-4">
                    下書きがあります。公開するには「公開する」ボタンを押してください。
                </div>

                <div class="flex flex-wrap gap-3">
                    <form action="{{ route('User.Review.Draft.Discard') }}" method="POST">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="title_key" value="{{ $title->key }}">
                        <button
                            type="submit"
                            class="btn btn-warning btn-sm"
                            onclick="return confirm('下書きを破棄します。よろしいですか？')"
                        >下書きを破棄</button>
                    </form>
                </div>
            </div>
        @endif

        {{-- メインフォーム（node-content tree を form 要素として使う） --}}
        <form
            class="node-content tree js-review-form"
            action="{{ route('User.Review.Publish') }}"
            method="POST"
        >
            @csrf
            <input type="hidden" name="title_key" value="{{ $title->key }}">

            {{-- スコア評価 --}}
            <section class="node" id="review-score-node">
                <div class="node-head">
                    <h3 class="node-head-text">スコア評価 <span class="text-red-400 text-xs font-normal">必須</span></h3>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content basic">

                    {{-- 怖さメーター --}}
                    <div class="mb-5">
                        <label class="mb-2 block font-semibold">怖さメーター（0〜40）</label>
                        <input type="hidden" class="js-fear-meter-value" name="fear_meter" value="{{ $fearMeterInitial }}" required>
                        <div
                            class="js-fear-meter-input"
                            data-fear-meter-min="{{ $fearMeterMin }}"
                            data-fear-meter-max="{{ $fearMeterMax }}"
                            data-fear-meter-texts='@json($fearMeterTexts)'
                        >
                            <div class="inline-flex flex-col">
                                <div class="flex flex-nowrap items-center gap-3">
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm js-fear-meter-decrease shrink-0 inline-flex items-center justify-center"
                                        aria-label="怖さメーターを下げる"
                                    ><span class="text-lg leading-none relative -top-0.5">-</span></button>
                                    <input
                                        type="range"
                                        class="flex-1 min-w-48 max-w-xs js-fear-meter-range"
                                        min="{{ $fearMeterMin }}"
                                        max="{{ $fearMeterMax }}"
                                        value="{{ $fearMeterInitial }}"
                                        step="1"
                                    >
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm js-fear-meter-increase shrink-0 inline-flex items-center justify-center"
                                        aria-label="怖さメーターを上げる"
                                    ><span class="text-lg leading-none relative -top-0.5">+</span></button>
                                </div>
                                <div class="mt-2 text-center text-sm text-slate-200" aria-live="polite">
                                    <span class="js-fear-meter-text">{{ $fearMeterTexts[$fearMeterInitial] ?? '' }}</span>
                                    <span class="js-fear-meter-score">(+{{ $fearMeterInitial * 10 }})</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 怖さメーターコメント --}}
                    <div class="mb-5">
                        <label for="fear_meter_comment" class="mb-2 block font-semibold">怖さについて一言コメント <span class="text-slate-400 text-xs font-normal">任意・100文字まで</span></label>
                        <textarea
                            id="fear_meter_comment"
                            name="fear_meter_comment"
                            class="form-control"
                            maxlength="100"
                            rows="3"
                            style="width: 100%;"
                            placeholder="怖さについて一言どうぞ"
                        >{{ $initialFearMeterComment }}</textarea>
                    </div>

                    {{-- 各スコア --}}
                    <div class="mb-4">
                        <label class="mb-2 block font-semibold">各スコア（0〜20）</label>
                        <div class="space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                <span class="w-28 text-sm text-slate-300 shrink-0">ストーリー</span>
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm js-review-score-decrease shrink-0 inline-flex items-center justify-center"
                                        aria-label="ストーリースコアを下げる"
                                        {{ $initialScoreStory <= 0 ? 'disabled' : '' }}
                                    ><span class="text-lg leading-none relative -top-0.5">-</span></button>
                                    <input type="range" name="score_story" class="js-review-score-select" min="0" max="20" step="5" value="{{ $initialScoreStory }}" required>
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm js-review-score-increase shrink-0 inline-flex items-center justify-center"
                                        aria-label="ストーリースコアを上げる"
                                        {{ $initialScoreStory >= 20 ? 'disabled' : '' }}
                                    ><span class="text-lg leading-none relative -top-0.5">+</span></button>
                                    <span class="js-review-score-value w-4 text-center text-sm text-slate-200">{{ $initialScoreStory }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                <span class="w-28 text-sm text-slate-300 shrink-0">雰囲気・演出</span>
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm js-review-score-decrease shrink-0 inline-flex items-center justify-center"
                                        aria-label="雰囲気スコアを下げる"
                                        {{ $initialScoreAtmosphere <= 0 ? 'disabled' : '' }}
                                    ><span class="text-lg leading-none relative -top-0.5">-</span></button>
                                    <input type="range" name="score_atmosphere" class="js-review-score-select" min="0" max="20" step="5" value="{{ $initialScoreAtmosphere }}" required>
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm js-review-score-increase shrink-0 inline-flex items-center justify-center"
                                        aria-label="雰囲気スコアを上げる"
                                        {{ $initialScoreAtmosphere >= 20 ? 'disabled' : '' }}
                                    ><span class="text-lg leading-none relative -top-0.5">+</span></button>
                                    <span class="js-review-score-value w-4 text-center text-sm text-slate-200">{{ $initialScoreAtmosphere }}</span>
                                </div>
                            </div>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                                <span class="w-28 text-sm text-slate-300 shrink-0">ゲーム性</span>
                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm js-review-score-decrease shrink-0 inline-flex items-center justify-center"
                                        aria-label="ゲーム性スコアを下げる"
                                        {{ $initialScoreGameplay <= 0 ? 'disabled' : '' }}
                                    ><span class="text-lg leading-none relative -top-0.5">-</span></button>
                                    <input type="range" name="score_gameplay" class="js-review-score-select" min="0" max="20" step="5" value="{{ $initialScoreGameplay }}" required>
                                    <button
                                        type="button"
                                        class="btn btn-secondary btn-sm js-review-score-increase shrink-0 inline-flex items-center justify-center"
                                        aria-label="ゲーム性スコアを上げる"
                                        {{ $initialScoreGameplay >= 20 ? 'disabled' : '' }}
                                    ><span class="text-lg leading-none relative -top-0.5">+</span></button>
                                    <span class="js-review-score-value w-4 text-center text-sm text-slate-200">{{ $initialScoreGameplay }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- スコア調整 --}}
                    <div class="mb-4">
                        <label for="user_score_adjustment" class="mb-2 block font-semibold">さじ加減（−20〜+20）</label>
                        <div class="flex items-center gap-3">
                            <button
                                type="button"
                                class="btn btn-secondary btn-sm js-review-adjustment-decrease shrink-0 inline-flex items-center justify-center"
                                aria-label="さじ加減を下げる"
                                {{ $initialAdjustment <= -20 ? 'disabled' : '' }}
                            ><span class="text-lg leading-none relative -top-0.5">-</span></button>
                            <input
                                type="range"
                                id="user_score_adjustment"
                                name="user_score_adjustment"
                                class="js-review-adjustment"
                                min="-20"
                                max="20"
                                step="1"
                                value="{{ $initialAdjustment }}"
                            >
                            <button
                                type="button"
                                class="btn btn-secondary btn-sm js-review-adjustment-increase shrink-0 inline-flex items-center justify-center"
                                aria-label="さじ加減を上げる"
                                {{ $initialAdjustment >= 20 ? 'disabled' : '' }}
                            ><span class="text-lg leading-none relative -top-0.5">+</span></button>
                            <span class="js-review-adjustment-value w-8 text-center text-sm text-slate-200">{{ $initialAdjustment >= 0 ? '+' : '' }}{{ $initialAdjustment }}</span>
                        </div>
                    </div>

                    {{-- 総合スコアプレビュー --}}
                    <div class="mb-2 p-3 bg-slate-800/60 rounded-lg inline-flex items-center gap-3">
                        <span class="text-sm text-slate-300">総合スコア：</span>
                        <span class="text-2xl font-bold text-sky-300 js-review-total-score">0</span>
                        <span class="text-sm text-slate-400">/ 100</span>
                    </div>
                    <p class="text-xs text-slate-500">怖さメーター×10 ＋ ストーリー ＋ 雰囲気・演出 ＋ ゲーム性 ＋ さじ加減（0〜100の範囲）</p>

                </div>
            </section>

            {{-- レビュー本文 --}}
            <section class="node" id="review-body-node">
                <div class="node-head">
                    <h3 class="node-head-text">レビュー本文 <span class="text-slate-400 text-xs font-normal">任意・2000文字まで</span></h3>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content basic">
                    <textarea
                        id="body"
                        name="body"
                        class="form-control"
                        maxlength="2000"
                        rows="10"
                        style="width: 100%;"
                        placeholder="このゲームのレビューを書いてください"
                    >{{ $initialBody }}</textarea>
                </div>
            </section>

            {{-- プレイ状況 --}}
            <section class="node" id="review-play-status-node">
                <div class="node-head">
                    <h3 class="node-head-text">プレイ状況 <span class="text-red-400 text-xs font-normal">必須</span></h3>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content basic">
                    <div class="flex flex-wrap gap-4">
                        @foreach (\App\Enums\PlayStatus::cases() as $case)
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input
                                    type="radio"
                                    name="play_status"
                                    value="{{ $case->value }}"
                                    class="js-play-status-radio"
                                    {{ $initialPlayStatus === $case->value ? 'checked' : '' }}
                                    required
                                >
                                {{ $case->text() }}
                            </label>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- プレイ環境 --}}
            @if ($packages->isNotEmpty())
                <section class="node" id="review-packages-node">
                    <div class="node-head">
                        <h3 class="node-head-text">プレイ環境 <span class="text-slate-400 text-xs font-normal">任意・複数選択可</span></h3>
                        <span class="node-pt">●</span>
                    </div>
                    <div class="node-content basic">
                        @foreach ($title->packageGroups->sortByDesc('sort_order') as $pkgGroup)
                            <div class="mb-3">
                                <p class="mb-1 text-slate-400">{{ $pkgGroup->name }}</p>
                                <div class="flex flex-wrap gap-3">
                                    @foreach ($pkgGroup->packages->sortBy([['sort_order', 'desc'], ['game_platform_id', 'desc'], ['default_img_type', 'desc']]) as $package)
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input
                                                type="checkbox"
                                                name="packages[]"
                                                value="{{ $package->id }}"
                                                {{ in_array($package->id, $initialPackageIds) ? 'checked' : '' }}
                                            >
                                            {{ $package->platform->acronym }}&nbsp;{!! $package->node_name !!}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ネタバレを含む --}}
            <section class="node" id="review-spoiler-node">
                <div class="node-head">
                    <h3 class="node-head-text">ネタバレを含む</h3>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content basic">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input
                            type="checkbox"
                            name="has_spoiler"
                            value="1"
                            {{ $initialHasSpoiler ? 'checked' : '' }}
                        >
                        ネタバレを含む
                    </label>

                    <div class="mt-10">
                        <div class="flex flex-wrap items-center gap-3">
                            <button type="submit" class="btn btn-success">投稿</button>
                            <button
                                type="button"
                                class="btn btn-secondary js-review-draft-save"
                                data-draft-url="{{ route('User.Review.Draft.Save') }}"
                            >下書き保存</button>
                        </div>

                    </div>
                </div>
            </section>

        </form>
    </section>

    @if ($review !== null)
        <section class="node" id="review-delete-node">
            <div class="node-head">
                <h2 class="node-head-text">削除</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                <form action="{{ route('User.Review.Destroy') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="title_key" value="{{ $title->key }}">
                    @if ($fearMeterExists)
                        <label class="mb-4 flex items-center gap-2 cursor-pointer text-sm">
                            <input type="checkbox" name="also_delete_fear_meter" value="1">
                            怖さメーターも一緒に削除する
                        </label>
                    @else
                        <input type="hidden" name="also_delete_fear_meter" value="0">
                    @endif
                    <button
                        type="submit"
                        class="btn btn-danger btn-sm"
                        onclick="return confirm('レビューを削除します。よろしいですか？')"
                    >レビューを削除</button>
                </form>
            </div>
        </section>
    @endif

    @php $shortcutFranchise = $title->getFranchise(); @endphp
    <section class="node tree-node" id="footer-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node tree-node" id="reviews-link-node">
                <div class="node-head">
                    <a href="{{ route('Game.TitleReviews', ['titleKey' => $title->key]) }}" class="node-head-text" data-hgn-scope="full">{{ $title->name }} レビュー一覧</a>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content tree">
                    <section class="node tree-node" id="title-link-node">
                        <div class="node-head">
                            <a href="{{ route('Game.TitleDetail', ['titleKey' => $title->key]) }}" class="node-head-text" data-hgn-scope="full">{{ $title->name }}</a>
                            <span class="node-pt">●</span>
                        </div>
                        <div class="node-content tree">
                            @if ($shortcutFranchise !== null)
                                <section class="node tree-node" id="franchise-link-node">
                                    <div class="node-head">
                                        <a href="{{ route('Game.FranchiseDetail', ['franchiseKey' => $shortcutFranchise->key]) }}" class="node-head-text" data-hgn-scope="full">{{ $shortcutFranchise->name }}フランチャイズ</a>
                                        <span class="node-pt">●</span>
                                    </div>
                                    <div class="node-content tree">
                                        <section class="node tree-node" id="lineup-link-node">
                                            <div class="node-head">
                                                <a href="{{ route('Game.Lineup') }}" class="node-head-text" data-hgn-scope="full">ラインナップ</a>
                                                <span class="node-pt">●</span>
                                            </div>
                                            <div class="node-content tree">
                                                <section class="node basic" id="root-link-node">
                                                    <div class="node-head">
                                                        <a href="{{ route('Root') }}" class="node-head-text" data-hgn-scope="full">ルート</a>
                                                        <span class="node-pt">●</span>
                                                    </div>
                                                </section>
                                            </div>
                                        </section>
                                    </div>
                                </section>
                            @else
                                <section class="node tree-node" id="lineup-link-node">
                                    <div class="node-head">
                                        <a href="{{ route('Game.Lineup') }}" class="node-head-text" data-hgn-scope="full">ラインナップ</a>
                                        <span class="node-pt">●</span>
                                    </div>
                                    <div class="node-content tree">
                                        <section class="node basic" id="root-link-node">
                                            <div class="node-head">
                                                <a href="{{ route('Root') }}" class="node-head-text" data-hgn-scope="full">ルート</a>
                                                <span class="node-pt">●</span>
                                            </div>
                                        </section>
                                    </div>
                                </section>
                            @endif
                        </div>
                    </section>
                </div>
            </section>
            <section class="node tree-node">
                <div class="node-head">
                    <a href="{{ route('User.Review.Index') }}" class="node-head-text" data-hgn-scope="full">マイレビュー</a>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content tree">
                    <section class="node basic">
                        <div class="node-head">
                            <a href="{{ route('User.MyNode.Top') }}" class="node-head-text" data-hgn-scope="full">マイノード</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                </div>
            </section>
            @if (Auth::check())
                <section class="node basic" id="logout-link-node">
                    <div class="node-head">
                        <a href="{{ route('Account.Logout') }}" class="node-head-text">ログアウト</a>
                        <span class="node-pt">●</span>
                    </div>
                </section>
            @else
                <section class="node basic">
                    <div class="node-head">
                        <a href="{{ route('Account.Register') }}" class="node-head-text">アカウント新規登録</a>
                        <span class="node-pt">●</span>
                    </div>
                </section>
                <section class="node basic">
                    <div class="node-head">
                        <a href="{{ route('Account.Login') }}" class="node-head-text">ログイン</a>
                        <span class="node-pt">●</span>
                    </div>
                </section>
            @endif
        </div>
    </section>
@endsection
