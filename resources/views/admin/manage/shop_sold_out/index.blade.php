@extends('admin.layout')

@section('content')
    <div class="panel panel-inverse">
        <div class="panel-heading">
            <h4 class="panel-title">Shop Sold Out Links</h4>
        </div>
        <div class="panel-body">
            <div>{{ $results->links() }}</div>
            <table class="table table-hover">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>種別</th>
                    <th>Source ID</th>
                    <th>ショップ</th>
                    <th>判定理由</th>
                    <th>マッチキーワード</th>
                    <th>URL</th>
                    <th>初検出日時</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @foreach ($results as $result)
                    @php $shop = \App\Enums\Shop::tryFrom($result->shop_id); @endphp
                    <tr>
                        <td>{{ $result->id }}</td>
                        <td>
                            @if ($result->source_table === 'game_package_shops')
                                <span class="badge bg-primary">パッケージ</span>
                            @else
                                <span class="badge bg-secondary">関連商品</span>
                            @endif
                        </td>
                        <td>{{ $result->source_id }}</td>
                        <td>{{ $shop?->name() ?? $result->shop_id }}</td>
                        <td>
                            @if ($result->reason === '404')
                                <span class="badge bg-danger">404</span>
                            @else
                                <span class="badge bg-warning text-dark">キーワード</span>
                            @endif
                        </td>
                        <td>{{ $result->matched_keyword ?? '-' }}</td>
                        <td>
                            <a href="{{ $result->url }}" target="_blank" class="text-break">{{ $result->url }}</a>
                        </td>
                        <td>{{ $result->detected_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            <form method="POST" action="{{ route('Admin.Manage.ShopSoldOut.Destroy', $result) }}"
                                  onsubmit="return confirm('元データ（ショップリンク）と判定結果を削除します。よろしいですか？')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm">削除</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            @if ($results->isEmpty())
                <div class="alert alert-info text-center">
                    販売終了と判定されたリンクはありません。
                </div>
            @endif
            <div>{{ $results->links() }}</div>
        </div>
    </div>
@endsection
