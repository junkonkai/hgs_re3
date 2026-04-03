@extends('layout')

@section('title', '外部サービス連携')
@section('current-node-title', '外部サービス連携')
@section('current-node-content')

@endsection

@section('nodes')
    <section class="node" id="social-accounts-form-node">
        <div class="node-head">
            <h2 class="node-head-text">外部サービス一覧</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            @if (session('success'))
                <div class="alert alert-success mt-3">
                    {{ session('success') }}
                </div>
            @endif
            @if (session('error'))
                <div class="alert alert-danger mt-3">
                    {{ session('error') }}
                </div>
            @endif

            @php
                $linkedProviders = $user->socialAccounts->pluck('provider')->unique()->values();
                {{-- X連携: フリープランでは /2/users/me が使えないため非表示。課金後に有効化する。 --}}
                $availableProviders = [\App\Enums\SocialAccountProvider::GitHub, \App\Enums\SocialAccountProvider::Steam];
            @endphp

            <ul id="social_accounts">
                @foreach ($availableProviders as $provider)
                    @php
                        $isLinked = $linkedProviders->contains($provider);
                        $socialAccount = $user->socialAccounts->firstWhere('provider', $provider);
                    @endphp
                    <li>
                        <div>
                            <span>{{ $provider->label() }}</span>
                            @if ($isLinked)
                                <span>連携済み</span>
                            @else
                                <span>未連携</span>
                            @endif
                        </div>
                        <div>
                            @if ($isLinked)
                                <form action="{{ route('User.MyNode.SocialAccounts.Unlink') }}" method="POST" onsubmit="return confirm('連携を解除しますか？');">
                                    @csrf
                                    <input type="hidden" name="provider" value="{{ $provider->value }}">
                                    <button type="submit">連携解除</button>
                                </form>
                            @else
                                <a href="{{ route('User.MyNode.SocialAccounts.Link', ['provider' => strtolower($provider->name)]) }}">連携する</a>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
    @include('common.shortcut')
@endsection
