@extends('layout')

@section('title', '問い合わせ完了')
@section('current-node-title', '問い合わせ完了')

@section('nodes')

    <section class="node">
        <div class="node-head">
            <h2 class="node-head-text">送信完了</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <div class="alert alert-warning">
                <p>
                    ⚠️ 重要：必ずURLをメモまたはブックマークしてください ⚠️
                </p>
                <p>
                    返信は現在のページにて行います。<br>
                    このページを閉じると、問い合わせ内容を確認できなくなりますので、<br>
                    必ずURLをメモまたはブックマークして、定期的に確認にお越しください。
                </p>
                <div class="alert alert-info">
                    <p>確認用URL:</p>
                    <p style="word-break: break-all;">
                        {{ route('Contact.Show', ['token' => $contact->token]) }}
                    </p>
                </div>
            </div>
        </div>
    </section>

    <section class="node tree-node" id="contact-content-node">
        <div class="node-head">
            <h2 class="node-head-text">問い合わせ内容</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic" id="contact-content">
            <div class="alert alert-info">
                <p>{{ $contact->category?->label() ?? '-' }}</p>
                <p>
                    {!! nl2br(e($contact->masked_message)) !!}
                </p>
            
                <div>
                    お名前：{{ $contact->name }}<br>
                    受付日時：{{ $contact->created_at->format('Y年m月d日 H:i') }}
                </div>
            </div>
        </div>
        <div class="node-content tree" id="response-node-content">
            <section class="node" id="response-form-node">
                <div class="node-head">
                    <h3 class="node-head-text">返信を投稿</h3>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content basic" id="response-form">
                    <div class="alert alert-info">
                        <strong>💡 個人情報保護機能について</strong>
                        <p>
                            メールアドレスや電話番号などの個人情報は、<code>/*</code>と<code>*/</code>で囲むことで、管理者にのみ表示され、確認画面では<strong>■で伏せ字</strong>として表示されます。
                        </p>
                    </div>
                    
                    <form method="POST" action="{{ route('Contact.StoreResponse', ['token' => $contact->token]) }}" data-child-only="0" data-no-push-state="1">
                        @csrf
        
                        <div class="form-group">
                            <label for="responder_name">
                                お名前 <span class="required">*</span>
                            </label>
                            <input 
                                type="text" 
                                id="responder_name" 
                                name="responder_name" 
                                value="{{ old('responder_name', $contact->name) }}" 
                                required
                            >
                            <p class="alert-secondary">※ 元の問い合わせ時のお名前がデフォルトで入力されています。</p>
                        </div>
        
                        <div class="form-group">
                            <label for="message">
                                返信内容 <span class="required">*</span>
                            </label>
                            <textarea 
                                id="message" 
                                name="message" 
                                rows="8" 
                                required
                            >{{ old('message') }}</textarea>
                        </div>
        
                        <button type="submit" class="btn btn-success">返信を投稿</button>
                    </form>
                </div>
            </section>

            @if($contact->status->value === 0)
                <section class="node" id="cancel-node">
                    <div class="node-head">
                        <h2 class="node-head-text">取り消し</h2>
                        <span class="node-pt">●</span>
                    </div>
                    <div class="node-content basic">
                        <p style="margin-bottom: 20px;">
                            未対応の問い合わせは取り消すことができます。<br>
                            取り消すと、この問い合わせは表示されなくなります。  
                        </p>
                        <form method="POST" action="{{ route('Contact.Cancel', ['token' => $contact->token]) }}" data-child-only="0" data-no-push-state="1">
                            @csrf
                            <button type="submit" class="btn btn-danger" onclick="return confirm('本当に取り消していいですか？');">問い合わせを取り消す</button>
                        </form>
                    </div>
                </section>
            @endif
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
        </div>
    </section>
@endsection

