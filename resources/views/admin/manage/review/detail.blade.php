@extends('admin.layout')

@section('content')
    <div class="panel panel-inverse">
        <div class="panel-heading">
            <h4 class="panel-title">Review #{{ $review->id }}</h4>
        </div>
        <div class="panel-body">
            <table class="table admin-form-table">
                <tr>
                    <th width="220">ID</th>
                    <td>{{ $review->id }}</td>
                </tr>
                <tr>
                    <th>Status</th>
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
                <tr>
                    <th>Title</th>
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
                    <th>プレイ状況</th>
                    <td>{{ $review->play_status?->text() ?? '-' }}</td>
                </tr>
                <tr>
                    <th>ネタバレ</th>
                    <td>{{ $review->has_spoiler ? 'あり' : 'なし' }}</td>
                </tr>
                <tr>
                    <th>総合スコア</th>
                    <td>{{ $review->total_score ?? '-' }}</td>
                </tr>
                <tr>
                    <th>ベーススコア</th>
                    <td>{{ $review->base_score ?? '-' }}</td>
                </tr>
                <tr>
                    <th>ストーリー</th>
                    <td>{{ $review->score_story ?? '-' }}</td>
                </tr>
                <tr>
                    <th>雰囲気</th>
                    <td>{{ $review->score_atmosphere ?? '-' }}</td>
                </tr>
                <tr>
                    <th>ゲーム性</th>
                    <td>{{ $review->score_gameplay ?? '-' }}</td>
                </tr>
                <tr>
                    <th>さじ加減</th>
                    <td>{{ $review->user_score_adjustment ?? '-' }}</td>
                </tr>
                <tr>
                    <th>いいね数</th>
                    <td>{{ $review->likes->count() }}</td>
                </tr>
                <tr>
                    <th>通報数</th>
                    <td>
                        @if ($review->reports->count() > 0)
                            <a href="{{ route('Admin.Manage.Review.Reports', $review) }}">{{ $review->reports->count() }}</a>
                        @else
                            0
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>プレイ環境</th>
                    <td>
                        @if ($review->packages->isNotEmpty())
                            @foreach ($review->packages as $pkg)
                                <div>{{ $pkg->gamePackage?->name ?? '(ID: ' . $pkg->game_package_id . ')' }}</div>
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>怖さメーター</th>
                    <td>
                        @if ($fearMeter)
                            <a href="{{ route('Admin.Manage.FearMeter.Show', [$review->user_id, $review->game_title_id]) }}">詳細を見る</a>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>非表示日時</th>
                    <td>{{ $review->hidden_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>投稿日時</th>
                    <td>{{ $review->created_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>更新日時</th>
                    <td>{{ $review->updated_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
            </table>

            @if ($review->body !== null)
                <hr>
                <h5>本文</h5>
                <div class="p-3 bg-light border rounded">
                    {!! nl2br(e($review->body)) !!}
                </div>
            @endif
        </div>
    </div>

    <a href="{{ $backUrl ?? route('Admin.Manage.Review') }}" class="btn btn-default">
        <i class="fas fa-arrow-left"></i> 一覧に戻る
    </a>
@endsection
