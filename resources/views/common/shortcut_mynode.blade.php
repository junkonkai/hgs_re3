@if (Auth::check())
    @isset($shortcutRoute)
    @else
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
    @endisset
@else
<section class="node basic">
    <div class="node-head">
        <a href="{{ route('Account.Login') }}" class="node-head-text">ログイン</a>
        <span class="node-pt">●</span>
    </div>
</section>
<section class="node basic">
    <div class="node-head">
        <a href="{{ route('Account.Register') }}" class="node-head-text">アカウント新規登録</a>
        <span class="node-pt">●</span>
    </div>
</section>
@endif

