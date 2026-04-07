@extends('layout')

@section('title', 'ログイン設定')
@section('current-node-title', 'ログイン設定')

@section('nodes')
    <section class="node" id="login-settings-node">
        <div class="node-head">
            <h2 class="node-head-text">2段階認証</h2>
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

            @if(empty($user->email))
                <p>
                    2段階認証を有効にするにはメールアドレスが設定されている必要があるようだ。<br>
                    メールアドレスを設定しますか？<br>
                    <br>
                </p>
                <div class="flex" style="width:200px;">
                    <div class="w-1/2 text-center">
                        <a href="{{ route('User.MyNode.Email') }}" data-hgn-scope="full">はい</a>
                    </div>
                    <div class="w-1/2 text-center">
                        <a href="{{ route('User.MyNode.Top') }}" data-hgn-scope="full">いいえ</a>
                    </div>
                </div>
            @elseif($user->hasTwoFactorEmail())
                <p class="mb-4">
                    メールによる2段階認証が設定されている。<br>
                    Authenticatorによる2段階認証に切り替えるには無効にする必要があるようだ。
                </p>
                <form action="{{ route('User.MyNode.LoginSettings.2fa') }}" method="POST" data-no-push-state="1">
                    @csrf
                    <input type="hidden" name="two_factor_method" value="">
                    <button type="submit" class="btn btn-danger btn-sm">メール2段階認証を無効にする</button>
                </form>
            @elseif($user->hasTwoFactorTotp())
                <p class="mb-4">
                    Authenticatorによる2段階認証が設定されている。<br>
                    メールによる2段階認証に切り替えるには無効にする必要があるようだ。
                </p>
                <form action="{{ route('User.MyNode.LoginSettings.Totp.Disable') }}" method="POST" data-no-push-state="1">
                    @csrf
                    <button type="submit" class="btn btn-danger btn-sm">Authenticator認証を無効にする</button>
                </form>
            @else

                <p class="mb-4">
                    メール認証かAuthenticator認証かを選択してください。
                </p>

                <dl>
                    <dt>【メール認証】</dt>
                    <dd class="mb-3">
                        ログイン時にご登録のメールアドレスへ6桁のコードを送信します。<br>
                        送られてきたコードを入力することでログインできます。
                    </dd>
                    <dt>【Authenticator認証】</dt>
                    <dd>
                        「Google Authenticator」または「Microsoft Authenticator」を利用します。<br>
                        ログイン時にアプリに表示されるコードを入力することでログインできます。
                    </dd>
                </dl>

                {{-- メール2段階認証 --}}
                <div class="mb-4">
                    <form action="{{ route('User.MyNode.LoginSettings.2fa') }}" method="POST" data-no-push-state="1">
                        @csrf
                        <input type="hidden" name="two_factor_method" value="email">
                        <button type="submit" class="btn btn-success btn-sm">メール2段階認証を有効にする</button>
                    </form>
                    
                    <a href="{{ route('User.MyNode.LoginSettings.Totp.Setup') }}" class="btn btn-success btn-sm" data-hgn-scope="full">Authenticator認証を設定する</a>
                </div>
                @endif
        </div>
    </section>

    @include('common.shortcut')
@endsection
