@extends('layout')

@section('title', 'プライバシーポリシー')
@section('current-node-title', 'プライバシーポリシー')


@section('current-node-content')
    <p style="font-size: 13px; padding-bottom: 30px;">
        最終改定日：{{ $privacyPolicyRevisionDate->format('Y年n月j日') }}
    </p>
    @if ($needsAcceptance ?? false)
    <p class="alert alert-warning">
        プライバシーポリシーが改定されています。<br>
        内容を確認し、同意できる場合は「同意」ボタンを押してください。<br>
        同意できない場合、個人情報を取り扱えないためサイトをご利用いただくことができなくなります。
    </p>
        
    <form method="POST" action="{{ route('PrivacyPolicy.Accept') }}" style="padding-bottom: 30px;" data-no-push-state="1" data-child-only="0">
        @csrf
        <button type="submit" class="btn btn-default">同意</button>
    </form>
    @endif
@endsection

@section('nodes')
    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">Intended for users in Japan only</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                当サイトは日本専用サイトです。<br>
                日本国の法律に従い、日本語以外でのサービス提供やサポートは行っておりません。
            </p>
            <p>
                This website is exclusively for users in Japan.<br>
                In compliance with Japanese law, we do not provide services or support in languages other than Japanese.
            </p>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">法令遵守</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                当サイトは、個人情報の保護に関する法律（個人情報保護法）およびその他関連法令を遵守し、個人情報を適切に取り扱います。
            </p>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">アクセスログ</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                当サイトでは、サーバーの運用・保守およびセキュリティ確保のため、アクセスログを記録しています。<br>
                記録される情報には、IPアドレス、アクセス日時、閲覧ページ、ブラウザの種類などが含まれます。<br>
                これらの情報は個人を特定する目的では使用せず、サイトの改善やセキュリティ対策のためにのみ利用します。<br>
                また、法令に基づく場合を除き、これらの情報を第三者へ提供することはありません。<br>
                アクセスログは一定期間（おおよそ6か月以内）保管した後、順次削除しています。
            </p>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">個人情報の取得</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                アカウント登録機能を利用される場合に限り、メールアドレスを取得しています。
            </p>
            <p>
                休止前のHGS/HGNへ登録いただいていた方のメールアドレスやSNSのID情報は削除しております。<br>
                長らく休止状態にあったため、個人情報保護の観点からアカウント情報をリセットすることにしました。<br>
                大変お手数ですが、改めてアカウントの新規登録をお願いします。
            </p>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">個人情報の安全管理措置</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                当サイトでは、取得した個人情報を適切に管理し、漏えい・改ざん・紛失等を防止するため、必要かつ合理的な安全管理措置を講じています。<br>
                通信にはSSLによる暗号化を採用し、通信経路上での盗聴や改ざんを防止しています。<br>
                当サイトはLaravelによって構築されており、サーバーにはアクセス制限および不正アクセス対策を実施しています。<br>
                個人情報は、利用目的の達成に必要な範囲内でのみ取り扱い、不要となった場合には適切な方法で削除または破棄いたします。
            </p>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">個人情報の開示・訂正・削除について</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                当サイトが保有する個人情報について、ご本人から開示・訂正・削除等のご請求があった場合には、本人確認の上、合理的な範囲で対応いたします。<br>
                請求は当サイトの<a href="{{ route('Contact') }}" data-hgn-scope="full">問い合わせ</a>よりご連絡ください。<br>
                メール等での個別対応は行っておりません。<br>
                原則として30日以内に対応いたしますが、法令等に基づき削除できない場合や、対応に時間を要する場合には、その旨をお知らせいたします。
            </p>
            <p>
                退会された場合、当サイトは不正利用防止・お問い合わせ対応等のため、退会日から90日間はユーザー情報を保持します。<br>
                当該期間経過後、法令上の保存義務がある場合を除き、速やかに削除します。
            </p>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">Cookieの利用</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                当サイトでは、Cookieを使用しています。<br>
                これは、ログイン状態の維持や各種パラメーターの保持のために利用しています。<br>
                取得されるCookie情報には個人を特定できる情報は含まれていません。
            </p>
            <p>
                また、広告配信および成果測定のため、第三者であるアフィリエイトサービス提供事業者のプログラムを利用しています。<br>
                これにより、当該事業者がCookie等を通じてユーザーのアクセス情報を取得する場合があります。<br>
                これらの事業者が取得する情報の取扱いについては、各事業者のプライバシーポリシーをご確認ください。
            </p>
            <ul>
                <li><a href="https://www.amazon.co.jp/gp/help/customer/display.html?nodeId=201909010" target="_blank">Amazon.co.jp プライバシー規約</a></li>
                <li><a href="https://www.dmm.com/help/privacy/" target="_blank">DMM.com プライバシーポリシー</a></li>
            </ul>
            <p>
                また、今後アクセス解析ツール（例：Google Analytics）等を導入する場合には、本ポリシーを改定し、その旨を明記します。
            </p>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">アフィリエイト</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                下記のアフィリエイトプログラムに参加しています。<br>
                当サイト内において、アフィリエイトの画像の表示や、アフィリエイトサイトの商品ページ・商品検索ページへのリンクが設置されています。
            </p>
            <ul>
                <li><a href="https://affiliate.amazon.co.jp/" target="_blank">Amazon.co.jpアソシエイト</a></li>
                <li><a href="https://affiliate.dmm.com/" target="_blank">DMM アフィリエイト</a></li>
            </ul>
        </div>
    </section>


    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">年齢確認</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                18歳以上向けコンテンツ(R-18)の制御のため、18歳以上か未満かの区分情報のみを保存する場合があります。<br>
                <br>
                未登録ユーザーはCookieに区分情報を保存しています。<br>
                登録ユーザーはCookieとデータベースに区分情報を保存しています。
            </p>
            <p class="mb-5">
                また、R-18にも2種類あり、それぞれ以下のように表記しています。
            </p>

            <dl>
                <dt>【R-18Z】</dt>
                <dd>
                    CERO-ZやSteamで成人指定されている、いわゆるZ指定ゲーム。<br>
                    また過激なグロテスク表現のある関連商品や二次創作。<br>
                    年齢設定をしていない場合、画面上に警告文が表示されます。
                </dd>
            </dl>
            <dl>
                <dt>【R-18A】</dt>
                <dd>
                    いわゆるエロゲ。<br>
                    また、性表現がある関連商品や二次創作。<br>
                    年齢設定をしていない場合、パッケージ画像や二次創作が非表示となりレビューや二次創作の投稿が行えません。<br>
                    ただし、全年齢版など表現規制版のパッケージがある場合はレビューや二次創作の投稿は行えます。
                </dd>
            </dl>
        </div>
    </section>


    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">免責事項</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                当サイトで掲載している画像・文章・動画等の著作権は、各権利所有者に帰属します。<br>
                当サイトの掲載内容に関して、できる限り正確な情報を提供するよう努めていますが、その正確性・完全性を保証するものではありません。<br>
                当サイトから他のサイトに移動された場合、移動先サイトで提供されるサービス等について一切の責任を負いません。<br>
                また、当サイトの利用により生じた損害等についても一切の責任を負いかねます。
            </p>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">プライバシーポリシーの改定</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                本ポリシーの内容は、法令の改正や運営方針の変更等により、予告なく改定することがあります。<br>
                重要な変更がある場合は、当サイト上でお知らせします。<br>
                過去の改定は<a href="https://github.com/hucklefriend/hgs_re3/commits/develop/resources/views/privacy_policy.blade.php" target="_blank" rel="external">GitHub</a>のコミット履歴から確認ください。
            </p>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">問い合わせ</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                個人情報の削除や本ポリシーに関するお問い合わせは、当サイト内の<a href="{{ route('Contact') }}" data-hgn-scope="full">問い合わせ</a>フォームよりご連絡ください。<br>
                スパム防止のため、メール等での直接対応は行っておりません。
            </p>
        </div>
    </section>

    @include('common.shortcut')
@endsection
