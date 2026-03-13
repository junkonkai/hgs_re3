@extends('layout')

@section('title', '502 Bad Gateway')
@section('current-node-title', '502 Bad Gateway')

@section('nodes')
    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">ゲートウェイエラー</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                不正な応答を受信したため、処理を実行できませんでした。<br>
                サーバーの一時的な問題の可能性があります。<br>
                <br>
                しばらく待ってから再度お試しください。<br>
                問題が解決しない場合は、<a href="{{ route('Contact') }}" data-hgn-scope="full">問い合わせ</a>ください。
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
