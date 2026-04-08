@extends('layout')

@section('title', 'ログイン設定')
@section('current-node-title', 'ログイン設定')

@section('nodes')
    <section class="node @if($user->hasTwoFactorEmail() || $user->hasTwoFactorTotp()) tree-node @endif" id="login-settings-node">
        <div class="node-head">
            <h2 class="node-head-text">2段階認証</h2>
            <span class="node-pt">●</span>
        </div>

        @if($user->hasTwoFactorEmail() || $user->hasTwoFactorTotp())
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

                @if($user->hasTwoFactorEmail())
                    <p class="ml-4 mb-4">
                        メールによる2段階認証が設定されている。<br>
                        Authenticatorによる2段階認証に切り替えるには無効にする必要があるようだ。
                    </p>
                    <form action="{{ route('User.MyNode.LoginSettings.2fa') }}" method="POST" data-no-push-state="1" class="ml-4 mb-8">
                        @csrf
                        <input type="hidden" name="two_factor_method" value="">
                        <button type="submit" class="btn btn-danger btn-sm">メール2段階認証を無効にする</button>
                    </form>
                @elseif ($user->hasTwoFactorTotp())
                    <p class="mb-4">
                        Authenticatorによる2段階認証が設定されている。<br>
                        メールによる2段階認証に切り替えるには無効にする必要があるようだ。
                    </p>
                    <form action="{{ route('User.MyNode.LoginSettings.Totp.Disable') }}" method="POST" data-no-push-state="1" class="mb-8">
                        @csrf
                        <button type="submit" class="btn btn-danger btn-sm">Authenticator認証を無効にする</button>
                    </form>
                @endif
            </div>
            <div class="node-content tree">
                <section class="node basic" id="logout-link-node">
                    <div class="node-head">
                        <h3 class="node-head-text">リカバリーコード再発行</h3>
                        <span class="node-pt">●</span>
                    </div>
                    <div class="node-content basic">
                        <p class="mb-3">
                            ここでリカバリーコードの再発行ができるようだ。<br>
                            再発行しますか？
                        </p>

                        <form action="{{ route('User.MyNode.LoginSettings.RecoveryCodes.Regenerate') }}" method="POST" data-no-push-state="1">
                            @csrf
                            <div class="d-flex gap-3">
                                <button type="submit" class="btn btn-warning btn-sm">再発行する</button>
                            </div>
                        </form>

                        <div class="alert alert-warning mt-3">
                            再発行すると、既存のリカバリーコードはすべて無効になります。<br>
                            新しいコードを必ず安全な場所に保管してください。
                        </div>
                    </div>
                </section>
            </div>

        @else

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
                    <a href="{{ route('User.MyNode.Email') }}" data-hgn-scope="full">メールアドレス設定画面へ</a>
                </p>
            @else

                {{-- メール2段階認証 --}}
                <div class="mb-4">
                    <form action="{{ route('User.MyNode.LoginSettings.2fa') }}" method="POST" data-no-push-state="1">
                        @csrf
                        <input type="hidden" name="two_factor_method" value="email">
                        <button type="submit" class="btn btn-success btn-sm">メール2段階認証を有効にする</button>
                    </form>
                    <p class="text-sm mb-4">
                        ログイン時にご登録のメールアドレスへ6桁のコードを送信します。<br>
                        送られてきたコードを入力することでログインできます。
                    </p>

                    <div class="mb-3" style="margin-top: 40px;">
                        <a href="{{ route('User.MyNode.LoginSettings.Totp.Setup') }}" class="btn btn-success btn-sm" data-hgn-scope="full">Authenticator認証を設定する</a>
                    </div>
                    <p class="text-sm mb-4">
                        「Google Authenticator」または「Microsoft Authenticator」を利用します。<br>
                        ログイン時にアプリに表示されるコードを入力することでログインできます。
                    </p>
                </div>
                @endif
        </div>
    @endif
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
                    <span class="node-pt">●</span>
                </div>
            </section>
            <section class="node basic">
                <div class="node-head">
                    <a href="{{ route('User.MyNode.Top') }}" class="node-head-text">マイノード</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            <section class="node basic" id="logout-link-node">
                <div class="node-head">
                    <a href="{{ route('Account.Logout') }}" class="node-head-text">ログアウト</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
        </div>
    </section>
@endsection
