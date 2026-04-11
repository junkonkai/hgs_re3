@extends('layout')

@section('title', 'プロフィール設定')
@section('current-node-title', 'プロフィール設定')

@section('nodes')
    <section class="node" id="profile-edit-node">
        <div class="node-head">
            <h2 class="node-head-text">プロフィール設定</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <form action="{{ route('User.MyNode.Profile.Update') }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <label for="name" class="form-label">表示名</label>
                    <input type="text" name="name" id="name" class="form-control" value="{{ old('name', $user->name) }}" required maxlength="255">
                    @error('name')
                        <div class="alert alert-warning mt-3">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="form-group mb-3">
                    <label for="show_id" class="form-label">ユーザーID</label>
                    <input type="text" name="show_id" id="show_id" class="form-control" value="{{ old('show_id', $user->show_id) }}" required maxlength="30">
                    <small class="form-text text-muted">使用可能文字：英数字、ハイフン、アンダースコア（1〜30文字）</small>
                    <small class="form-text text-amber-400">ユーザーIDを変更すると、投稿済みレビューのURLも変わります。</small>
                    @error('show_id')
                        <div class="alert alert-warning mt-3">
                            {{ $message }}
                        </div>
                    @enderror
                </div>
                <div class="form-group">
                    <button type="submit" class="btn btn-success">更新</button>
                </div>
            </form>
        </div>
    </section>

    <section class="node tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node basic">
                <div class="node-head">
                    <a href="{{ route('Root') }}" class="node-head-text">ルート</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            <section class="node basic">
                <div class="node-head">
                    <a href="{{ route('User.MyNode.Top') }}" class="node-head-text">マイノード</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            <section class="node basic" id="logout-link-node">
                <div class="node-head">
                    <a href="{{ route('Account.Logout') }}" class="node-head-text">ログアウト</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
        </div>
    </section>
@endsection


