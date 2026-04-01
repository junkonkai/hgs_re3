@extends('layout')

@section('title', '怖さメーター一覧')
@section('current-node-title', '怖さメーター一覧')

@section('current-node-content')
@if (session('success'))
    <div class="alert alert-success mt-3 relative pr-10">
        <button type="button" class="absolute top-0 right-0 p-2 border-0 bg-transparent cursor-pointer" style="line-height: 1;" onclick="this.closest('.alert').style.display='none'" aria-label="閉じる"><i class="bi bi-x"></i></button>
        {!! nl2br(e(session('success'))) !!}
    </div>
@endif
@if ($fearMeters->isEmpty())
    <p>
        怖さメーターを入力していないようだ。<br>
        <a href="{{ route('Game.Lineup') }}" data-hgn-scope="full">ラインナップ</a>からタイトルを探して、怖さメーターを入力してみよう。
    </p>
@endif
@endsection

@section('nodes')
    @if ($fearMeters->isNotEmpty())
        <section class="node" id="fear-meter-list-node">
            <div class="node-head">
                <h2 class="node-head-text">怖さメーター</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                <table class="border border-gray-500 border-collapse">
                    @foreach ($fearMeters as $fearMeter)
                        <tr>
                            <td class="border border-gray-500 px-3 py-2">{{ $fearMeter->gameTitle->name }}</td>
                            <td class="border border-gray-500 px-3 py-2">
                                @php
                                    $fearMeterMax = 4;
                                    $fearMeterPercent = ($fearMeter->fear_meter->value / $fearMeterMax) * 100;
                                @endphp
                                <div class="space-y-1">
                                    <div class="h-3 w-48 overflow-hidden rounded-full bg-slate-700/60">
                                        <div
                                            class="h-full bg-gradient-to-r from-slate-800 via-sky-600 to-indigo-500"
                                            style="width: {{ $fearMeterPercent }}%;"
                                        ></div>
                                    </div>
                                    <div class="text-sm text-slate-200">
                                        <span class="font-semibold">{{ $fearMeter->fear_meter->value }} / {{ $fearMeterMax }}</span>
                                        <span class="text-slate-400">（{{ $fearMeter->fear_meter->text() }}）</span>
                                    </div>
                                </div>
                            </td>
                            <td class="border border-gray-500 px-3 py-2">
                                <a href="{{ route('User.FearMeter.Form', ['titleKey' => $fearMeter->gameTitle->key, 'from' => 'fear-meter-list']) }}" data-hgn-scope="full"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </table>
                @include('common.pager', ['pager' => $pager])
            </div>
        </section>
    @endif

    @include('common.shortcut')
@endsection
