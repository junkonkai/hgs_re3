@extends('layout')

@section('title', '419 CSRF Token Mismatch')
@section('current-node-title', '419 CSRF Token Mismatch')

@section('nodes')
    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">セキュリティトークンエラー</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                トークンの検証に失敗しました。<br>
                ページの有効期限が切れた可能性があります。<br>
                お手数ですが、再度アクセスしてやり直してください。<br>
                <br>
                何度アクセスしてもこの画面が表示される場合は、<br>
                一度ブラウザを終了してから再度お試しください。
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
