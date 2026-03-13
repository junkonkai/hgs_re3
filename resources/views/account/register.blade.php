@extends('layout')

@section('title', '新規登録')
@section('current-node-title', '新規登録')
@section('current-node-content')
    <p class="alert alert-info">
        最新の<a href="{{ route('PrivacyPolicy') }}" data-hgn-scope="full">プライバシーポリシー</a>に同意いただいたものとして新規登録を受け付けます。
    </p>
@endsection

@section('nodes')
    <section class="node" id="register-form-node">
        <div class="node-head">
            <h2 class="node-head-text">外部サービスで登録</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <div class="mb-3">
                <a href="{{ route('Account.GitHub.Redirect') }}" class="btn btn-outline-secondary">GitHub</a>
            </div>
        </div>
    </section>

    <section class="node" id="register-form-node">
        <div class="node-head">
            <h2 class="node-head-text">新規登録フォーム</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            @if(session('error'))
                <div class="alert alert-danger mt-3">
                    {{ session('error') }}
                </div>
            @endif

            @error('email')
                <div class="alert alert-warning my-3">
                    {!! nl2br(e($message)) !!}
                </div>
            @enderror
            <form id="account-register-form" action="{{ route('Account.Register.Store') }}" method="POST" data-child-only="0">
                @csrf
                <div class="form-group mb-3">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input type="email" name="email" class="form-control" id="email" placeholder="メールアドレス" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="form-group mb-3">
                    <label for="name" class="form-label">お名前</label>
                    <input type="text" name="name" class="form-control" id="name" placeholder="お名前" value="{{ old('name') }}">
                    @error('name')
                        <div class="alert alert-warning my-3">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">新規登録</button>
                </div>
            </form>
        </div>
    </section>

    @include('common.shortcut')
@endsection

