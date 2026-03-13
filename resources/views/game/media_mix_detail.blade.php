@extends('layout')

@section('title', $mediaMix->name)
@section('current-node-title', $mediaMix->name)
@section('ratingCheck', $mediaMix->rating == \App\Enums\Rating::None ? "false" : "true")

@section('current-node-content')
    @include('common.current-node-ogp', ['model' => $mediaMix])
@endsection

@section('nodes')

    @include('common.related_products', ['model' => $mediaMix])

    @if ($mediaMix->mediaMixGroup && $mediaMix->mediaMixGroup->mediaMixes->count() > 1)
    <section class="node tree-node" id="footer-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">関連作品</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            @foreach ($mediaMix->mediaMixGroup->mediaMixes as $sameMediaMix)
            @if ($sameMediaMix->id === $mediaMix->id)
                @continue
            @endif
            <section class="node basic" id="{{ $sameMediaMix->key }}-link-node">
                <div class="node-head">
                    <a href="{{ route('Game.MediaMixDetail', ['mediaMixKey' => $sameMediaMix->key]) }}" class="node-head-text">{{ $sameMediaMix->name }}</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            @endforeach
        </div>
    </section>
@endif

    @php $franchise = $mediaMix->getFranchise(); @endphp
    <section class="node tree-node" id="quick-link-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node tree-node" id="back-to-franchise-detail-link-node">
                <div class="node-head">
                    <a href="{{ route('Game.FranchiseDetail', ['franchiseKey' => $franchise->key]) }}" class="node-head-text">{{ $franchise->name }}フランチャイズ</a>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content tree">
                    <section class="node tree-node" id="back-to-franchises-node">
                        <div class="node-head">
                            <a href="{{ route('Game.Franchises') }}" class="node-head-text">フランチャイズ</a>
                            <span class="node-pt">●</span>
                        </div>
                        <div class="node-content tree">
                            <section class="node basic" id="back-to-root-node">
                                <div class="node-head">
                                    <a href="{{ route('Root') }}" class="node-head-text">ルート</a>
                                    <span class="node-pt">●</span>
                                </div>
                            </section>
                        </div>
                    </section>
                </div>
            </section>

            @include('common.shortcut_mynode')
        
            @if (is_admin_user())
            <section class="node basic" id="admin-link-node">
                <div class="node-head">
                    <a href="{{ route('Admin.Game.MediaMix.Detail', $mediaMix) }}" class="node-head-text" rel="external">管理</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            @endif
        </div>
    </section>

    {{-- 
    <section>
        <div class="node">
            <h2 class="head2 fade">
                関連ネットワーク
            </h2>
        </div>
        <div class="node-map" style="margin-bottom: 50px;">

            @foreach ($relatedNetworks as $relatedNetwork)
                <div class="node">
                    <div class="link-node-center fade">
                        <a href="{{ route('Game.MediaMixDetail', ['mediaMixKey' => $relatedNetwork->key]) }}">
                            {!! $relatedNetwork->node_name !!}
                        </a>
                    </div>
                </div>
            @endforeach

            @if ($mediaMix->getFranchise())
                <div class="node">
                    <div class="link-node-center fade">
                        <a href="{{ route('Game.FranchiseDetail', ['franchiseKey' => $mediaMix->getFranchise()->key]) }}">
                            {{ $mediaMix->getFranchise()->node_name }}<br>
                            フランチャイズ
                        </a>
                    </div>
                </div>
            @endif

            @if ($mediaMix->titles()->exists())
                @foreach ($mediaMix->titles as $title)
                    <div class="node">
                        @include('common.nodes.title-node', ['title' => $title])
                    </div>
                @endforeach
            @endif
        </div>
    </section>

    @include('footer')



    @if (\Illuminate\Support\Facades\Auth::guard('admin')->check())
        <div class="admin-edit">
            <a href="{{ route('Admin.Game.MediaMix.Detail', $mediaMix) }}">管理</a>
        </div>
    @endif
     --}}
@endsection
