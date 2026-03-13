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
    <p>入力した怖さメーターはありません。</p>
@else
    <p>入力した怖さメーターの一覧です。</p>
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
                            <td class="border border-gray-500 px-3 py-2">{{ $fearMeter->fear_meter->text() }}</td>
                            <td class="border border-gray-500 px-3 py-2">
                                <a href="{{ route('User.FearMeter.Form', ['titleKey' => $fearMeter->gameTitle->key, 'from' => 'fear-meter-list']) }}" data-hgn-scope="full"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    @endforeach
                </table>
                <div class="mt-3">{{ $fearMeters->links('user.fear_meter.pagination') }}</div>
            </div>
        </section>
    @endif

    @include('common.shortcut')
@endsection
