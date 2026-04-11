@extends('layout')

@section('title', '405 Method Not Allowed')
@section('current-node-title', '405 Method Not Allowed')

@section('nodes')
    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">許可されていないリクエスト</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                そのリクエストは許可されていないようだ。
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
