@extends('admin.layout')

@section('content')
    <div class="panel panel-inverse">
        <div class="panel-heading">
            <h4 class="panel-title">Search</h4>
        </div>
        <div class="panel-body">
            <form action="{{ route('Admin.Manage.FearMeter') }}" method="GET">
                <div class="row mb-3">
                    <label class="form-label col-form-label col-md-3">Keyword</label>
                    <div class="col-md-9">
                        <input type="text" name="keyword" class="form-control w-auto" placeholder="ユーザー名 / タイトル名" value="{{ $search['keyword'] }}">
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
            <h4 class="panel-title">Fear Meters</h4>
        </div>
        <div class="panel-body">
            <div>{{ $fearMeters->appends($search)->links() }}</div>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>ユーザー</th>
                    <th>怖さメーター</th>
                    <th>通報数</th>
                    <th>更新日時</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($fearMeters as $fearMeter)
                    <tr>
                        <td>{{ $fearMeter->gameTitle?->name ?? '-' }}</td>
                        <td>{{ $fearMeter->user?->name ?? '-' }}</td>
                        <td>{{ $fearMeter->fear_meter?->text() ?? '-' }}</td>
                        <td>
                            @if ($fearMeter->reports_count > 0)
                                <a href="{{ route('Admin.Manage.FearMeter.Reports', [$fearMeter->user_id, $fearMeter->game_title_id]) }}">{{ $fearMeter->reports_count }}</a>
                            @else
                                0
                            @endif
                        </td>
                        <td>{{ $fearMeter->updated_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-center">
                            @if ($fearMeter->user && $fearMeter->gameTitle)
                                <a href="{{ route('Admin.Manage.FearMeter.Show', [$fearMeter->user_id, $fearMeter->game_title_id]) }}" class="btn btn-default btn-sm">Detail</a>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($fearMeters->isEmpty())
                <div class="alert alert-warning text-center">
                    怖さメーターは見つかりませんでした。
                </div>
            @endif
            <div>{{ $fearMeters->appends($search)->links() }}</div>
        </div>
    </div>
@endsection
