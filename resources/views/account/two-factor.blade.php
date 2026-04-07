@extends('layout')

@section('title', '2段階認証')
@section('current-node-title', '2段階認証')

@section('nodes')
    <section class="node" id="two-factor-form-node">
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

            @if(($twoFactorMethod ?? 'email') === 'totp')
                <p>Google AuthenticatorまたはMicrosoft Authenticatorアプリを開き、表示されている6桁のコードを入力してください。</p>
            @else
                <p>ご登録のメールアドレスに6桁の認証コードを送信しました。<br>コードを入力してログインを完了してください。</p>
                <p><small class="text-muted">コードの有効期限は15分です。</small></p>
            @endif

            <form action="{{ route('TwoFactor.Verify') }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <label class="form-label">認証コード（6桁）</label>
                    <div class="js-otp-input-wrapper d-flex gap-2">
                        <input type="text" class="js-otp-digit form-control text-center fs-4 fw-bold p-0" inputmode="numeric" maxlength="1" autocomplete="off" style="width:3rem;height:3.25rem;color:#000;" autofocus>
                        <input type="text" class="js-otp-digit form-control text-center fs-4 fw-bold p-0" inputmode="numeric" maxlength="1" autocomplete="off" style="width:3rem;height:3.25rem;color:#000;">
                        <input type="text" class="js-otp-digit form-control text-center fs-4 fw-bold p-0" inputmode="numeric" maxlength="1" autocomplete="off" style="width:3rem;height:3.25rem;color:#000;">
                        <input type="text" class="js-otp-digit form-control text-center fs-4 fw-bold p-0" inputmode="numeric" maxlength="1" autocomplete="off" style="width:3rem;height:3.25rem;color:#000;">
                        <input type="text" class="js-otp-digit form-control text-center fs-4 fw-bold p-0" inputmode="numeric" maxlength="1" autocomplete="off" style="width:3rem;height:3.25rem;color:#000;">
                        <input type="text" class="js-otp-digit form-control text-center fs-4 fw-bold p-0" inputmode="numeric" maxlength="1" autocomplete="off" style="width:3rem;height:3.25rem;color:#000;">
                        <input type="hidden" name="code" class="js-otp-hidden">
                    </div>
                    @error('code')
                        <div class="alert alert-danger mt-3">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-success">認証する</button>
                </div>
            </form>

            @if(($twoFactorMethod ?? 'email') !== 'totp')
                <hr>

                <form action="{{ route('TwoFactor.Resend') }}" method="POST">
                    @csrf
                    <p>コードが届かない場合は再送信できます。</p>
                    <button type="submit" class="btn btn-outline-secondary btn-sm">認証コードを再送信</button>
                </form>
            @endif

            <hr>

            <p>認証コードが使えない場合はリカバリーコードでログインできます。</p>

            <form action="{{ route('TwoFactor.Recovery') }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <label class="form-label">リカバリーコード（6桁）</label>
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
                        <div class="alert alert-danger mt-3">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-outline-secondary btn-sm">リカバリーコードでログイン</button>
                </div>
            </form>
        </div>
    </section>

    @include('common.shortcut')
@endsection
