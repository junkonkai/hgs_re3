<?php

namespace App\Http\Controllers;

use App\Enums\Rating;
use App\Models\GameFranchise;
use App\Models\GameMaker;
use App\Models\GameMediaMix;
use App\Models\GamePackage;
use App\Models\GamePackageGroupPackageLink;
use App\Models\GameTitlePackageGroupLink;
use App\Models\GamePlatform;
use App\Models\GameSeries;
use App\Models\GameTitle;
use App\Models\GameTitleFearMeterStatistic;
use App\Models\GameTitleReviewStatistic;
use App\Models\UserFavoriteGameTitle;
use App\Models\UserGameTitleFearMeterCommentLike;
use App\Models\UserGameTitleFearMeterCommentReport;
use App\Models\UserGameTitleFearMeterLog;
use App\Models\UserGameTitleReview;
use App\Models\UserGameTitleReviewLike;
use App\Models\UserGameTitleReviewReport;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Support\Pager;
use Illuminate\Support\Facades\Auth;

class GameController extends Controller
{
    private const LINEUP_PER_PAGE = 10;

    /**
     * ホラーゲームラインナップ
     * last_title_update_at 降順のフランチャイズと、紐づくシリーズ・タイトルをツリー形式で表示する。
     * text またはフィルタが指定された場合は Meilisearch で検索し、検索結果を表示する。
     *
     * @param Request $request
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function lineup(Request $request): JsonResponse|Application|Factory|View
    {
        $text = trim($request->input('text', ''));
        $platformId = $request->integer('platform_id', 0) > 0 ? $request->integer('platform_id') : null;
        $makerId = $request->integer('maker_id', 0) > 0 ? $request->integer('maker_id') : null;
        $fearMeterMin = $request->filled('fear_meter_min') ? $request->integer('fear_meter_min') : null;
        $fearMeterMax = $request->filled('fear_meter_max') ? $request->integer('fear_meter_max') : null;
        $releaseFrom = $request->integer('release_from', 0) > 0 ? $request->integer('release_from') : null;
        $releaseTo = $request->integer('release_to', 0) > 0 ? $request->integer('release_to') : null;

        $hasFilters = $platformId !== null || $makerId !== null
            || $fearMeterMin !== null || $fearMeterMax !== null
            || $releaseFrom !== null || $releaseTo !== null;

        $platforms = GamePlatform::select(['id', 'name', 'acronym'])->orderBy('sort_order')->get();

        $makerName = '';
        if ($makerId !== null) {
            $makerModel = GameMaker::select(['id', 'name'])->find($makerId);
            $makerName = $makerModel?->name ?? '';
            if ($makerModel === null) {
                $makerId = null;
            }
        }

        if (!empty($text) || $hasFilters) {
            $searchResultIds = $this->searchTitleIds(
                $text,
                $platformId,
                $makerId,
                $fearMeterMin,
                $fearMeterMax,
                $releaseFrom,
                $releaseTo,
            );

            $allFranchises = $this->buildFranchiseTree($searchResultIds);
            $total = $allFranchises->count();
            $page = max(1, $request->integer('page', 1));
            $totalPages = (int) ceil($total / self::LINEUP_PER_PAGE);
            $franchises = $allFranchises->slice(($page - 1) * self::LINEUP_PER_PAGE, self::LINEUP_PER_PAGE)->values();

            $routeParams = array_filter([
                'text'           => $text ?: null,
                'platform_id'    => $platformId,
                'maker_id'       => $makerId,
                'maker_name'     => $makerName ?: null,
                'fear_meter_min' => $fearMeterMin,
                'fear_meter_max' => $fearMeterMax,
                'release_from'   => $releaseFrom,
                'release_to'     => $releaseTo,
            ], fn ($v) => $v !== null);

            $pager = new Pager($page, $totalPages, 'Game.Lineup', $routeParams, 'children');

            $lineupComponents = ['LineupSearch' => ['makerSuggestUrl' => route('api.game.maker.suggest')]];
            return $this->tree(view('game.lineup', compact(
                'text', 'franchises', 'pager', 'total',
                'platforms', 'platformId', 'makerId', 'makerName',
                'fearMeterMin', 'fearMeterMax', 'releaseFrom', 'releaseTo',
            )), ['components' => $lineupComponents]);
        }

        $page = max(1, (int) $request->input('page', 1));
        $offset = ($page - 1) * self::LINEUP_PER_PAGE;

        [$franchises, $hasMore, $total] = $this->getLineupFranchises($offset, self::LINEUP_PER_PAGE);
        $totalPages = (int) ceil($total / self::LINEUP_PER_PAGE);
        $pager = new Pager($page, $totalPages, 'Game.Lineup', [], 'children');

        $lineupComponents = ['LineupSearch' => ['makerSuggestUrl' => route('api.game.maker.suggest')]];
        return $this->tree(view('game.lineup', compact(
            'text', 'franchises', 'pager', 'total',
            'platforms', 'platformId', 'makerId', 'makerName',
            'fearMeterMin', 'fearMeterMax', 'releaseFrom', 'releaseTo',
        )), ['components' => $lineupComponents]);
    }

    /**
     * Meilisearch でタイトルIDを検索して返す
     *
     * @param string $text
     * @param int|null $platformId
     * @param int|null $makerId
     * @param int|null $fearMeterMin
     * @param int|null $fearMeterMax
     * @param int|null $releaseFrom 年（例: 2020）
     * @param int|null $releaseTo 年（例: 2023）
     * @return array
     */
    private function searchTitleIds(
        string $text,
        ?int $platformId,
        ?int $makerId,
        ?int $fearMeterMin,
        ?int $fearMeterMax,
        ?int $releaseFrom,
        ?int $releaseTo,
    ): array {
        $filters = [];

        if ($platformId !== null) {
            $filters[] = "platform_ids = {$platformId}";
        }
        if ($makerId !== null) {
            $filters[] = "maker_ids = {$makerId}";
        }
        if ($fearMeterMin !== null && $fearMeterMax !== null) {
            if ($fearMeterMin === $fearMeterMax) {
                $filters[] = "fear_meter = {$fearMeterMin}";
            } else {
                $filters[] = "fear_meter >= {$fearMeterMin} AND fear_meter <= {$fearMeterMax}";
            }
        } elseif ($fearMeterMin !== null) {
            $filters[] = "fear_meter >= {$fearMeterMin}";
        } elseif ($fearMeterMax !== null) {
            $filters[] = "fear_meter <= {$fearMeterMax}";
        }
        if ($releaseFrom !== null) {
            $filters[] = "first_release_int >= {$releaseFrom}0101";
        }
        if ($releaseTo !== null) {
            $filters[] = "first_release_int <= {$releaseTo}1231";
        }

        $filterStr = implode(' AND ', $filters);

        if (!empty($text)) {
            $searchText = mb_convert_kana($text, 'a');
            $words = array_filter(explode(' ', $searchText), fn ($w) => trim($w) !== '');

            $resultIds = [];
            foreach ($words as $word) {
                $word = trim($word);
                if ($word === '') {
                    continue;
                }
                $query = GameTitle::search($word);
                if ($filterStr !== '') {
                    $query->options(['filter' => $filterStr]);
                }
                $ids = $query->get()->pluck('id')->toArray();
                $resultIds = array_merge($resultIds, $ids);
            }

            return array_values(array_unique($resultIds));
        }

        // フィルタのみ（テキストなし）
        $query = GameTitle::search('');
        if ($filterStr !== '') {
            $query->options(['filter' => $filterStr]);
        }

        return $query->get()->pluck('id')->toArray();
    }

    /**
     * タイトルIDの配列からフランチャイズ→シリーズ→タイトルのツリーを構築して返す
     *
     * @param array $titleIds
     * @return \Illuminate\Support\Collection
     */
    private function buildFranchiseTree(array $titleIds): \Illuminate\Support\Collection
    {
        if (empty($titleIds)) {
            return collect();
        }

        $titles = GameTitle::select(['id', 'key', 'name', 'game_series_id', 'game_franchise_id', 'rating'])
            ->whereIn('id', $titleIds)
            ->get()
            ->values();

        $seriesIds = $titles->whereNotNull('game_series_id')
            ->unique('game_series_id')
            ->pluck('game_series_id')
            ->toArray();

        $series = GameSeries::whereIn('id', $seriesIds)->get()->keyBy('id');

        $franchiseIds = [];
        foreach ($series as $s) {
            $s->searchTitles = [];
            $franchiseIds[] = $s->game_franchise_id;
        }

        $franchiseIds = array_merge(
            $franchiseIds,
            $titles->whereNotNull('game_franchise_id')
                ->pluck('game_franchise_id')
                ->unique()
                ->toArray()
        );

        $franchises = GameFranchise::whereIn('id', $franchiseIds)->get()->keyBy('id');

        foreach ($franchises as $franchise) {
            $franchise->searchTitles = [];
            $franchise->searchSeries = [];
        }

        foreach ($titles as $title) {
            if (!empty($title->game_series_id) && isset($series[$title->game_series_id])) {
                $s = $series[$title->game_series_id];
                $searchTitles = $s->searchTitles;
                $searchTitles[] = $title;
                $s->searchTitles = $searchTitles;
            } elseif (!empty($title->game_franchise_id) && isset($franchises[$title->game_franchise_id])) {
                $f = $franchises[$title->game_franchise_id];
                $searchTitles = $f->searchTitles;
                $searchTitles[] = $title;
                $f->searchTitles = $searchTitles;
            }
        }

        foreach ($series as $s) {
            if (isset($franchises[$s->game_franchise_id])) {
                $f = $franchises[$s->game_franchise_id];
                $searchSeries = $f->searchSeries;
                $searchSeries[] = $s;
                $f->searchSeries = $searchSeries;
            }
        }

        return $franchises->values();
    }

    /**
     * ラインナップ用フランチャイズ一覧を取得（シリーズ・タイトル付き）
     *
     * @param int $offset
     * @param int $limit
     * @return array{0: \Illuminate\Support\Collection, 1: bool, 2: int} [franchises, hasMore, total]
     */
    private function getLineupFranchises(int $offset = 0, int $limit = self::LINEUP_PER_PAGE): array
    {
        $query = GameFranchise::select(['id', 'key', 'name', 'rating'])
            ->orderByDesc('last_title_update_at');

        $total = $query->count();
        $franchises = (clone $query)->offset($offset)->limit($limit)->get();
        $hasMore = ($offset + $limit) < $total;

        $franchiseIds = $franchises->pluck('id')->toArray();

        if (empty($franchiseIds)) {
            return [$franchises, false, $total];
        }

        $series = GameSeries::select(['id', 'name', 'game_franchise_id'])
            ->whereIn('game_franchise_id', $franchiseIds)
            ->get()
            ->keyBy('id');

        $seriesIds = $series->pluck('id')->toArray();

        foreach ($series as $s) {
            $s->searchTitles = [];
        }

        $titles = GameTitle::select(['id', 'key', 'name', 'game_series_id', 'game_franchise_id', 'rating'])
            ->where(function ($q) use ($franchiseIds, $seriesIds) {
                $q->whereIn('game_series_id', $seriesIds)
                    ->orWhere(function ($q) use ($franchiseIds) {
                        $q->whereIn('game_franchise_id', $franchiseIds)->whereNull('game_series_id');
                    });
            })
            ->get();

        foreach ($franchises as $franchise) {
            $franchise->searchTitles = [];
            $franchise->searchSeries = [];
        }

        foreach ($titles as $title) {
            if (!empty($title->game_series_id) && isset($series[$title->game_series_id])) {
                $s = $series[$title->game_series_id];
                $searchTitles = $s->searchTitles;
                $searchTitles[] = $title;
                $s->searchTitles = $searchTitles;
            } elseif (!empty($title->game_franchise_id)) {
                $franchise = $franchises->firstWhere('id', $title->game_franchise_id);
                if ($franchise !== null) {
                    $searchTitles = $franchise->searchTitles;
                    $searchTitles[] = $title;
                    $franchise->searchTitles = $searchTitles;
                }
            }
        }

        foreach ($series as $s) {
            $franchise = $franchises->firstWhere('id', $s->game_franchise_id);
            if ($franchise !== null) {
                $searchSeries = $franchise->searchSeries;
                $searchSeries[] = $s;
                $franchise->searchSeries = $searchSeries;
            }
        }

        return [$franchises, $hasMore, $total];
    }

    /**
     * メーカーネットワーク
     *
     * @param Request $request
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function maker(Request $request): JsonResponse|Application|Factory|View
    {
        $makers = GameMaker::select()
            ->whereNull('related_game_maker_id')
            ->orderBy('name')
            ->get();

        return $this->tree(view('game.makers', compact('makers')));
    }

    /**
     * メーカー詳細ネットワーク
     *
     * @param Request $request
     * @param string $makerKey
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function makerDetail(Request $request, string $makerKey): JsonResponse|Application|Factory|View
    {
        $maker = GameMaker::findByKey($makerKey);

        if (!$maker) {
            abort(404);
        }

        $packages = $maker->packages();
        $packageGroups = GamePackageGroupPackageLink::whereIn('game_package_id', $packages->pluck('id'))->get();
        $titleIds = GameTitlePackageGroupLink::whereIn('game_package_group_id', $packageGroups->pluck('game_package_group_id')->unique())->pluck('game_title_id');
        $titles = GameTitle::whereIn('id', $titleIds)->get();

        $ratingCheck = $titles->where('rating', Rating::R18A)->count() > 0;

        return $this->tree(view('game.maker_detail', [
            'maker'  => $maker,
            'titles' => $titles,
        ]), options: ['ratingCheck' => $ratingCheck]);
    }

    /**
     * プラットフォーム
     *
     * @param Request $request
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function platform(Request $request): JsonResponse|Application|Factory|View
    {
        $platforms = GamePlatform::select()
            ->orderBy('sort_order')
            ->get();

        return $this->tree(view('game.platforms', compact('platforms')));
    }

    /**
     * プラットフォームの詳細
     *
     * @param Request $request
     * @param string $platformKey
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function platformDetail(Request $request, string $platformKey): JsonResponse|Application|Factory|View
    {
        $platform = GamePlatform::findByKey($platformKey);

        if (!$platform) {
            abort(404);
        }

        $packages = GamePackage::select(['id'])->where('game_platform_id', $platform->id)->get();
        $packageGroups = GamePackageGroupPackageLink::whereIn('game_package_id', $packages->pluck('id'))->get();
        $titleIds = GameTitlePackageGroupLink::whereIn('game_package_group_id', $packageGroups->pluck('game_package_group_id')->unique())->pluck('game_title_id');
        $titles = GameTitle::whereIn('id', $titleIds)->get();

        return $this->tree(view('game.platform_detail', [
            'platform'    => $platform,
            'titles'      => $titles
        ]));
    }

    /**
     * フランチャイズのネットワーク
     *
     * @param Request $request
     * @param string $prefix
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function franchises(Request $request, string $prefix = 'a'): JsonResponse|Application|Factory|View
    {
        if (preg_match('/^[akstnhmry]$/', $prefix) !== 1) {
            $prefix = 'a';
        }

        $prefixes =[
            'a' => ['あ', 'い', 'う', 'え', 'お'],
            'k' => ['か', 'き', 'く', 'け', 'こ', 'が', 'ぎ', 'ぐ', 'げ', 'ご'],
            's' => ['さ', 'し', 'す', 'せ', 'そ', 'ざ', 'じ', 'ず', 'ぜ', 'ぞ'],
            't' => ['た', 'ち', 'つ', 'て', 'と', 'だ', 'ぢ', 'づ', 'で', 'ど'],
            'n' => ['な', 'に', 'ぬ', 'ね', 'の'],
            'h' => ['は', 'ひ', 'ふ', 'へ', 'ほ', 'ば', 'び', 'ぶ', 'べ', 'ぼ', 'ぱ', 'ぴ', 'ぷ', 'ぺ', 'ぽ'],
            'm' => ['ま', 'み', 'む', 'め', 'も'],
            'y' => ['や', 'よ', 'ゆ'],
            'r' => ['ら', 'り', 'る', 'れ', 'ろ', 'わ', 'を', 'ん'],
        ];

        $franchisesByPrefix = [];
        foreach ($prefixes as $prefix => $words) {
            $model = GameFranchise::select(['id', 'key', 'name', 'description', 'rating']);
            foreach ($words as $word) {
                $model->orWhere('phonetic', 'like', $word . '%');
            }
    
            $franchisesByPrefix[$prefix] = $model->orderBy('phonetic')->get();
        }

        return $this->tree(view('game.franchises', compact('prefixes', 'franchisesByPrefix')));
    }

    /**
     * フランチャイズの詳細ネットワーク
     *
     * @param Request $request
     * @param string $franchiseKey
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function franchiseDetail(Request $request, string $franchiseKey): JsonResponse|Application|Factory|View
    {
        $franchise = GameFranchise::findByKey($franchiseKey);

        if (!$franchise) {
            abort(404);
        }

        $ratingCheck = false;
        $titles = [];
        foreach ($franchise->series as $series) {
            foreach ($series->titles as $title) {
                $titles[] = $title;
                if ($title->rating == Rating::R18A) {
                    $ratingCheck = true;
                }
            }
        }
        foreach ($franchise->titles as $title) {
            $titles[] = $title;
            if ($title->rating == Rating::R18A) {
                $ratingCheck = true;
            }
        }

        return $this->tree(view('game.franchise_detail', [
            'franchise'   => $franchise,
            'titles'      => $titles,
        ]), options: ['ratingCheck' => $ratingCheck]);
    }

    /**
     * タイトルの詳細
     *
     * @param Request $request
     * @param string $titleKey
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function titleDetail(Request $request, string $titleKey): JsonResponse|Application|Factory|View
    {
        $title = GameTitle::findByKey($titleKey);

        if (!$title) {
            abort(404);
        }

        $ratingCheck = $title->rating == Rating::R18A;
        $franchise = $title->getFranchise();

        // ログイン状態ならお気に入りに入っているか確認
        $isFavorite = false;
        if (Auth::check()) {
            $isFavorite = UserFavoriteGameTitle::where('user_id', Auth::id())
                ->where('game_title_id', $title->id)
                ->exists();
        }

        // 怖さメーター統計データを取得（集計されていなかったらnull）
        $fearMeter = GameTitleFearMeterStatistic::find($title->id);
        $commentLogPickup = UserGameTitleFearMeterLog::query()
            ->visibleComments()
            ->where('game_title_id', $title->id)
            ->with('user')
            ->withCount(['likes', 'reports'])
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();
        $likedLogIds = [];
        $reportedLogIds = [];
        if (Auth::check() && $commentLogPickup->isNotEmpty()) {
            $logIds = $commentLogPickup->pluck('id')->toArray();
            $likedLogIds = UserGameTitleFearMeterCommentLike::query()
                ->where('user_id', Auth::id())
                ->whereIn('fear_meter_log_id', $logIds)
                ->pluck('fear_meter_log_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
            $reportedLogIds = UserGameTitleFearMeterCommentReport::query()
                ->where('reporter_user_id', Auth::id())
                ->whereIn('fear_meter_log_id', $logIds)
                ->pluck('fear_meter_log_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        }

        // レビュー統計
        $reviewStatistic = GameTitleReviewStatistic::find($title->id);

        // 新着レビュー（ネタバレなし優先、最大3件）
        $recentReviews = UserGameTitleReview::where('game_title_id', $title->id)
            ->where('is_deleted', false)
            ->where('is_hidden', false)
            ->orderByRaw('has_spoiler ASC')
            ->orderByDesc('updated_at')
            ->limit(3)
            ->with(['user', 'horrorTypeTags'])
            ->get();

        // ログインユーザーがレビューに書けるかどうか
        $userReview = null;
        if (Auth::check()) {
            $userReview = UserGameTitleReview::where('user_id', Auth::id())
                ->where('game_title_id', $title->id)
                ->where('is_deleted', false)
                ->first();
        }

        return $this->tree(
            view('game.title_detail', compact(
                'title',
                'ratingCheck',
                'franchise',
                'isFavorite',
                'fearMeter',
                'commentLogPickup',
                'likedLogIds',
                'reportedLogIds',
                'reviewStatistic',
                'recentReviews',
                'userReview',
            )),
            options: [
                'ratingCheck' => $ratingCheck,
                'components' => [
                    'TitleDetailFavorite' => [],
                    'FearMeterCommentReaction' => [],
                ],
                'url' => route('Game.TitleDetail', ['titleKey' => $title->key]),
            ],
        );
    }

    /**
     * レビュー一覧（全タイトル）
     */
    public function reviews(Request $request): JsonResponse|Application|Factory|View
    {
        $reviews = UserGameTitleReview::where('is_deleted', false)
            ->where('is_hidden', false)
            ->orderByDesc('updated_at')
            ->with(['user', 'gameTitle', 'horrorTypeTags'])
            ->paginate(20);

        $pager = new Pager($reviews->currentPage(), $reviews->lastPage(), 'Game.Reviews');

        return $this->tree(
            view('game.reviews', compact('reviews', 'pager')),
        );
    }

    /**
     * タイトルのレビュー全件
     *
     * @param Request $request
     * @param string $titleKey
     * @return JsonResponse|Application|Factory|View
     */
    public function titleReviews(Request $request, string $titleKey): JsonResponse|Application|Factory|View
    {
        $title = GameTitle::findByKey($titleKey);
        if (!$title) {
            abort(404);
        }
        $franchise = $title->getFranchise();

        $reviews = UserGameTitleReview::where('game_title_id', $title->id)
            ->where('is_deleted', false)
            ->where('is_hidden', false)
            ->orderByDesc('updated_at')
            ->with(['user', 'horrorTypeTags'])
            ->paginate(10);

        $pager = new Pager($reviews->currentPage(), $reviews->lastPage(), 'Game.TitleReviews', ['titleKey' => $title->key]);

        return $this->tree(
            view('game.title_reviews', compact('title', 'franchise', 'reviews', 'pager')),
            options: ['ratingCheck' => $title->rating == \App\Enums\Rating::R18A],
        );
    }

    /**
     * タイトルのレビュー個別ページ
     *
     * @param Request $request
     * @param string $titleKey
     * @param string $showId
     * @return JsonResponse|Application|Factory|View
     */
    public function titleReview(Request $request, string $titleKey, string $showId): JsonResponse|Application|Factory|View
    {
        $title = GameTitle::findByKey($titleKey);
        if (!$title) {
            abort(404);
        }
        $franchise = $title->getFranchise();

        $reviewUser = \App\Models\User::where('show_id', $showId)->first();
        if (!$reviewUser) {
            abort(404);
        }

        $review = UserGameTitleReview::where('user_id', $reviewUser->id)
            ->where('game_title_id', $title->id)
            ->withCount('likes')
            ->with(['user', 'horrorTypeTags', 'packages.gamePackage.platform'])
            ->first();

        if (!$review) {
            abort(404);
        }

        $userLiked = false;
        $userReported = false;
        if (Auth::check() && !$review->is_deleted && !$review->is_hidden) {
            $userLiked = UserGameTitleReviewLike::where('user_id', Auth::id())
                ->where('review_id', $review->id)
                ->exists();
            $userReported = UserGameTitleReviewReport::where('user_id', Auth::id())
                ->where('review_id', $review->id)
                ->exists();
        }

        return $this->tree(
            view('game.title_review', compact('title', 'franchise', 'review', 'reviewUser', 'userLiked', 'userReported')),
            options: [
                'ratingCheck' => $title->rating == \App\Enums\Rating::R18A,
                'url' => route('Game.TitleReview', ['titleKey' => $title->key, 'showId' => $showId]),
                'components' => [
                    'ReviewReaction' => [],
                ],
            ],
        );
    }

    /**
     * タイトルの怖さメーターコメントログ
     *
     * @param Request $request
     * @param string $titleKey
     * @return JsonResponse|Application|Factory|View
     */
    public function titleFearMeterComments(Request $request, string $titleKey): JsonResponse|Application|Factory|View
    {
        $title = GameTitle::findByKey($titleKey);
        if (!$title) {
            abort(404);
        }
        $franchise = $title->getFranchise();

        $commentLogs = UserGameTitleFearMeterLog::query()
            ->visibleComments()
            ->where('game_title_id', $title->id)
            ->with('user')
            ->withCount(['likes', 'reports'])
            ->orderBy('created_at')
            ->paginate(30);
        $likedLogIds = [];
        $reportedLogIds = [];
        if (Auth::check() && $commentLogs->isNotEmpty()) {
            $logIds = $commentLogs->getCollection()->pluck('id')->toArray();
            $likedLogIds = UserGameTitleFearMeterCommentLike::query()
                ->where('user_id', Auth::id())
                ->whereIn('fear_meter_log_id', $logIds)
                ->pluck('fear_meter_log_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
            $reportedLogIds = UserGameTitleFearMeterCommentReport::query()
                ->where('reporter_user_id', Auth::id())
                ->whereIn('fear_meter_log_id', $logIds)
                ->pluck('fear_meter_log_id')
                ->map(fn ($id) => (int) $id)
                ->toArray();
        }

        $pager = new Pager($commentLogs->currentPage(), $commentLogs->lastPage(), 'Game.TitleFearMeterComments', ['titleKey' => $title->key]);

        return $this->tree(
            view('game.title_fear_meter_comments', compact('title', 'franchise', 'commentLogs', 'likedLogIds', 'reportedLogIds', 'pager')),
            options: [
                'ratingCheck' => $title->rating == Rating::R18A,
                'components' => [
                    'FearMeterCommentReaction' => [],
                ],
            ],
        );
    }

    /**
     * メディアミックスの詳細ネットワーク
     *
     * @param Request $request
     * @param string $mediaMixKey
     * @return JsonResponse|Application|Factory|View
     * @throws \Throwable
     */
    public function mediaMixDetail(Request $request, string $mediaMixKey): JsonResponse|Application|Factory|View
    {
        $mediaMix = GameMediaMix::findByKey($mediaMixKey);

        if (!$mediaMix) {
            abort(404);
        }

        $relatedNetworks = [];
        if ($mediaMix->mediaMixGroup !== null) {
            foreach ($mediaMix->mediaMixGroup->mediaMixes as $relatedMediaMix) {
                if ($relatedMediaMix->id === $mediaMix->id) {
                    continue;
                }
                $relatedNetworks[] = $relatedMediaMix;
            }
        }

        return $this->tree(view('game.media_mix_detail', [
            'mediaMix' => $mediaMix,
            'relatedNetworks' => $relatedNetworks,
        ]), options: ['ratingCheck' => $mediaMix->rating == Rating::R18A]);
    }
}
