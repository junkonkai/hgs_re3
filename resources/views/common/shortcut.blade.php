<section class="node tree-node">
    <div class="node-head">
        <h2 class="node-head-text">近道</h2>
        <span class="node-pt">●</span>
    </div>
    <div class="node-content tree">
        @isset($shortcutRoute)
            @include('common.shortcut_node', ['nodes' => $shortcutRoute])
        @else
            <section class="node basic" id="shortcut-root-node">
                <div class="node-head">
                    <a href="{{ route('Root') }}" class="node-head-text">ルート</a>
                    <span class="node-pt main-node-pt">●</span>
                </div>
            </section>
        @endisset

        @if (Auth::check())
        @isset ($myNodeShortcutRoute)
            @include('common.shortcut_node', ['nodes' => $myNodeShortcutRoute])
        @else
            <section class="node basic">
                <div class="node-head">
                    <a href="{{ route('User.MyNode.Top') }}" class="node-head-text">マイページ</a>
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
    </div>
</section>
