@extends('layout')

@section('title', 'ルート')
@section('current-node-title', 'ホラーゲームネットワーク(α)')

@section('nodes')
    <section class="node tree-node" id="horror-games-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">ホラーゲーム</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node link-node" id="search-link-node">
                <div class="node-head">
                    <a href="{{ route('Game.Search') }}" class="node-head-text">タイトル検索</a>
                    <span class="node-pt">●</span>
                </div>
            </section>

            <section class="node link-node" id="lineup-link-node">
                <div class="node-head">
                    <a href="{{ route('Game.Lineup') }}" class="node-head-text">ラインナップ</a>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content behind">
                    <div class="behind-node">
                        <span class="node-pt">●</span><span>バイオハザード</span>
                    </div>
                    <div class="behind-node">
                        <span class="node-pt">●</span><span>サイレントヒル</span>
                    </div>
                    <div class="behind-node">
                        <span class="node-pt">●</span><span>零</span>
                    </div>
                </div>
            </section>

            <section class="node link-node" id="platform-link-node">
                <div class="node-head">
                    <a href="{{ route('Game.Platform') }}" class="node-head-text">プラットフォーム</a>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content behind">
                    <div class="behind-node">
                        <span class="node-pt">●</span><span>Nintendo Switch 2</span>
                    </div>
                    <div class="behind-node">
                        <span class="node-pt">●</span><span>PlayStation 5</span>
                    </div>
                    <div class="behind-node">
                        <span class="node-pt">●</span><span>Xbox Series X</span>
                    </div>
                </div>
            </section>

            {{--
            <section class="node link-node" id="maker-link-node">
                <div class="node-head">
                    <a href="{{ route('Game.Maker') }}" class="node-head-text">ゲームメーカー</a>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content behind">
                    <div class="behind-node">
                        <span class="node-pt">●</span><span>カプコン</span>
                    </div>
                    <div class="behind-node">
                        <span class="node-pt">●</span><span>コナミ</span>
                    </div>
                    <div class="behind-node">
                        <span class="node-pt">●</span><span>コーエーテクモ</span>
                    </div>
                </div>
            </section>
            --}}
        </div>
    </section>
    <section class="node link-node" id="information-node">
        <div class="node-head">
            <a href="{{ route('Informations') }}" class="node-head-text">お知らせ</a>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content behind">
            @if (!$infoList->isEmpty())
                @foreach ($infoList as $info)
                    <div class="behind-node">
                        <span class="node-pt">●</span><span>{{ $info->head }}</span>
                    </div>
                @endforeach
            @else
                <div class="behind-node">
                    <span class="node-pt">●</span><span>現在、お知らせはありません。</span>
                </div>
            @endif
        </div>
    </section>

    <section class="node tree-node" id="account-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">アカウント</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            @if (!Auth::check())
            <section class="node link-node" id="login-link-node">
                <div class="node-head">
                    <a href="{{ route('Account.Login') }}" class="node-head-text">ログイン</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            <section class="node link-node" id="login-link-node">
                <div class="node-head">
                    <a href="{{ route('Account.Register') }}" class="node-head-text">新規登録</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            @else
            <section class="node link-node" id="logout-link-node">
                <div class="node-head">
                    <a href="{{ route('User.MyNode.Top') }}" class="node-head-text">マイページ</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            <section class="node link-node" id="logout-link-node">
                <div class="node-head">
                    <a href="{{ route('Account.Logout') }}" class="node-head-text">ログアウト</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            @endif
        </div>
    </section>

    <section class="node link-node" id="about-node">
        <div class="node-head">
            <a href="{{ route('About') }}" class="node-head-text" id="about-a">このサイトについて</a>
            <span class="node-pt">●</span>
        </div>
    </section>
    <section class="node link-node" id="privacy-policy-node">
        <div class="node-head">
            <a href="{{ route('PrivacyPolicy') }}" class="node-head-text" id="privacy-policy-a">プライバシーポリシー</a>
            <span class="node-pt">●</span>
        </div>
    </section>
    <section class="node link-node" id="contact-node">
        <div class="node-head">
            <a href="{{ route('Contact') }}" class="node-head-text" id="contact-a">問い合わせ</a>
            <span class="node-pt">●</span>
        </div>
    </section>
@endsection
