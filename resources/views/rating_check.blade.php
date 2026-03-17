@extends('layout')

@section('title', '年齢確認')
@section('current-node-title', '年齢確認')

@section('nodes')
    <section class="node" id="age-check-node">
        <div class="node-head node-head-small-margin">
            <h2 class="node-head-text">年齢確認</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                この先、性表現を含んだアダルトゲームに関する情報が表示される場合があります。<br>
                18歳未満の閲覧はできません。<br>
                <br>
                18歳以上ですか？
            </p>
            <ul style="margin-top: 30px;">
                <li style="margin-bottom: 10px;"><a href="{{ $currentUrl }}" data-hgn-scope="full">はい、18歳以上です</a></li>
                <li><a href="{{ route('Root') }}" data-hgn-scope="full">いいえ、18歳未満です</a></li>
            </ul>
        </div>
    </section>
@endsection
