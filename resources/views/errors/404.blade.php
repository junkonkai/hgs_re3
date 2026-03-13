@extends('layout')

@section('title', '404 Not Found')
@section('current-node-title', '404 Not Found')

@section('nodes')
    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">ページが見つかりません</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                指定のページは存在しません。<br>
                URLが間違っているか、ページが削除された可能性があります。
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
