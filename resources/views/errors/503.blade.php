@extends('layout')

@section('title', '503 Service Unavailable')
@section('current-node-title', '503 Service Unavailable')

@section('nodes')
    <section class="node">
        <div class="node-head">
            @if (app()->isDownForMaintenance())
                <h2 class="node-head-text">メンテナンス中</h2>
            @else
                <h2 class="node-head-text">サービス利用不可</h2>
            @endif
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            @if (app()->isDownForMaintenance())
                <p>
                    現在、メンテナンスを実施しております。<br>
                    @if(isset($exception) && isset($exception->retryAfter))
                        {{ date('Y年m月d日 H:i', $exception->retryAfter) }} 頃に終了予定です。<br>
                    @endif
                    <br>
                    しばらくお待ちください。
                </p>
            @else
                <p>
                    サービスが一時的に利用できません。<br>
                    サーバーの負荷が高い可能性があります。<br>
                    <br>
                    申し訳ありませんが、しばらく待って再度アクセスしてください。
                </p>
            @endif
        </div>
    </section>

    @if (!app()->isDownForMaintenance())
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
    </section>
    @endif
@endsection
