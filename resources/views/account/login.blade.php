@extends('layout')

@section('title', 'ログイン')
@section('current-node-title', 'ログイン')
@section('current-node-content')
<p class="alert alert-warning">
    休止前に登録されたアカウントは、個人情報保護の観点から削除しました。<br>
    2025年11月1日以前に登録頂いた方には大変申し訳ありませんが、<br>
    改めて<a href="{{ route('Account.Register') }}" data-hgn-scope="full">新規登録</a>をお願いします。
</p>
@endsection

@section('nodes')
    <section class="node" id="login-form-node">
        <div class="node-head">
            <h2 class="node-head-text">外部サービスでログイン</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <div class="mt-3 mb-3">
                <a href="{{ route('Account.GitHub.Redirect') }}" class="btn btn-outline-secondary" data-hgn-scope="external">GitHub</a>
                <a href="{{ route('Account.Steam.Redirect') }}" class="btn btn-outline-secondary" data-hgn-scope="external">Steam</a>
                {{-- X連携: フリープランでは /2/users/me が使えないため非表示。課金後に有効化する。 --}}
                {{-- <a href="{{ route('Account.X.Redirect') }}" class="btn btn-outline-secondary">X</a> --}}
            </div>
        </div>
    </section>

    <section class="node" id="login-form-node">
        <div class="node-head">
            <h2 class="node-head-text">ログインフォーム</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">

            @if(session('success'))
                <div class="alert alert-success mt-3">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger mt-3">
                    {{ session('error') }}
                </div>
            @endif
        
            @error('login')
                <div class="alert alert-danger mt-3">
                    {{ $message }}
                </div>
            @enderror
            

            <form id="login-form" action="{{ route('Account.Auth') }}" method="POST" data-child-only="0">
                @csrf
                <div class="form-group mb-3">
                    <label for="email" class="form-label">メールアドレス</label>
                    <input type="email" name="email" class="form-control" id="email" placeholder="メールアドレス" value="{{ old('email') }}" required autofocus>
                </div>
                <div class="form-group mb-3">
                    <label for="password" class="form-label">パスワード</label>
                    <input type="password" name="password" class="form-control" id="password" placeholder="パスワード" required>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" name="remember_me" value="1" id="rememberMe" checked>
                    <label class="form-check-label" for="rememberMe">
                        ログイン状態を保持する
                    </label>
                </div>
                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="btn btn-success">ログイン</button>
                </div>
            </form>



            <p>
                アカウントをお持ちでない方は<a href="{{ route('Account.Register') }}">こちら</a>から新規登録してください。
            </p>
            <p>
                <a href="{{ route('Account.PasswordReset') }}">パスワードをお忘れの方はこちら</a>
            </p>
        </div>
    </section>

    @include('common.shortcut')
@endsection

