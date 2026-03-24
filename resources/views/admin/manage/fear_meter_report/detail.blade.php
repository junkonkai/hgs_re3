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
            <h4 class="panel-title">Fear Meter Report #{{ $model->id }}</h4>
        </div>
        <div class="panel-body">
            <table class="table admin-form-table">
                <tr>
                    <th width="220">Status</th>
                    <td>{{ $model->status }}</td>
                </tr>
                <tr>
                    <th>Title</th>
                    <td>
                        @if ($model->fearMeterLog?->gameTitle)
                            <a href="{{ route('Admin.Game.Title.Detail', $model->fearMeterLog->gameTitle) }}">{{ $model->fearMeterLog->gameTitle->name }}</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>投稿者</th>
                    <td>
                        @if ($model->fearMeterLog?->user)
                            <a href="{{ route('Admin.Manage.User.Show', $model->fearMeterLog->user) }}">{{ $model->fearMeterLog->user->name }}</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>通報者</th>
                    <td>
                        @if ($model->reporter)
                            <a href="{{ route('Admin.Manage.User.Show', $model->reporter) }}">{{ $model->reporter->name }}</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>コメント</th>
                    <td>{!! nl2br(e($model->fearMeterLog?->comment ?? '-')) !!}</td>
                </tr>
                <tr>
                    <th>理由</th>
                    <td>{!! nl2br(e($model->reason ?? '-')) !!}</td>
                </tr>
                <tr>
                    <th>通報日時</th>
                    <td>{{ $model->created_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
            </table>

            <hr>
            <form method="POST" action="{{ route('Admin.Manage.FearMeterReport.Status', $model) }}">
                @csrf
                <div class="row mb-2">
                    <div class="col-md-3">ステータス更新</div>
                    <div class="col-md-6">
                        <select name="status" class="form-select">
                            @foreach ($statuses as $status)
                                <option value="{{ $status }}" {{ $model->status === $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-primary">更新</button>
                    </div>
                </div>
            </form>

            <hr>
            @if ($activeRestriction)
                <div class="alert alert-warning">
                    この投稿者は既に怖さメーター入力制限中です。
                </div>
            @else
                <form method="POST" action="{{ route('Admin.Manage.FearMeterReport.RestrictUser', $model) }}">
                    @csrf
                    <input type="hidden" name="source" value="report_threshold">
                    <div class="row mb-2">
                        <div class="col-md-3">入力制限</div>
                        <div class="col-md-6">
                            <input type="text" name="reason" class="form-control" value="通報により入力制限" />
                        </div>
                        <div class="col-md-3">
                            <button type="submit" class="btn btn-danger">投稿者を入力制限</button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>

    <a href="{{ route('Admin.Manage.FearMeterReport') }}" class="btn btn-default">
        一覧に戻る
    </a>
@endsection
