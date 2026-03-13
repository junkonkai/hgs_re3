@extends('layout')

@section('title', 'マイノード')
@section('current-node-title', 'マイノード')

@section('current-node-content')

@if ($needsAcceptance)
<div class="alert alert-warning mt-3">
    <p>
        <a href="{{ route('PrivacyPolicy') }}" data-hgn-scope="full">プライバシーポリシー</a>が改定されています。<br>
        <a href="{{ route('PrivacyPolicy') }}" data-hgn-scope="full">プライバシーポリシー</a>にて内容を確認し「同意」の実行をお願いします。
    </p>
</div>
@endif
@if (session('success'))
<div class="alert alert-success mt-3">
    {{ session('success') }}
</div>
@endif

@endsection

@section('nodes')
    <section class="node tree-node" id="mypage-welcome-node">
        <div class="node-head">
            <h2 class="node-head-text">設定・管理</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree" id="user-tree-node">
            <section class="node tree-node" id="user-data-tree-node">
                <div class="node-head">
                    <h3 class="node-head-text">ゲーム</h3>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content tree">
                    <section class="node basic" id="user-favorite-title-link-node">
                        <div class="node-head">
                            <a href="{{ route('User.Follow.FavoriteTitles') }}" class="node-head-text">お気に入りタイトル</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                    <section class="node basic" id="user-fear-meter-index-link-node">
                        <div class="node-head">
                            <a href="{{ route('User.FearMeter.Index') }}" class="node-head-text">怖さメーター</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                </div>
            </section>


            <section class="node tree-node" id="user-account-tree-node">
                <div class="node-head">
                    <h3 class="node-head-text">アカウント</h3>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content tree">
                    <section class="node basic" id="user-account-profile-edit-link-node">
                        <div class="node-head">
                            <a href="{{ route('User.MyNode.Profile') }}" class="node-head-text">プロフィール設定</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                    <section class="node basic" id="user-account-email-change-link-node">
                        <div class="node-head">
                            <a href="{{ route('User.MyNode.Email') }}" class="node-head-text">メールアドレス変更</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                    <section class="node basic" id="user-account-password-change-link-node">
                        <div class="node-head">
                            <a href="{{ route('User.MyNode.Password') }}" class="node-head-text">パスワード変更</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                    <section class="node basic" id="user-account-social-accounts-link-node">
                        <div class="node-head">
                            <a href="{{ route('User.MyNode.SocialAccounts') }}" class="node-head-text">外部サービス連携</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                    <section class="node basic" id="user-account-withdraw-link-node">
                        <div class="node-head">
                            <a href="{{ route('User.MyNode.Withdraw') }}" class="node-head-text">退会</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </section>

    @include('common.shortcut')
@endsection

