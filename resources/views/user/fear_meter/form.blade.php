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

            @if (isset($fearMeter))
                <div class="alert alert-warning mt-3">
                    このタイトルの怖さメーターは入力済みです。<br>変更する場合は一度削除してください。
                </div>
                @php
                    $fearMeterMax = 4;
                    $fearMeterValue = (float) $fearMeter->fear_meter->value;
                    $fearMeterValue = max(0, min($fearMeterMax, $fearMeterValue));
                    $fearMeterPercent = ($fearMeterValue / $fearMeterMax) * 100;
                @endphp
                <div class="mt-2 flex flex-wrap items-center gap-3">
                    <div class="h-3 w-full max-w-xs overflow-hidden rounded-full bg-slate-700/60">
                        <div
                            class="h-full bg-gradient-to-r from-slate-800 via-sky-600 to-indigo-500"
                            style="width: {{ $fearMeterPercent }}%;"
                        ></div>
                    </div>
                    <div class="text-sm text-slate-200">
                        <span class="font-semibold">{{ number_format($fearMeterValue, 0) }}: {{ $fearMeter->fear_meter->text() }}</span>
                    </div>
                </div>
                    @if (!empty($fearMeterComment))
                    <div class="flex gap-2 text-sm mt-2">
                        <span class="block text-slate-400">一言コメント:</span>
                        <span class="block text-slate-200">{!! nl2br(e($fearMeterComment)) !!}</span>
                    </div>
                    @endif

                <form action="{{ route('User.FearMeter.Form.Delete') }}" method="POST" class="mt-10">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="title_key" value="{{ $title->key }}">
                    @if (!empty($from))
                    <input type="hidden" name="from" value="{{ $from }}">
                    @endif
                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('怖さメーターを削除します。よろしいですか？')">削除</button>
                </form>
            @else
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
                    $fearMeterInitial = is_numeric($fearMeterOld) ? (int) $fearMeterOld : 2;
                    $fearMeterInitial = max($fearMeterMin, min($fearMeterMax, $fearMeterInitial));
                    $fearMeterInitialPercent = (($fearMeterInitial - $fearMeterMin) / $fearMeterRange) * 100;
                @endphp
                <form action="{{ route('User.FearMeter.Form.Store') }}" method="POST" class="fear-meter-input-form">
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
                        <textarea id="comment" name="comment" maxlength="100" rows="3" style="width: 100%;">{{ old('comment') }}</textarea>
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-success">登録</button>
                    </div>
                </form>
            @endif
        </div>
    </section>
    @include('common.shortcut', ['shortcutRoute' => $shortcutRoute])
@endsection


