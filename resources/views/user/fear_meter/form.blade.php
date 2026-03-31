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
                    @if (!empty($fearMeterComment))
                    <div class="basis-full text-sm">
                        <span class="text-slate-400">一言コメント: </span><br>
                        <span class="text-slate-200">{!! nl2br(e($fearMeterComment)) !!}</span>
                    </div>
                    @endif
                </div>

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
                <form action="{{ route('User.FearMeter.Form.Store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="title_key" value="{{ $title->key }}">
                    @if (!empty($from))
                    <input type="hidden" name="from" value="{{ $from }}">
                    @endif

                    @foreach (\App\Enums\FearMeter::cases() as $case)
                    <div class="mb-3 flex items-center gap-2">
                        <input type="radio" name="fear_meter" value="{{ $case->value }}" id="fear_meter_{{ $case->value }}" required>
                        <label for="fear_meter_{{ $case->value }}">{{ $case->text() }}</label>
                    </div>
                    @endforeach

                    <div class="form-group" style="margin-top: 20px;">
                        <label for="comment">怖さについて一言コメント（任意・100文字まで）</label>
                        <textarea id="comment" name="comment" maxlength="100" rows="3" style="width: 100%;">{{ old('comment') }}</textarea>
                    </div>

                    <div class="form-group" style="margin-top: 20px;">
                        <button type="submit" class="btn btn-success">入力</button>
                    </div>
                </form>
            @endif
        </div>
    </section>
    @include('common.shortcut', ['shortcutRoute' => $shortcutRoute])
@endsection


