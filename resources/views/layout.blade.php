@php
    $siteName = 'ホラーゲームネットワーク(β)';
    $ogpTitle = $ogpTitle ?? $siteName;
    $ogpDescription = $ogpDescription ?? 'ホラーゲーム好きのためのコミュニティサイトです。レビューや二次創作など、みなさんの「好き」を共有し、より深くホラーゲームを楽しんでほしいという想いで運営しています。';
    $ogpImage = $ogpImage ?? '/img/ogp.png';
    $ogpUrl = $ogpUrl ?? url()->current();
    $ogpType = $ogpType ?? 'website';
@endphp
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', '') | {{ $siteName }}</title>
    <meta content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no" name="viewport">
    {{-- OGP: ビューで @section('ogp') を定義するか、$ogpTitle / $ogpDescription / $ogpImage / $ogpUrl / $ogpType を渡すと上書き --}}
    @hasSection('ogp')
        @yield('ogp')
    @else
        @include('common.ogp_meta', [
            'siteName' => $siteName,
            'ogpTitle' => $ogpTitle,
            'ogpDescription' => $ogpDescription,
            'ogpImage' => $ogpImage,
            'ogpUrl' => $ogpUrl,
            'ogpType' => $ogpType,
        ])
    @endif
    <link rel="icon" type="image/png" href="{{ asset('favicon-96x96.png') }}" sizes="96x96" />
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}" />
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" />
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}" />
    <link rel="manifest" href="{{ asset('site.webmanifest') }}" />
    {{-- <link href="{{ asset('assets/plugins/simple-line-icons/css/simple-line-icons.css') }}" rel="stylesheet"> --}}
    <link href="{{ asset('assets/plugins/bootstrap-icons/font/bootstrap-icons.css') }}" rel="stylesheet">
    <script>
        window.Laravel = @json(['csrfToken' => csrf_token()]);
        window.baseUrl = '{{ url('/') }}';
        window.lazyCss = @json([]);
        window.siteName = '{{ $siteName }}';
        window.components = @json($components ?? []);
    </script>
    @vite(['resources/css/app.css', 'resources/ts/app.ts'])
</head>
<body class="@isset($colorState) has-{{ $colorState }} @endisset py-4">
    <main>
        <section class="node" id="current-node">
            <div class="node-head">
                <h1 class="node-head-text">@yield('current-node-title')</h1>
                <span class="node-pt current-node-pt">●</span>
            </div>
            
            <div class="node-content" id="current-node-content">
                @hasSection('current-node-content')
                    @yield('current-node-content')
                @endif
            </div>

            <div class="node-content tree" id="current-tree-nodes">
                @yield('nodes')
            </div>
        </section>
    </main>

    <footer>
        &copy; 2003-{{ date('Y') }} <a href="https://junkonkai.com" target="_blank" rel="external noopener">電子創作房 純魂会</a>
    </footer>
</body>
</html>
