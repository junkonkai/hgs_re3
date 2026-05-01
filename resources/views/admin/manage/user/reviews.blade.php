@extends('admin.layout')

@section('content')
    <div class="panel panel-inverse">
        <div class="panel-heading">
            <h4 class="panel-title">{{ $user->name }} のレビュー一覧</h4>
        </div>
        <div class="panel-body">
            <div>{{ $reviews->links() }}</div>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>タイトル</th>
                    <th>総合スコア</th>
                    <th>投稿日時</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($reviews as $review)
                    <tr>
                        <td>{{ $review->id }}</td>
                        <td>
                            @if ($review->is_deleted)
                                <span class="badge bg-secondary">削除済み</span>
                            @elseif ($review->is_hidden)
                                <span class="badge bg-warning text-dark">非表示</span>
                            @else
                                <span class="badge bg-success">公開中</span>
                            @endif
                        </td>
                        <td>{{ $review->gameTitle?->name ?? '-' }}</td>
                        <td>{{ $review->total_score ?? '-' }}</td>
                        <td>{{ $review->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-center">
                            <a href="{{ route('Admin.Manage.User.Reviews.Show', [$user, $review]) }}" class="btn btn-default btn-sm">Detail</a>
                        </td>
                    </tr>
                @endforeach
                @foreach ($drafts as $draft)
                    <tr>
                        <td>-</td>
                        <td><span class="badge bg-info">下書き</span></td>
                        <td>{{ $draft->gameTitle?->name ?? '-' }}</td>
                        <td>-</td>
                        <td>{{ $draft->created_at?->format('Y-m-d H:i') }}</td>
                        <td></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($reviews->isEmpty() && $drafts->isEmpty())
                <div class="alert alert-warning text-center">
                    レビューは見つかりませんでした。
                </div>
            @endif
            <div>{{ $reviews->links() }}</div>
        </div>
    </div>

    <a href="{{ route('Admin.Manage.User.Show', $user) }}" class="btn btn-default">
        <i class="fas fa-arrow-left"></i> ユーザー詳細に戻る
    </a>
@endsection
