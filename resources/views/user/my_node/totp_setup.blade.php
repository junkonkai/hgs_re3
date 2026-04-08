@extends('layout')

@section('title', 'Authenticator 2段階認証の設定')
@section('current-node-title', 'Authenticator 2段階認証の設定')

@section('nodes')
    <section class="node" id="totp-setup-node">
        <div class="node-head">
            <h2 class="node-head-text">手順1：アプリインストール</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                Google Authenticator または Microsoft Authenticator を<br>
                スマホやタブレット端末にインストールしてください。
            </p>

            <div class="mt-3">
                <div class="mb-3">
                    <p class="mb-1"><strong>Google Authenticator</strong></p>
                    <div class="pl-4">
                        <a href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2" target="_blank" class="mr-4">Google Play</a>
                        <a href="https://apps.apple.com/jp/app/google-authenticator/id388497605" target="_blank">App Store</a>
                    </div>
                </div>
                <div>
                    <p class="mb-1"><strong>Microsoft Authenticator</strong></p>
                    <div class="pl-4">
                        <a href="https://play.google.com/store/apps/details?id=com.azure.authenticator" target="_blank" class="mr-4">Google Play</a>
                        <a href="https://apps.apple.com/jp/app/microsoft-authenticator/id983156458" target="_blank">App Store</a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="node" id="totp-setup-node">
        <div class="node-head">
            <h2 class="node-head-text">手順2：アプリでQRコード読み取り</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>アプリを開き、QRコードを読み取ってください。</p>

            <div class="my-4">
                <img src="data:image/svg+xml;base64,{{ $qrCodeBase64 }}" alt="QRコード" width="200" height="200">
            </div>

            <p>QRコードが読み取れない場合は、以下のキーを手動で入力してください。</p>
            <div class="mt-2 mb-3">
                <code class="d-block p-2 bg-secondary-subtle rounded" style="letter-spacing: 0.15em; font-size: 1.1em; word-break: break-all;">{{ $secret }}</code>
            </div>
        </div>
    </section>

    <section class="node" id="totp-setup-node">
        <div class="node-head">
            <h2 class="node-head-text">手順3：コード入力</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p class="mb-3">アプリに表示された6桁のコードを入力してください。</p>

            <form action="{{ route('User.MyNode.LoginSettings.Totp.Confirm') }}" method="POST" data-no-push-state="1">
                @csrf
                <div class="form-group mb-3">
                    <div class="js-otp-input-wrapper d-flex gap-2">
                        <input type="text" class="js-otp-digit form-control text-center fs-4 fw-bold p-0" inputmode="numeric" maxlength="1" autocomplete="off" style="width:3rem;height:3.25rem;color:#000;">
                        <input type="text" class="js-otp-digit form-control text-center fs-4 fw-bold p-0" inputmode="numeric" maxlength="1" autocomplete="off" style="width:3rem;height:3.25rem;color:#000;">
                        <input type="text" class="js-otp-digit form-control text-center fs-4 fw-bold p-0" inputmode="numeric" maxlength="1" autocomplete="off" style="width:3rem;height:3.25rem;color:#000;">
                        <input type="text" class="js-otp-digit form-control text-center fs-4 fw-bold p-0" inputmode="numeric" maxlength="1" autocomplete="off" style="width:3rem;height:3.25rem;color:#000;">
                        <input type="text" class="js-otp-digit form-control text-center fs-4 fw-bold p-0" inputmode="numeric" maxlength="1" autocomplete="off" style="width:3rem;height:3.25rem;color:#000;">
                        <input type="text" class="js-otp-digit form-control text-center fs-4 fw-bold p-0" inputmode="numeric" maxlength="1" autocomplete="off" style="width:3rem;height:3.25rem;color:#000;">
                        <input type="hidden" name="code" class="js-otp-hidden">
                    </div>
                    @error('code')
                        <div class="alert alert-danger mt-2">{{ $message }}</div>
                    @enderror
                </div>
                <div class="form-group d-flex gap-2">
                    <button type="submit" class="btn btn-success">設定する</button>
                </div>
            </form>
        </div>
    </section>

    <section class="node tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node tree-node" id="back-to-lineup-node">
                <div class="node-head">
                    <a href="{{ route('User.MyNode.LoginSettings') }}" class="node-head-text">ログイン設定</a>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content tree">
                    <section class="node basic" id="back-to-root-node">
                        <div class="node-head">
                            <a href="{{ route('User.MyNode.Top') }}" class="node-head-text">マイノード</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                </div>
            </section>

            <section class="node basic">
                <div class="node-head">
                    <a href="{{ route('Root') }}" class="node-head-text">ルート</a>
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
