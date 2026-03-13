@extends('layout')

@section('title', $title->name)
@section('current-node-title', $title->name)
@section('ratingCheck', $title->rating == \App\Enums\Rating::None ? "false" : "true")

@section('current-node-content')

    @if (session('success'))
        <div class="alert alert-success mt-3 relative pr-10">
            <button type="button" class="absolute top-0 right-0 p-2 border-0 bg-transparent cursor-pointer" style="line-height: 1;" onclick="this.closest('.alert').style.display='none'" aria-label="閉じる"><i class="bi bi-x"></i></button>
            {!! nl2br(e(session('success'))) !!}
        </div>
    @endif

    @if (!$isOver18 && $title->rating == \App\Enums\Rating::R18Z)
        <p class="rating-warning">
            CERO-Z相当の年齢指定があるパッケージが含まれます。<br>
            18歳未満には適さない表現が表示される場合があります。
        </p>
    @endif

    @include('common.current-node-ogp', ['model' => $title])


    <div class="title-users-info">
        <div>
            @if (Auth::check())
            <form action="{{ route('api.user.favorite.toggle') }}" method="POST" class="favorite-toggle-form" data-component-use="1">
                @csrf
                <input type="hidden" name="game_title_id" value="{{ $title->id }}">
                <button type="submit" class="btn btn-favorite{{ $isFavorite ? ' is-favorite' : '' }}" title="{{ $isFavorite ? 'お気に入りを解除' : 'お気に入りに登録' }}">
                    @if ($isFavorite)
                        ★
                    @else
                        ☆
                    @endif
                </button>
            </form>
            @endif
        </div>
    </div>
@endsection

@section('nodes')
    <section class="node" id="title-review-node">
        <div class="node-head">
            <h2 class="node-head-text">評判</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic">
            <div class="title-fear-meter">
                <label>怖さメーター</label>
                @if ($fearMeter)    
                <div>
                    @for ($i = 0; $i < $fearMeter->fear_meter->value; $i++)
                    ■
                    @endfor
                    @for ($i = 0; $i < 4 - $fearMeter->fear_meter->value; $i++)
                    □
                    @endfor
                    {{ $fearMeter->average_rating }} ({{ $fearMeter->fear_meter->text() }})
                </div>
                @else
                <div></div>
                @endif
            </div>
            @if (Auth::check())
            <div style="margin-left: 20px;">
                <a href="{{ route('User.FearMeter.Form', ['titleKey' => $title->key, 'from' => 'title-detail']) }}" data-hgn-scope="full">あなたの怖さメーター</a>
            </div>
            @endif
        </div>
    </section>

    @if ($title->packageGroups()->exists())
        <section class="node tree-node" id="pkg-lineup-tree-node">
            <div class="node-head">
                <h2 class="node-head-text">パッケージラインナップ</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content tree">
                @foreach ($title->packageGroups->sortByDesc('sort_order') as $pkgGroup)
                    <section class="node" id="pkgg-{{ $pkgGroup->id }}-tree-node">
                        <div class="node-head node-head-small-margin">
                            <h3 class="node-head-text">{{ $pkgGroup->name }}</h3>
                            <span class="node-pt">●</span>
                        </div>
                        <div class="node-content basic">
                            @if (!empty($pkgGroup->description))
                                <p class="pkg-group-description">{!! nl2br($pkgGroup->description) !!}</p>
                            @endif
                            @foreach ($pkgGroup->packages->sortBy([['sort_order', 'desc'], ['game_platform_id', 'desc'], ['default_img_type', 'desc']]) as $pkg)
                            <div class="pkg-info">
                                <div class="pkg-info-text">
                                    <a href="{{ route('Game.PlatformDetail', ['platformKey' => $pkg->platform->key]) }}" data-hgn-scope="full">{{ $pkg->platform->acronym }}</a>
                                    @empty($pkg->node_name)
                                    @else
                                        &nbsp;{!! $pkg->node_name !!}
                                    @endif
                                    <br>
                                    <span>{{ $pkg->release_at }}</span>
                                </div>
                                
                                @if ($pkg->shops->count() > 0)
                                <div class="pkg-info-shops">
                                    @foreach($pkg->shops as $shop)
                                    <div class="pkg-info-shop">
                                        <a href="{{ $shop->url }}" target="_blank" rel="noopener noreferrer">
                                            <div class="pkg-info-shop-img">
                                            @if ($shop->ogp !== null && $shop->ogp->image !== null)
                                                <img src="{{ $shop->ogp->image }}" width="{{ $shop->ogp->image_width }}" height="{{ $shop->ogp->image_height }}" class="pkg-img">
                                            @elseif (!empty($shop->img_tag))
                                                {!! $shop->img_tag !!}
                                            @else
                                                <img src="{{ $pkg->default_img_type->imgUrl() }}">
                                            @endif
                                            </div>
                                            <div class="shop-name">
                                                {{ $shop->shop()?->name() ?? '--' }}
                                            </div>
                                        </a>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </section>
    @endif

    @if ($title->series && $title->series->titles->count() > 1)
        <section class="node tree-node" id="footer-tree-node">
            <div class="node-head">
                <h2 class="node-head-text">シリーズ作品</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content tree">
                @foreach ($title->series->titles->sortBy('first_release_int') as $sameSeriesTitle)
                @if ($sameSeriesTitle->id === $title->id)
                    @continue
                @endif
                <section class="node basic" id="{{ $title->key }}-link-node">
                    <div class="node-head">
                        <a href="{{ route('Game.TitleDetail', ['titleKey' => $sameSeriesTitle->key]) }}" class="node-head-text">{{ $sameSeriesTitle->name }}</a>
                        <span class="node-pt">●</span>
                    </div>
                </section>
                @endforeach
            </div>
        </section>
    @endif

    <section class="node tree-node" id="footer-tree-node">
        <div class="node-head">
            <h2 class="node-head-text">近道</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">
            <section class="node tree-node" id="admin-link-node">
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
                    <a href="{{ route('Admin.Game.Title.Detail', $title) }}" class="node-head-text" rel="external">管理</a>
                    <span class="node-pt">●</span>
                </div>
            </section>
            @endif
        </div>
    </section>


@endsection
