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
    @if (session('warning'))
        <div class="alert alert-warning mt-3">
            {!! nl2br(e(session('warning'))) !!}
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
    <section class="node tree-node" id="title-reputation-node">
        <div class="node-head">
            <h2 class="node-head-text">評判</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content tree">

    <section class="node @if ($fearMeter) tree-node @endif" id="title-review-node">
        <div class="node-head">
            <h2 class="node-head-text">怖さメーター</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic @if ($fearMeter) pl-4 @endif mb-5">
            <div class="title-fear-meter">
                @if ($fearMeter)    
                @php
                    $fearMeterMax = 4;
                    $fearMeterAverage = (float) $fearMeter->average_rating;
                    $fearMeterAverage = max(0, min($fearMeterMax, $fearMeterAverage));
                    $fearMeterPercent = ($fearMeterAverage / $fearMeterMax) * 100;
                @endphp
                <div class="space-y-2">
                    <div class="h-3 w-full max-w-xs overflow-hidden rounded-full bg-slate-700/60">
                        <div
                            class="h-full bg-gradient-to-r from-slate-800 via-sky-600 to-indigo-500"
                            style="width: {{ $fearMeterPercent }}%;"
                        ></div>
                    </div>
                    <div class="text-sm text-slate-200">
                        <span class="font-semibold">{{ number_format($fearMeterAverage, 2) }} / {{ $fearMeterMax }}</span>
                        <span class="text-slate-400">（{{ $fearMeter->fear_meter->text() }}）</span>
                    </div>
                </div>
                @else
                <p>怖さメーターは入力されていないようだ</p>
                    @if (Auth::check())
                    <p class="mt-5">
                        <a href="{{ route('User.FearMeter.Form', ['titleKey' => $title->key, 'from' => 'title-detail']) }}" data-hgn-scope="full">怖さメーターを入力しますか？</a>
                    </p>
                    @endif
                @endif
            </div>
        </div>

        @if ($fearMeter)    
        <div class="node-content tree">
            <section class="node" id="title-fear-meter-comment-pickup-node">
                <div class="node-head">
                    <h3 class="node-head-text">コメントピックアップ</h3>
                    <span class="node-pt">●</span>
                </div>
                
                <div class="node-content basic">
                    @if ($commentLogPickup->isEmpty())
                        <p>コメントの投稿はまだないようだ</p>
                    @else
                        @foreach ($commentLogPickup as $commentLog)
                            <div class="pb-4 border-b border-white/10 last:border-b-0">
                                <div class="mb-1 text-xs text-slate-400">
                                    {{ $commentLog->created_at }} / {{ $commentLog->user?->name ?? '(不明)' }}
                                </div>
                                <div class="mb-2 text-sm text-slate-200">
                                    @php
                                        $fearMeter = \App\Enums\FearMeter::tryFrom((int) $commentLog->new_fear_meter);
                                    @endphp
                                    <strong>怖さ:</strong> {{ $fearMeter?->text() ?? '-' }}
                                </div>
                                <div class="text-sm leading-relaxed text-slate-100">{!! nl2br(e($commentLog->comment)) !!}</div>
                                <div class="mt-2 text-sm flex items-center gap-6">
                                    @if (Auth::check())
                                        <form method="POST" action="{{ route('Game.TitleFearMeterComments.Like', ['titleKey' => $title->key, 'logId' => $commentLog->id]) }}" class="fear-meter-reaction-form inline-flex items-center mb-0" data-component-use="1" data-reaction-kind="like" data-done="{{ in_array($commentLog->id, $likedLogIds, true) ? '1' : '0' }}" data-like-url="{{ route('Game.TitleFearMeterComments.Like', ['titleKey' => $title->key, 'logId' => $commentLog->id]) }}" data-unlike-url="{{ route('Game.TitleFearMeterComments.UnlikePost', ['titleKey' => $title->key, 'logId' => $commentLog->id]) }}">
                                            @csrf
                                            <button type="submit" class="inline-flex h-6 items-center gap-1 leading-none text-slate-400 transition-colors hover:text-sky-400" title="いいね">
                                                <i class="bi bi-hand-thumbs-up"></i> <span class="js-like-count">{{ $commentLog->likes_count }}</span>
                                            </button>
                                        </form>
                                    @else
                                        <span class="inline-flex h-6 items-center gap-1 leading-none text-slate-400">
                                            <i class="bi bi-hand-thumbs-up"></i> {{ $commentLog->likes_count }}
                                        </span>
                                    @endif

                                    <div>
                                        @if (Auth::check())
                                            @if (in_array($commentLog->id, $reportedLogIds, true))
                                                <span class="inline-flex h-6 items-center gap-1 leading-none text-slate-500" title="通報済み">
                                                    <i class="bi bi-flag-fill"></i> 通報済み
                                                </span>
                                            @else
                                                <form method="POST" action="{{ route('Game.TitleFearMeterComments.Report', ['titleKey' => $title->key, 'logId' => $commentLog->id]) }}" class="fear-meter-reaction-form inline-flex items-center mb-0" data-component-use="1" data-reaction-kind="report" data-done="0">
                                                    @csrf
                                                    <button type="submit" class="inline-flex h-6 items-center gap-1 leading-none text-slate-400 transition-colors hover:text-rose-400" title="通報">
                                                        <i class="bi bi-flag"></i> 通報
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="inline-flex h-6 items-center gap-1 leading-none text-slate-500" title="通報">
                                                <i class="bi bi-flag"></i> 通報
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        <div style="margin-top: 12px;">
                            <a href="{{ route('Game.TitleFearMeterComments', ['titleKey' => $title->key]) }}" data-hgn-scope="full">コメントをもっと見る</a>
                        </div>
                    @endif
                </div>
            </section>
            
        </div>
        @endif
    </section>

    <section class="node @if ($reviewStatistic) tree-node @endif" id="title-reviews-node">
        <div class="node-head">
            <h2 class="node-head-text">レビュー</h2>
            <span class="node-pt">●</span>
        </div>
        <div class="node-content basic @if ($reviewStatistic) pl-4 @endif mb-5">
            @if ($reviewStatistic)
                <div class="space-y-2">
                    <div class="text-sm text-slate-200">
                        <span class="font-semibold text-lg">{{ $reviewStatistic->avg_total_score !== null ? round((float)$reviewStatistic->avg_total_score) : '-' }}</span>
                        <span class="text-slate-400 text-xs"> / 100</span>
                        <span class="ml-2 text-slate-400">（{{ $reviewStatistic->review_count }}件）</span>
                    </div>
                    <div class="text-xs text-slate-400 flex flex-wrap gap-x-4 gap-y-1">
                        @if ($reviewStatistic->avg_story !== null)
                            <span>ストーリー: {{ round((float)$reviewStatistic->avg_story) }}/20</span>
                        @endif
                        @if ($reviewStatistic->avg_atmosphere !== null)
                            <span>雰囲気・演出: {{ round((float)$reviewStatistic->avg_atmosphere) }}/20</span>
                        @endif
                        @if ($reviewStatistic->avg_gameplay !== null)
                            <span>ゲーム性: {{ round((float)$reviewStatistic->avg_gameplay) }}/20</span>
                        @endif
                        @if ($reviewStatistic->user_score_adjustment !== null)
                            <span>さじ加減: {{ round((float)$reviewStatistic->user_score_adjustment) }}/20</span>
                        @endif
                    </div>
                </div>
            @else
                <p>レビューはまだないようだ</p>
                @if (Auth::check())
                    <div class="mt-5">
                        <a href="{{ route('User.Review.Form', ['titleKey' => $title->key]) }}" data-hgn-scope="full">レビューを書く</a>
                    </div>
                @endif
            @endif
        </div>

        @if ($reviewStatistic)
        <div class="node-content tree">
            <section class="node" id="title-review-pickup-node">
                <div class="node-head">
                    <h3 class="node-head-text">新着レビュー</h3>
                    <span class="node-pt">●</span>
                </div>
                <div class="node-content basic">
                    @if ($recentReviews->isEmpty())
                        <p>レビューはまだないようだ</p>
                    @else
                        @foreach ($recentReviews as $recentReview)
                            <div class="py-3 border-b border-white/10 last:border-b-0">
                                <div class="mb-1 text-xs text-slate-400 flex flex-wrap gap-x-3">
                                    <span>{{ $recentReview->user?->show_id ?? '(不明)' }}</span>
                                    @if ($recentReview->play_status === \App\Enums\PlayStatus::Watched)
                                        <span class="text-sky-400">{{ $recentReview->play_status->text() }}</span>
                                    @else
                                        <span>{{ $recentReview->play_status?->text() }}</span>
                                    @endif
                                    @if ($recentReview->total_score !== null)
                                        <span class="font-semibold text-slate-200">{{ $recentReview->total_score }}<span class="font-normal text-slate-400">/100</span></span>
                                    @endif
                                    @if ($recentReview->has_spoiler)
                                        <span class="text-amber-400">【ネタバレあり】</span>
                                    @endif
                                </div>
                                @if ($recentReview->horrorTypeTags->isNotEmpty())
                                    <div class="mb-1 flex flex-wrap gap-1">
                                        @foreach ($recentReview->horrorTypeTags as $tag)
                                            <span class="text-xs px-1.5 py-0.5 rounded bg-slate-700 text-slate-300">{{ $tag->tag->text() }}</span>
                                        @endforeach
                                    </div>
                                @endif
                                @if ($recentReview->has_spoiler)
                                    <details class="text-sm">
                                        <summary class="cursor-pointer text-slate-400 hover:text-slate-200">本文を表示（ネタバレあり）</summary>
                                        <div class="mt-2 leading-relaxed text-slate-100">{!! nl2br(e($recentReview->body)) !!}</div>
                                    </details>
                                @else
                                    <div class="text-sm leading-relaxed text-slate-100">{!! nl2br(e(mb_strimwidth($recentReview->body, 0, 200, '…'))) !!}</div>
                                @endif
                                <div class="mt-1 text-xs">
                                    <a href="{{ route('Game.TitleReview', ['titleKey' => $title->key, 'reviewKey' => $recentReview->key]) }}" data-hgn-scope="full">全文を読む</a>
                                </div>
                            </div>
                        @endforeach
                        <div class="mt-3">
                            <a href="{{ route('Game.TitleReviews', ['titleKey' => $title->key]) }}" data-hgn-scope="full">レビューを全件見る</a>
                        </div>
                        @if (Auth::check() && !$userReview)
                            <div class="mt-2">
                                <a href="{{ route('User.Review.Form', ['titleKey' => $title->key]) }}" data-hgn-scope="full">レビューを書く</a>
                            </div>
                        @elseif (Auth::check() && $userReview)
                            <div class="mt-2">
                                <a href="{{ route('User.Review.Form', ['titleKey' => $title->key]) }}" data-hgn-scope="full">レビューを編集する</a>
                            </div>
                        @endif
                    @endif
                </div>
            </section>
        </div>
        @endif
    </section>

        </div>
    </section>{{-- /評判 --}}

    @if ($title->packageGroups()->exists())
        <section class="node tree-node" id="pkg-lineup-tree-node">
            <div class="node-head">
                <h2 class="node-head-text">パッケージラインナップ</h2>
                <span class="node-pt">●</span>
            </div>
            <div class="node-content tree">
                @foreach ($title->packageGroups->sortByDesc('sort_order') as $pkgGroup)
                    <section class="node" id="pkgg-{{ $pkgGroup->id }}-tree-node">
                        <div class="node-head">
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
                                        <a href="{{ $shop->url }}" target="_blank" rel="noopener">
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
                    <section class="node tree-node" id="back-to-lineup-node">
                        <div class="node-head">
                            <a href="{{ route('Game.Lineup') }}" class="node-head-text">ラインナップ</a>
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
