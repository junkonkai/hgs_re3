@extends('layout')

@section('title', 'リカバリーコード')
@section('current-node-title', 'リカバリーコード')

@section('nodes')
    <section class="node" id="recovery-codes-node">
        <div class="node-head">
            <h2 class="node-head-text">リカバリーコード</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">

            <div class="alert alert-warning mt-3">
                <strong>このコードは今後二度と表示されません。</strong><br>
                必ずメモや印刷など安全な方法で保管してください。<br>
                2段階認証が使えなくなった際に、1回につき1コードでログインできます。
            </div>

            <ul class="list-unstyled mb-4" style="font-family: monospace; font-size: 1.2rem; letter-spacing: 0.1em;">
                @foreach($plainCodes as $code)
                    <li>{{ $code }}</li>
                @endforeach
            </ul>

            <a href="{{ route('User.MyNode.LoginSettings') }}" class="btn btn-success" data-hgn-scope="full">
                保存しました。ログイン設定へ戻る
            </a>
        </div>
    </section>

    @include('common.shortcut')
@endsection
