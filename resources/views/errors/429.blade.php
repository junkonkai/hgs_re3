@extends('layout')

@section('title', 'リクエストが多すぎます')
@section('current-node-title', 'リクエストが多すぎます')

@section('nodes')
    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">リクエストが多すぎます</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                短時間にリクエストが多すぎます。<br>
                しばらく時間をおいてから再度お試しください。
            </p>
        </div>
    </section>

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
@endsection

