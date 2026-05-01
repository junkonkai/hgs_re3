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
            <h4 class="panel-title">通報一覧</h4>
        </div>
        <div class="panel-body">
            <table class="table admin-form-table mb-0">
                <tr>
                    <th width="220">Title</th>
                    <td><a href="{{ route('Admin.Game.Title.Detail', $gameTitle) }}">{{ $gameTitle->name }}</a></td>
                </tr>
                <tr>
                    <th>ユーザー</th>
                    <td><a href="{{ route('Admin.Manage.User.Show', $user) }}">{{ $user->name }}</a></td>
                </tr>
                <tr>
                    <th>怖さメーター</th>
                    <td>{{ $fearMeter->fear_meter?->text() ?? '-' }}</td>
                </tr>
            </table>
        </div>
    </div>

    @forelse ($logs as $log)
        <div class="panel panel-inverse">
            <div class="panel-heading">
                <h4 class="panel-title">
                    コメントログ #{{ $log->id }}
                    @if ($log->is_deleted)
                        <span class="badge bg-secondary ms-2">削除済み</span>
                    @endif
                </h4>
            </div>
            <div class="panel-body">
                <table class="table admin-form-table">
                    <tr>
                        <th width="220">コメント</th>
                        <td>{!! nl2br(e($log->comment ?? '-')) !!}</td>
                    </tr>
                    <tr>
                        <th>怖さメーター値</th>
                        <td>
                            @php $newEnum = $log->new_fear_meter !== null ? \App\Enums\FearMeter::tryFrom($log->new_fear_meter) : null; @endphp
                            {{ $newEnum?->text() ?? '-' }}
                        </td>
                    </tr>
                    <tr>
                        <th>投稿日時</th>
                        <td>{{ $log->created_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                    </tr>
                </table>

                <h6 class="mt-3 mb-2">通報 ({{ $log->reports->count() }}件)</h6>
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
                    @foreach ($log->reports as $report)
                        <tr>
                            <td>{{ $report->id }}</td>
                            <td>{{ $report->status }}</td>
                            <td>
                                @if ($report->reporter)
                                    <a href="{{ route('Admin.Manage.User.Show', $report->reporter) }}">{{ $report->reporter->name }}</a>
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

                @if (!$log->is_deleted)
                    <form method="POST" action="{{ route('Admin.Manage.FearMeter.DeleteLog', [$user->id, $gameTitle->id, $log->id]) }}">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger btn-sm"
                                onclick="return confirm('このコメントを削除しますか？')">
                            コメントを削除
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @empty
        <div class="alert alert-warning text-center">
            通報は見つかりませんでした。
        </div>
    @endforelse

    <a href="{{ route('Admin.Manage.FearMeter.Show', [$user->id, $gameTitle->id]) }}" class="btn btn-default">
        <i class="fas fa-arrow-left"></i> 詳細に戻る
    </a>
@endsection
