@extends('layout')

@section('title', '問い合わせが見つかりません')
@section('current-node-title', '問い合わせが見つかりません')


@section('current-node-content')
    <div class="alert alert-danger">
        <p>
            問い合わせが見つかりませんでした。<br>
            URLが間違っているか、問い合わせが削除されている可能性があります。
        </p>
    </div>
@endsection
@section('nodes')

    <section class="node tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node tree-node">
                <div class="node-head">
                    <a href="{{ route('Contact') }}" class="node-head-text">問い合わせ</a>
                    <span class="node-pt main-node-pt">●</span>
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
        </div>
    </section>
@endsection

