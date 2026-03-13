@extends('layout')

@section('title', 'お知らせ')
@section('current-node-title', $info->head)

@section('current-node-content')
    @if (!empty($info->header_text))
    <p>
        {!! nl2br(e($info->header_text)) !!}
    </p>
    @endif
    <div style="margin-top: 30px;font-size: 13px;">
        公開日時：{{ $info->open_at->format('Y-m-d H:i') }}<br>
        @if($info->close_at->format('Y-m-d H:i') !== '2099-12-31 23:59')公開終了日時：{{ $info->close_at->format('Y-m-d H:i') }}@endif
    </div>
@endsection


@section('nodes')

    @for ($i = 1; $i <= 10; $i++)
        @if (!empty($info->{'sub_title_' . $i}) || !empty($info->{'sub_text_' . $i}))
        <section class="node">
            <div class="node-head">
                <h2 class="node-head-text">{{ $info->{'sub_title_' . $i} }}</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content basic">
                {!! nl2br($info->{'sub_text_' . $i}) !!}
            </div>
        </section>
        @endif
    @endfor

    <section class="node tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node tree-node" id="back-to-informations-node">
                <div class="node-head">
                    <a href="{{ route('Informations') }}" class="node-head-text">お知らせ</a>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content tree">
                    <section class="node basic" id="back-to-root-node">
                        <div class="node-head">
                            <a href="{{ route('Root') }}" class="node-head-text">ルート</a>
                            <span class="node-pt">●</span>
                        </div>
                    </section>
                </div>
            </section>

            @include('common.shortcut_mynode')
        
            @if (is_admin_user())
            <section class="node basic">
                <div class="node-head">
                    <a href="{{ route('Admin.Manage.Information.Show', $info) }}" class="node-head-text" rel="external">管理</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            @endif
        </div>
    </section>
@endsection
