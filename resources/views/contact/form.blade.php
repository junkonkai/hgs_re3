@extends('layout')

@section('title', '問い合わせ | ホラーゲームネットワーク')
@section('current-node-title', '問い合わせ')

@section('nodes')

    <section class="node" id="contact-about-node">
        <div class="node-head">
            <h2 class="node-head-text">問い合わせについて</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <div class="alert alert-warning">
                <p>
                    現在当サイトはテスト運用中の段階です。<br>
                    [個人情報の削除]以外については、対応が遅くなるかもしれません。<br>
                    ご了承ください。<br>
                    <br>
                    なお、当サイトで扱われる個人情報については<a href="{{ route('PrivacyPolicy') }}" data-hgn-scope="full">プライバシーポリシー</a>をご確認ください。
                </p>
            </div>
            
            <div class="alert alert-info">
                <strong>💡 個人情報保護機能について</strong>
                <p>
                    メールアドレスやSNSのIDなどの個人情報を送信したい場合は、<code>/*</code>と<code>*/</code>で囲んでください。<br>
                    囲まれた部分は管理者にのみ表示され、確認画面では<strong>■で伏せ字</strong>として表示されます。
                </p>
                <p class="alert-secondary">
                    <strong>例：</strong> 私のメールアドレスは /*example@example.com*/ です。<br>
                    → 確認画面では「私のメールアドレスは <strong>■■■■■■■■■■■■■■■■■■■■■</strong> です。」と表示されます。
                </p>
            </div>
        </div>
    </section>
    <section class="node" id="contact-form-node">
        <div class="node-head">
            <h2 class="node-head-text">問い合わせフォーム</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul style="margin: 0; padding-left: 20px;">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form id="contact-form" method="POST" action="{{ route('SendContact') }}" style="margin-top: 20px;" data-child-only="0">
                @csrf

                <div class="form-group">
                    <label for="name">お名前</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}">
                </div>

                <div class="form-group">
                    <label for="category">
                        カテゴリー
                    </label>
                    <select id="category" name="category">
                        <option value="">選択してください</option>
                        @foreach (\App\Enums\ContactCategory::cases() as $category)
                            <option value="{{ $category->value }}" {{ old('category') == $category->value ? 'selected' : '' }}>{{ $category->label() }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="message">
                        問い合わせ内容 <span class="required">*</span>
                    </label>
                    <textarea id="message" name="message" rows="10" maxlength="10000" placeholder="スパム対策で「ひらがな」または「カタカナ」が含まれていない問い合わせは管理者へは届きませんのでご注意ください。" required>{{ old('message') }}</textarea>
                </div>

                <div style="margin-top: 30px;">
                    <button type="submit" class="btn btn-success">送信</button>
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
            <section class="node basic">
                <div class="node-head">
                    <a href="{{ route('Root') }}" class="node-head-text">ルート</a>
                    <span class="node-pt main-node-pt">●</span>
                </div>
            </section>
            
        
            @if (\Illuminate\Support\Facades\Auth::guard('admin')->check())
            <section class="node basic">
                <div class="node-head">
                    <a href="{{ route('Admin.Manage.Contact') }}" class="node-head-text" rel="external">管理</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            @endif
        </div>
    </section>
@endsection

