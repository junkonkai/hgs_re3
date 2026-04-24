@extends('layout')

@section('title', '怖さメーター')
@section('current-node-title', '怖さメーター')

@section('nodes')
    <section class="node" id="password-change-form-node">
        <div class="node-head">
            <h2 class="node-head-text">{{ $title->name }}</h2>
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

            @php
                $fearMeterCases = \App\Enums\FearMeter::cases();
                $fearMeterValues = array_map(fn ($case) => $case->value, $fearMeterCases);
                $fearMeterMin = min($fearMeterValues);
                $fearMeterMax = max($fearMeterValues);
                $fearMeterRange = max(1, $fearMeterMax - $fearMeterMin);
                $fearMeterTexts = [];
                foreach ($fearMeterCases as $case) {
                    $fearMeterTexts[$case->value] = $case->text();
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
                $fearMeterInitial = max($fearMeterMin, min($fearMeterMax, $fearMeterInitial));
                $fearMeterInitialPercent = (($fearMeterInitial - $fearMeterMin) / $fearMeterRange) * 100;
                $initialComment = old('comment') ?? $fearMeterDraft?->comment ?? $fearMeterLogComment ?? '';
            @endphp

            <form id="fear-meter-form" action="{{ route('User.FearMeter.Form.Store') }}" method="POST" class="fear-meter-input-form">
                @csrf
                <input type="hidden" name="title_key" value="{{ $title->key }}">
                @if (!empty($from))
                <input type="hidden" name="from" value="{{ $from }}">
                @endif
                <input type="hidden" class="js-fear-meter-value" name="fear_meter" value="{{ $fearMeterInitial }}" required>

                <div
                    class="mb-5 js-fear-meter-input"
                    data-fear-meter-min="{{ $fearMeterMin }}"
                    data-fear-meter-max="{{ $fearMeterMax }}"
                    data-fear-meter-texts='@json($fearMeterTexts)'
                >
                    <label class="mb-2 block">怖さメーター</label>
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

                <div class="form-group" style="margin-top: 20px;">
                    <label for="comment">怖さについて一言コメント（任意・100文字まで）</label>
                    <textarea id="comment" name="comment" class="form-control" maxlength="100" rows="3" style="width: 100%;">{{ $initialComment }}</textarea>
                </div>
            </form>

            <form id="fear-meter-draft-form" action="{{ route('User.FearMeter.Draft.Save') }}" method="POST">
                @csrf
                <input type="hidden" name="title_key" value="{{ $title->key }}">
                <input type="hidden" class="js-fear-meter-draft-value" name="fear_meter" value="{{ $fearMeterInitial }}">
                <input type="hidden" class="js-fear-meter-draft-comment" name="comment" value="{{ $initialComment }}">
            </form>

            @if (isset($fearMeter))
                @php
                    $deleteConfirmMessage = $hasReview
                        ? '怖さメーターを削除すると、レビューも一緒に削除されます。よろしいですか？'
                        : '怖さメーターを削除します。よろしいですか？';
                @endphp
                <form id="fear-meter-delete-form" action="{{ route('User.FearMeter.Form.Delete') }}" method="POST">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="title_key" value="{{ $title->key }}">
                    @if (!empty($from))
                    <input type="hidden" name="from" value="{{ $from }}">
                    @endif
                    @if ($hasReview)
                    <input type="hidden" name="also_delete_review" value="1">
                    @endif
                </form>
            @endif

            <div class="flex items-center justify-between mt-5">
                <div class="flex items-center gap-2">
                    <button type="submit" form="fear-meter-form" class="btn btn-success">{{ isset($fearMeter) ? '更新' : '登録' }}</button>
                    <button type="submit" form="fear-meter-draft-form" class="btn btn-secondary btn-sm js-fear-meter-draft-save">下書き保存</button>
                </div>
                @if (isset($fearMeter))
                <button type="submit" form="fear-meter-delete-form" class="btn btn-danger btn-sm" onclick="return confirm('{{ $deleteConfirmMessage }}')">削除</button>
                @endif
            </div>
        </div>
    </section>
    @include('common.shortcut', ['shortcutRoute' => $shortcutRoute])
@endsection
