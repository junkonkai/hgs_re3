@extends('admin.layout')

@section('content')
    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <strong>成功!</strong> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="panel panel-inverse">
        <div class="panel-heading">
            <h4 class="panel-title">Search</h4>
        </div>
        <div class="panel-body">
            <form action="{{ route('Admin.Manage.ReviewReport') }}" method="GET">
                <div class="row mb-3">
                    <label class="form-label col-form-label col-md-3">Status</label>
                    <div class="col-md-9">
                        <select name="is_resolved" class="form-select w-auto">
                            <option value="">All</option>
                            <option value="0" {{ $search['is_resolved'] === '0' ? 'selected' : '' }}>未解決</option>
                            <option value="1" {{ $search['is_resolved'] === '1' ? 'selected' : '' }}>解決済み</option>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-7 offset-md-3">
                        <button type="submit" class="btn btn-sm btn-primary w-100px me-5px">Search</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="panel panel-inverse">
        <div class="panel-heading">
            <h4 class="panel-title">Review Reports</h4>
        </div>
        <div class="panel-body">
            <div>{{ $reports->appends($search)->links() }}</div>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Title</th>
                    <th>レビュー投稿者</th>
                    <th>通報者</th>
                    <th>通報日時</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($reports as $report)
                    <tr>
                        <td>{{ $report->id }}</td>
                        <td>{{ $report->is_resolved ? '解決済み' : '未解決' }}</td>
                        <td>{{ $report->review?->gameTitle?->name ?? '-' }}</td>
                        <td>{{ $report->review?->user?->name ?? '-' }}</td>
                        <td>{{ $report->user?->name ?? '-' }}</td>
                        <td>{{ $report->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-center">
                            <a href="{{ route('Admin.Manage.ReviewReport.Show', $report) }}" class="btn btn-default btn-sm">Detail</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($reports->isEmpty())
                <div class="alert alert-warning text-center">
                    通報は見つかりませんでした。
                </div>
            @endif
            <div>{{ $reports->appends($search)->links() }}</div>
        </div>
    </div>
@endsection
