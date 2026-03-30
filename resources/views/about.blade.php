@extends('layout')

@section('title', 'このサイトについて')
@section('current-node-title', 'このサイトについて')

@section('nodes')

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">ホラーゲームネットワークとは？</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                ホラーゲーム好きのためのコミュニティサイトです。<br>
                レビューや二次創作など、みなさんの「好き」を共有し、より深くホラーゲームを楽しんでほしい（自分も楽しみたい）という想いで運営しています。
            </p>
            <p>
                個人で運営しているため、できることが限られています。<br>
                ご理解頂ける方のみ利用をお願いします。
            </p>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">ホラーゲームの定義</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                これはとても難しい問題です。<br>
                ホラーゲームとは"怖さ"を楽しむゲームです。<br>
                ですが怖さの感じ方は人それぞれ。<br>
                「あんなゲーム、ホラーゲームじゃない」なんて意見もあるでしょう。<br>
                そこで当サイトでは、以下の条件を満たすゲームをホラーゲームとして扱っています。
            </p>
            <ul>
                <li>メーカーが「ホラーゲーム」として販売している</li>
                <li>プレイアブルキャラクターがずっと怖がっている</li>
                <li><a href="https://vndb.org/" target="_blank">vndb</a>でhorrorタグが付いている</li>
            </ul>
            <p>
                例えば、「ディノクライシス」シリーズは1のみホラーゲームとして販売されていますが、2以降はアクションゲームとして販売されています。<br>
                そのため、1のみ当サイトで扱っています。<br>
                また、恐怖演出がほとんどない「ルイージマンション」はプレイアブルキャラクターであるルイージがずっと怖がっているので当サイトで扱っています。<br>
                カジュアルなホラーゲームや、ホラーコメディも当サイトでは取り扱います。<br>
                <br>
                ゾンビや悪魔といったホラー的なものが出るものの、上記の定義に当てはまらないため取り扱っていないゲームもあります。<br>
                「デッドライジング」や「デビルメイクライ」などがそれに当たります。
            </p>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">用語</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <dl>
                <dt>【フランチャイズ】</dt>
                <dd>
                    1つの世界をまとめたグループのことを「フランチャイズ」と呼んでいます。<br>
                    マーベルの「アベンジャーズ」でいうと、「アベンジャーズ」がフランチャイズです。<br>
                    「アベンジャーズ」の中には「アイアンマン」や「キャプテン・アメリカ」といった個別の作品があります。
                </dd>
            </dl>
            <dl>
                <dt>【タイトル】</dt>
                <dd>
                    1つのゲームソフトのことを「タイトル」と呼んでいます。<br>
                    「アベンジャーズ」フランチャイズでいうと、「アイアンマン」や「キャプテン・アメリカ」といった個別の作品がタイトルになります。<br>
                </dd>
            </dl>
            <dl>
                <dt>【シリーズ】</dt>
                <dd>
                    フランチャイズとタイトルの間にある、中間グループのことをシリーズと呼んでいます。<br>
                    「アイアンマン」シリーズには「アイアンマン」「アイアンマン2」「アイアンマン3」の3タイトルがあります。
                </dd>
            </dl>
            <dl>
                <dt>【パッケージ】</dt>
                <dd>
                    ゲームタイトルの販売形態単位のことを「パッケージ」と呼んでいます。<br>
                    「アイアンマン」はDVD・ブルーレイ・各種配信サイトでの配信などで販売されています。<br>
                    それぞれ1つを1パッケージと呼んでいます。
                </dd>
            </dl>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">リメイクと移植とリマスター</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                リメイクは別のタイトルとして扱います。<br>
                移植やリマスターは同一タイトルとして扱います。<br>
                <br>
                "零～紅い蝶～"を例に紹介します。<br>
                "零～紅い蝶～"はPS2版以外にも、XBOX移植版の"FATAL FRAME2"というパッケージがあります。<br>
                "FATAL FRAME2"は移植なので、"零～紅い蝶～"タイトルのパッケージの1つであり、同じタイトルとして扱います。<br>
                "零～真紅の蝶～"及び"零～紅い蝶～ REMAKE"はリメイクなので、別のタイトルとして扱います。
            </p>
            <p>
                もう一つ、"BIOHAZARD"を例に紹介します。<br>
                初代"BIOHAZARD"と、そのリメイク"biohazard"は別のタイトルとして扱います。<br>
                リマスターの"biohazard HDリマスター"は"biohazard"タイトルに含まれる1つのパッケージとして扱います。
            </p>
        </div>
    </section>


    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">サイトの用語</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                当サイトはツリー構造的なデザインを採用しており、それに合わせて独特な表現をしています。
            </p>

            <dl>
                <dt>【ルート】</dt>
                <dd>
                    一般的なWEBサイトのトップページのことを指します。
                </dd>
            </dl>

            <dl>
                <dt>【ノード】</dt>
                <dd>
                    ツリー構造における1つの「節」のことです。<br>
                    それぞれのページや、左側の緑の線で枝分かれしている1つのコンテンツのことをノードと呼称しています。<br>
                </dd>
            </dl>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">問い合わせ先</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                <a href="{{ route('Contact') }}" data-hgn-scope="full">問い合わせ</a>機能を使ってください。
            </p>
            <p>
                個人情報の削除のみ、なるべく急ぎで対応します。<br>
                他の問い合わせについては、対応が遅くなるかもしれません。<br>
                ご理解頂ける方のみ利用をお願いします。
            </p>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">生成AIの利用</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <dl>
                <dt>【画像生成】</dt>
                <dd>
                    下記の画像はChatGPTを使って生成しました。
                    <ul>
                        <li><a href="{{ asset('img/ai/favicon.png') }}" target="_blank">ファビコン</a></li>
                    </ul>
                </dd>
            </dl>

            <dl>
                <dt>【文章生成】</dt>
                <dd>
                    タイトル、プラットフォームやフランチャイズ等の紹介文は生成AIによる文章が一部あります<br>
                    その場合は引用元に生成AIの名称を記載しています。
                </dd>
            </dl>

            <dl>
                <dt>【プログラム生成】</dt>
                <dd>
                    当サイトのプログラムの一部はCursor・ChatGPT・Claudeによるコード生成です。<br>
                    生成AIが生成した部分を示すものは残していません。<br>
                    当初、生成AIによるコードは全体の50%くらいでしたが、2026年以降の実装はほぼバイブコーディングとなっており、AI実装割合は日に日に増えています。
                </dd>
            </dl>

            <dl>
                <dt>【サーバー構築】</dt>
                <dd>
                    サーバー構築時の不明点の調査などでChatGPT・Claudeに質問し問題解決のサポートをしてもらっています。
                </dd>
            </dl>

            <dl>
                <dt>【ホラーゲームの情報収集】</dt>
                <dd>
                    ホラーゲームの情報収集のため、ChatGPT・Claude・Grok・Geminiを利用しています。
                </dd>
            </dl>
        </div>
    </section>

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">利用画像</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <p>
                下記の素材サイト様から、素材を利用させて頂いてます。
            </p>
            <ul>
                <li><a href="https://icon-pit.com/" target="_blank">icon-pit</a></li>
            </ul>
        </div>
    </section>

    @include('common.shortcut')
@endsection
