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
            <h4 class="panel-title">ユーザー詳細 #{{ $model->id }}</h4>
        </div>
        <div class="panel-body">
            <table class="table admin-form-table">
                <tr>
                    <th width="220">ID</th>
                    <td>{{ $model->id }}</td>
                </tr>
                <tr>
                    <th>Show ID</th>
                    <td><code>{{ $model->show_id ?? '-' }}</code></td>
                </tr>
                <tr>
                    <th>Name</th>
                    <td>{{ $model->name }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $model->email }}</td>
                </tr>
                <tr>
                    <th>Role</th>
                    <td>
                        @php
                            $role = $model->role instanceof \App\Enums\UserRole
                                ? $model->role
                                : \App\Enums\UserRole::tryFrom($model->role);
                        @endphp
                        {{ $role?->label() ?? '-' }}
                    </td>
                </tr>
                <tr>
                    <th>Password</th>
                    <td>
                        ***** 
                        <a href="{{ route('Admin.Manage.User.Password', $model) }}" class="btn btn-warning btn-sm ms-3">
                            <i class="fas fa-key"></i> 変更
                        </a>
                    </td>
                </tr>
                <tr>
                    <th>HGS12 User</th>
                    <td>
                        @if ($model->hgs12_user)
                            <span class="badge bg-info">Yes</span>
                        @else
                            <span class="badge bg-secondary">No</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Email Verification Sent At</th>
                    <td>{{ $model->email_verification_sent_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Email Verification Token</th>
                    <td>
                        @if ($model->email_verification_token)
                            <code>{{ $model->email_verification_token }}</code>
                        @else
                            -
                        @endif
                    </td>
                </tr>
                <tr>
                    <th>Withdrawn At</th>
                    <td>{{ $model->withdrawn_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Sign Up At</th>
                    <td>{{ $model->sign_up_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>Updated At</th>
                    <td>{{ $model->updated_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                </tr>
                <tr>
                    <th>レビュー数</th>
                    <td>
                        <a href="{{ route('Admin.Manage.User.Reviews', $model) }}">{{ $reviewCount }} 件</a>
                    </td>
                </tr>
                <tr>
                    <th>Fear Meter Restriction</th>
                    <td>
                        @if ($activeFearMeterRestriction)
                            <span class="badge bg-danger">制限中</span>
                            <div class="mt-2">
                                開始: {{ $activeFearMeterRestriction->started_at?->format('Y-m-d H:i:s') ?? '-' }}
                            </div>
                            <div class="mt-1">
                                理由: {{ $activeFearMeterRestriction->reason ?? '-' }}
                            </div>
                            <form action="{{ route('Admin.Manage.User.FearMeterRestriction.Release', $model) }}" method="POST" class="mt-2">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning">入力制限を解除</button>
                            </form>
                        @else
                            <span class="badge bg-secondary">制限なし</span>
                            <form action="{{ route('Admin.Manage.User.FearMeterRestriction.Store', $model) }}" method="POST" class="mt-2">
                                @csrf
                                <div class="mb-2">
                                    <input type="text" name="reason" class="form-control" placeholder="制限理由（任意）">
                                </div>
                                <input type="hidden" name="source" value="manual">
                                <button type="submit" class="btn btn-sm btn-danger">入力制限する</button>
                            </form>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="d-flex justify-content-between mt-3">
        <a href="{{ route('Admin.Manage.User') }}" class="btn btn-default">
            <i class="fas fa-arrow-left"></i> 一覧に戻る
        </a>
        <form action="{{ route('Admin.Manage.User.Destroy', $model) }}" method="POST" onsubmit="return confirm('このユーザーを本当に削除していいですか？');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">
                <i class="fas fa-trash-alt"></i> 削除
            </button>
        </form>
    </div>
@endsection


