@extends('admin.layout')

@section('content')
    <div class="panel panel-inverse">
        <div class="panel-heading">
            <h4 class="panel-title">Search</h4>
        </div>
        <div class="panel-body">
            <form action="{{ route('Admin.Manage.Review') }}" method="GET">
                <div class="row mb-3">
                    <label class="form-label col-form-label col-md-3">Keyword</label>
                    <div class="col-md-9">
                        <input type="text" name="keyword" class="form-control w-auto" placeholder="ユーザー名 / タイトル名" value="{{ $search['keyword'] }}">
                    </div>
                </div>
                <div class="row mb-3">
                    <label class="form-label col-form-label col-md-3">Status</label>
                    <div class="col-md-9">
                        <select name="status" class="form-select w-auto">
                            <option value="">All</option>
                            <option value="public"  {{ $search['status'] === 'public'  ? 'selected' : '' }}>公開中</option>
                            <option value="hidden"  {{ $search['status'] === 'hidden'  ? 'selected' : '' }}>非表示</option>
                            <option value="deleted" {{ $search['status'] === 'deleted' ? 'selected' : '' }}>削除済み</option>
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
            <h4 class="panel-title">Reviews</h4>
        </div>
        <div class="panel-body">
            <div>{{ $reviews->appends($search)->links() }}</div>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Status</th>
                    <th>Title</th>
                    <th>ユーザー</th>
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
                        <td>{{ $review->user?->name ?? '-' }}</td>
                        <td>{{ $review->total_score ?? '-' }}</td>
                        <td>{{ $review->created_at?->format('Y-m-d H:i') }}</td>
                        <td class="text-center">
                            <a href="{{ route('Admin.Manage.Review.Show', $review) }}" class="btn btn-default btn-sm">Detail</a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($reviews->isEmpty())
                <div class="alert alert-warning text-center">
                    レビューは見つかりませんでした。
                </div>
            @endif
            <div>{{ $reviews->appends($search)->links() }}</div>
        </div>
    </div>
@endsection
