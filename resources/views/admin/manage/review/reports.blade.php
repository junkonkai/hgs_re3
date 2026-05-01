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
            <h4 class="panel-title">通報一覧 — Review #{{ $review->id }}</h4>
        </div>
        <div class="panel-body">
            <table class="table admin-form-table mb-0">
                <tr>
                    <th width="220">Title</th>
                    <td>
                        @if ($review->gameTitle)
                            <a href="{{ route('Admin.Game.Title.Detail', $review->gameTitle) }}">{{ $review->gameTitle->name }}</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>ユーザー</th>
                    <td>
                        @if ($review->user)
                            <a href="{{ route('Admin.Manage.User.Show', $review->user) }}">{{ $review->user->name }}</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>レビュー状態</th>
                    <td>
                        @if ($review->is_deleted)
                            <span class="badge bg-secondary">削除済み</span>
                        @elseif ($review->is_hidden)
                            <span class="badge bg-warning text-dark">非表示</span>
                        @else
                            <span class="badge bg-success">公開中</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="panel panel-inverse">
        <div class="panel-heading">
            <h4 class="panel-title">通報 ({{ $review->reports->count() }}件)</h4>
        </div>
        <div class="panel-body">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>通報者</th>
                    <th>理由</th>
                    <th>通報日時</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($review->reports as $report)
                    <tr>
                        <td>{{ $report->id }}</td>
                        <td>{{ $report->is_resolved ? '解決済み' : '未解決' }}</td>
                        <td>
                            @if ($report->user)
                                <a href="{{ route('Admin.Manage.User.Show', $report->user) }}">{{ $report->user->name }}</a>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $report->reason ?? '-' }}</td>
                        <td>{{ $report->created_at?->format('Y-m-d H:i') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($review->reports->isEmpty())
                <div class="alert alert-warning text-center">
                    通報は見つかりませんでした。
                </div>
            @endif

            @if (!$review->is_deleted)
                <hr>
                <form method="POST" action="{{ route('Admin.Manage.Review.ForceDelete', $review) }}">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger"
                            onclick="return confirm('このレビューを強制削除しますか？')">
                        レビューを強制削除
                    </button>
                </form>
            @endif
        </div>
    </div>

    <a href="{{ route('Admin.Manage.Review.Show', $review) }}" class="btn btn-default">
        <i class="fas fa-arrow-left"></i> 詳細に戻る
    </a>
@endsection
