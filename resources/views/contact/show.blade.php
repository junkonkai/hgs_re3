@extends('layout')

@section('title', '問い合わせ')
@section('current-node-title', '問い合わせ')
@section('current-node-content')
    @if($contact->status->value === 2)
        <div class="alert alert-warning">
            <p>
                ⚠️ 対応完了のお知らせ
            </p>
            <p>
                この問い合わせへの対応は完了したものとしています。<br>
                特に追加の投稿がない場合、本問い合わせは<strong>2週間後に自動でクローズ</strong>され、閲覧できなくなります。<br>
                追加でご連絡したいことがある場合は、下記の返信フォームからご投稿ください。
            </p>
        </div>
    @endif

    <div class="alert alert-info">
        <div style="line-height: 1.6;font-size: 13px;">
            <span style="padding: 3px 5px; background-color: {{ $contact->status->value === 0 ? '#ff9800' : ($contact->status->value === 1 ? '#2196F3' : ($contact->status->value === 2 ? '#4CAF50' : '#999')) }}; color: white; border-radius: 3px;">
                {{ $contact->status->label() }}
            </span>
        </div>
        <p>{{ $contact->category?->label() ?? '-' }}</p>
        <p>
            {!! nl2br(e($contact->masked_message)) !!}
        </p>

        <div style="line-height: 1.6;font-size: 13px;margin-top: 10px;">
            お名前：{{ $contact->name }}<br>
            受付日時：{{ $contact->created_at->format('Y年m月d日 H:i') }}
        </div>
    </div>
@endsection


@section('nodes')
    <section class="node tree-node" id="response-node">
        <div class="node-head">
            <h2 class="node-head-text">返信</h2>
            <span class="node-pt">●</span>
        </div>

        @if($responses->count() === 0)
        <div class="node-content basic" id="response-empty">
            <p>返信はありません。</p>
        </div>
        @endif
        <div class="node-content tree" id="response-node-content">
            @if($responses->count() > 0)
                @foreach($responses as $idx => $response)
                <section class="node" id="response-node-{{ $idx }}">
                    <div class="node-head">
                        <h3 class="node-head-text">
                            {{ $response->responder_name }}
                            {{ $response->created_at->format('Y年m月d日 H:i') }}
                        </h3>
                        <span class="node-pt">●</span>
                    </div>
                    <div class="node-content basic">
                        {!! nl2br(e($response->masked_message)) !!}
                    </div>
                </section>
                @endforeach
            @endif
            <section class="node" id="response-form-node">
                <div class="node-head">
                    <h3 class="node-head-text">返信を投稿</h3>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content basic" id="response-form">
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
                    
                    <form method="POST" action="{{ route('Contact.StoreResponse', ['token' => $contact->token]) }}" data-child-only="1" data-no-push-state="1">
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
                            <p class="alert-secondary">※ 元のお名前がデフォルトで入力されています。</p>
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

    <section class="node tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node tree-node">
                <div class="node-head">
                    <a href="{{ route('Contact') }}" class="node-head-text">問い合わせ</a>
                    <span class="node-pt main-node-pt">●</span>
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
        </div>
    </section>


@endsection

