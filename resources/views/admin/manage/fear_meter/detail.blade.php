@extends('admin.layout')

@section('content')
    <div class="panel panel-inverse">
        <div class="panel-heading">
            <h4 class="panel-title">Fear Meter</h4>
        </div>
        <div class="panel-body">
            <table class="table admin-form-table">
                <tr>
                    <th width="220">Title</th>
                    <td>
                        <a href="{{ route('Admin.Game.Title.Detail', $gameTitle) }}">{{ $gameTitle->name }}</a>
                    </td>
                </tr>
                <tr>
                    <th>ユーザー</th>
                    <td>
                        <a href="{{ route('Admin.Manage.User.Show', $user) }}">{{ $user->name }}</a>
                    </td>
                </tr>
                <tr>
                    <th>怖さメーター</th>
                    <td>{{ $fearMeter->fear_meter?->text() ?? '-' }}</td>
                </tr>
                <tr>
                    <th>更新日時</th>
                    <td>{{ $fearMeter->updated_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>投稿日時</th>
                    <td>{{ $fearMeter->created_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>レビュー</th>
                    <td>
                        @if ($review)
                            <a href="{{ route('Admin.Manage.Review.Show', $review) }}">詳細を見る</a>
                        @else
                            レビュー未投稿
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>通報数</th>
                    <td>
                        @if ($reportsCount > 0)
                            <a href="{{ route('Admin.Manage.FearMeter.Reports', [$user->id, $gameTitle->id]) }}">{{ $reportsCount }}</a>
                        @else
                            0
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="panel panel-inverse">
        <div class="panel-heading">
            <h4 class="panel-title">更新ログ</h4>
        </div>
        <div class="panel-body">
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Action</th>
                    <th>変更前</th>
                    <th>変更後</th>
                    <th>コメント</th>
                    <th>削除済み</th>
                    <th>日時</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($logs as $log)
                    <tr>
                        <td>{{ $log->id }}</td>
                        <td>{{ $log->action }}</td>
                        <td>
                            @php $oldEnum = $log->old_fear_meter !== null ? \App\Enums\FearMeter::tryFrom($log->old_fear_meter) : null; @endphp
                            {{ $oldEnum?->text() ?? '-' }}
                        </td>
                        <td>
                            @php $newEnum = $log->new_fear_meter !== null ? \App\Enums\FearMeter::tryFrom($log->new_fear_meter) : null; @endphp
                            {{ $newEnum?->text() ?? '-' }}
                        </td>
                        <td>{{ $log->comment ?? '-' }}</td>
                        <td>
                            @if ($log->is_deleted)
                                <span class="badge bg-secondary">削除済み</span>
                            @else
                                -
                            @endif
                        </td>
                        <td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($logs->isEmpty())
                <div class="alert alert-warning text-center">
                    ログは見つかりませんでした。
                </div>
            @endif
        </div>
    </div>

    <a href="{{ route('Admin.Manage.FearMeter') }}" class="btn btn-default">
        <i class="fas fa-arrow-left"></i> 一覧に戻る
    </a>
@endsection
