@extends('layout')

@section('title', 'システムエラー')
@section('current-node-title', 'システムエラー')

@section('nodes')
    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">エラー発生</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                システムエラーが発生しました。<br>
                エラーの詳細はログに記録済みですので、ご連絡いただく必要はございません。<br>
                申し訳ありませんが、使われていた機能は現在利用できません。<br>
                修正されるまでしばらくお待ちください。
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
