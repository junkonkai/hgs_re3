@extends('layout')

@section('title', $title->name . ' 怖さコメント')
@section('current-node-title', $title->name . ' 怖さコメント')

@section('current-node-content')
    @if (session('success'))
        <div class="alert alert-success mt-3">
            {!! nl2br(e(session('success'))) !!}
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning mt-3">
            {!! nl2br(e(session('warning'))) !!}
        </div>
    @endif
@endsection

@section('nodes')
    <section class="node" id="title-fear-meter-comment-log-node">
        <div class="node-head">
            <h2 class="node-head-text">コメント</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            @if ($commentLogs->isEmpty())
                <p>コメントの投稿はまだないようだ。</p>
            @else
                <div>
                    @foreach ($commentLogs as $commentLog)
                        <div class="py-3 border-b border-white/10 last:border-b-0">
                            <div class="mb-1 text-xs text-slate-400">
                                {{ $commentLog->created_at }} / {{ $commentLog->user?->name ?? '(不明)' }}
                            </div>
                            <div class="mb-2 text-sm text-slate-200">
                                @php
                                    $fearMeter = \App\Enums\FearMeter::tryFrom((int) $commentLog->new_fear_meter);
                                @endphp
                                <strong>怖さ:</strong> {{ $fearMeter?->text() ?? '-' }}
                            </div>
                            <div class="text-sm leading-relaxed text-slate-100">{!! nl2br(e($commentLog->comment)) !!}</div>
                            <div class="mt-2 text-sm flex items-center gap-6">
                                @if (Auth::check())
                                    <form method="POST" action="{{ route('Game.TitleFearMeterComments.Like', ['titleKey' => $title->key, 'logId' => $commentLog->id]) }}" class="fear-meter-reaction-form inline-flex items-center mb-0" data-component-use="1" data-reaction-kind="like" data-done="{{ in_array($commentLog->id, $likedLogIds, true) ? '1' : '0' }}" data-like-url="{{ route('Game.TitleFearMeterComments.Like', ['titleKey' => $title->key, 'logId' => $commentLog->id]) }}" data-unlike-url="{{ route('Game.TitleFearMeterComments.UnlikePost', ['titleKey' => $title->key, 'logId' => $commentLog->id]) }}">
                                        @csrf
                                        <button type="submit" class="inline-flex h-6 items-center gap-1 leading-none text-slate-400 transition-colors hover:text-sky-400" title="いいね">
                                            <i class="bi bi-hand-thumbs-up"></i> <span class="js-like-count">{{ $commentLog->likes_count }}</span>
                                        </button>
                                    </form>
                                @else
                                    <span class="inline-flex h-6 items-center gap-1 leading-none text-slate-400">
                                        <i class="bi bi-hand-thumbs-up"></i> {{ $commentLog->likes_count }}
                                    </span>
                                @endif

                                <div>
                                    @if (Auth::check())
                                        @if (in_array($commentLog->id, $reportedLogIds, true))
                                            <span class="inline-flex h-6 items-center gap-1 leading-none text-slate-500" title="通報済み">
                                                <i class="bi bi-flag-fill"></i> 通報済み
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('Game.TitleFearMeterComments.Report', ['titleKey' => $title->key, 'logId' => $commentLog->id]) }}" class="fear-meter-reaction-form inline-flex items-center mb-0" data-component-use="1" data-reaction-kind="report" data-done="0">
                                                @csrf
                                                <button type="submit" class="inline-flex h-6 items-center gap-1 leading-none text-slate-400 transition-colors hover:text-rose-400" title="通報">
                                                    <i class="bi bi-flag"></i> 通報
                                                </button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="inline-flex h-6 items-center gap-1 leading-none text-slate-500" title="通報">
                                            <i class="bi bi-flag"></i> 通報
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-3">
                    {{ $commentLogs->appends(request()->query())->links('user.fear_meter.pagination') }}
                </div>
            @endif
        </div>
    </section>

    <section class="node tree-node" id="footer-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node tree-node" id="title-link-node">
                <div class="node-head">
                    <a href="{{ route('Game.TitleDetail', ['titleKey' => $title->key]) }}" class="node-head-text">{{ $title->name }}</a>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content tree">
                    <section class="node tree-node" id="franchise-link-node">
                        <div class="node-head">
                            <a href="{{ route('Game.FranchiseDetail', ['franchiseKey' => $franchise->key]) }}" class="node-head-text">{{ $franchise->name }}フランチャイズ</a>
                            <span class="node-pt">●</span>
                        </div>
                        <div class="node-content tree">
                            <section class="node basic" id="root-link-node">
                                <div class="node-head">
                                    <a href="{{ route('Root') }}" class="node-head-text">ルート</a>
                                    <span class="node-pt">●</span>
                                </div>
                            </section>
                        </div>
                    </section>
                </div>
            </section>
        </div>
    </section>
@endsection
