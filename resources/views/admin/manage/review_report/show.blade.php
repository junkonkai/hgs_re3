@extends('admin.layout')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <strong>成功!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if (session('warning'))
        <div class="alert alert-warning alert-dismissible fade show">
            <strong>注意!</strong> {{ session('warning') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="panel panel-inverse">
        <div class="panel-heading">
            <h4 class="panel-title">Review Report #{{ $report->id }}</h4>
        </div>
        <div class="panel-body">
            <table class="table admin-form-table">
                <tr>
                    <th width="220">Status</th>
                    <td>{{ $report->is_resolved ? '解決済み' : '未解決' }}</td>
                </tr>
                <tr>
                    <th>Title</th>
                    <td>
                        @if ($report->review?->gameTitle)
                            <a href="{{ route('Admin.Game.Title.Detail', $report->review->gameTitle) }}">{{ $report->review->gameTitle->name }}</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>レビュー投稿者</th>
                    <td>
                        @if ($report->review?->user)
                            <a href="{{ route('Admin.Manage.User.Show', $report->review->user) }}">{{ $report->review->user->name }}</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>通報者</th>
                    <td>
                        @if ($report->user)
                            <a href="{{ route('Admin.Manage.User.Show', $report->user) }}">{{ $report->user->name }}</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>通報理由</th>
                    <td>{!! nl2br(e($report->reason ?? '-')) !!}</td>
                </tr>
                <tr>
                    <th>通報日時</th>
                    <td>{{ $report->created_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>解決日時</th>
                    <td>{{ $report->resolved_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
            </table>

            <hr>
            <h5>通報時のレビュー内容</h5>
            @php
                $logBody = $report->reviewLog?->body;
                $currentBody = $report->review?->body;
            @endphp
            <table class="table admin-form-table">
                <tr>
                    <th width="220">本文（通報時）</th>
                    <td>{!! nl2br(e($logBody ?? $currentBody ?? '-')) !!}</td>
                </tr>
                @if ($report->reviewLog && $logBody !== $currentBody)
                    <tr>
                        <th>本文（現在）</th>
                        <td class="text-muted">{!! nl2br(e($currentBody ?? '-')) !!}</td>
                    </tr>
                @endif
                <tr>
                    <th>レビュー状態</th>
                    <td>
                        @if ($report->review === null)
                            <span class="badge bg-secondary">削除済み（物理）</span>
                        @elseif ($report->review->is_deleted)
                            <span class="badge bg-secondary">削除済み（ソフト）</span>
                        @elseif ($report->review->is_hidden)
                            <span class="badge bg-warning text-dark">非表示</span>
                        @else
                            <span class="badge bg-success">公開中</span>
                        @endif
                    </td>
                </tr>
            </table>

            <hr>
            <form method="POST" action="{{ route('Admin.Manage.ReviewReport.Status', $report) }}">
                @csrf
                <div class="row mb-2">
                    <div class="col-md-3">ステータス更新</div>
                    <div class="col-md-6">
                        <select name="is_resolved" class="form-select">
                            <option value="0" {{ !$report->is_resolved ? 'selected' : '' }}>未解決</option>
                            <option value="1" {{ $report->is_resolved ? 'selected' : '' }}>解決済み</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">更新</button>
                    </div>
                </div>
            </form>

            @if ($report->review !== null && !$report->review->is_deleted)
                <hr>
                @if (!$report->review->is_hidden)
                    <form method="POST" action="{{ route('Admin.Manage.ReviewReport.HideReview', $report) }}">
                        @csrf
                        <div class="row mb-2">
                            <div class="col-md-3">レビューを非表示</div>
                            <div class="col-md-9">
                                <button type="submit" class="btn btn-warning"
                                        onclick="return confirm('このレビューを非表示にしますか？')">
                                    非表示にする
                                </button>
                            </div>
                        </div>
                    </form>
                @endif

                <hr>
                <form method="POST" action="{{ route('Admin.Manage.ReviewReport.DeleteReview', $report) }}">
                    @csrf
                    @method('DELETE')
                    <div class="row mb-2">
                        <div class="col-md-3">レビューを物理削除</div>
                        <div class="col-md-9">
                            <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('このレビューを完全に削除します。この操作は取り消せません。よろしいですか？')">
                                完全削除
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <a href="{{ route('Admin.Manage.ReviewReport') }}" class="btn btn-default">
        一覧に戻る
    </a>
@endsection
