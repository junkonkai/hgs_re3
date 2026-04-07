@extends('layout')

@section('title', 'リカバリーコード再発行')
@section('current-node-title', 'リカバリーコード再発行')

@section('nodes')
    <section class="node" id="recovery-codes-confirm-regen-node">
        <div class="node-head">
            <h2 class="node-head-text">リカバリーコード再発行</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">

            <div class="alert alert-warning mt-3">
                再発行すると、既存のリカバリーコードはすべて無効になります。<br>
                新しいコードを必ず安全な場所に保管してください。
            </div>

            <form action="{{ route('User.MyNode.LoginSettings.RecoveryCodes.Regenerate') }}" method="POST" data-no-push-state="1">
                @csrf
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-danger btn-sm">再発行する</button>
                    <a href="{{ route('User.MyNode.LoginSettings') }}" class="btn btn-outline-secondary btn-sm" data-hgn-scope="full">キャンセル</a>
                </div>
            </form>
        </div>
    </section>

    @include('common.shortcut')
@endsection
