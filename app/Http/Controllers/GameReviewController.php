<?php

namespace App\Http\Controllers;

use App\Enums\DiscordChannel;
use App\Enums\Rating;
use App\Http\Requests\ReviewReportRequest;
use App\Models\GameTitle;
use App\Models\UserGameTitleFearMeter;
use App\Models\UserGameTitleFearMeterLog;
use App\Models\UserGameTitleReview;
use App\Models\UserGameTitleReviewLike;
use App\Models\UserGameTitleReviewReport;
use App\Services\Discord\DiscordWebhookService;
use App\Support\Pager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameReviewController extends Controller
{
    /**
     * レビュー一覧（全タイトル）
     */
    public function reviews(Request $request): JsonResponse|Application|Factory|View
    {
        $sort = $request->input('sort', 'newest');
        $allowedSorts = ['newest', 'score', 'fear', 'story', 'atmosphere', 'gameplay'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'newest';
        }

        $query = GameTitle::query()
            ->join('game_title_review_statistics as rs', 'game_titles.id', '=', 'rs.game_title_id')
            ->leftJoin('game_title_fear_meter_statistics as fms', 'game_titles.id', '=', 'fms.game_title_id')
            ->select([
                'game_titles.id',
                'game_titles.key',
                'game_titles.name',
                'rs.review_count',
                'rs.avg_total_score',
                'rs.avg_story',
                'rs.avg_atmosphere',
                'rs.avg_gameplay',
                'rs.updated_at as latest_review_at',
                'fms.fear_meter',
                'fms.average_rating as fear_meter_avg',
            ]);

        match ($sort) {
            'score'      => $query->orderByRaw('rs.avg_total_score IS NULL, rs.avg_total_score DESC'),
            'fear'       => $query->orderByRaw('fms.average_rating IS NULL, fms.average_rating DESC'),
            'story'      => $query->orderByRaw('rs.avg_story IS NULL, rs.avg_story DESC'),
            'atmosphere' => $query->orderByRaw('rs.avg_atmosphere IS NULL, rs.avg_atmosphere DESC'),
            'gameplay'   => $query->orderByRaw('rs.avg_gameplay IS NULL, rs.avg_gameplay DESC'),
            default      => $query->orderByDesc('rs.updated_at'),
        };

        $titles = $query->paginate(20);

        $pager = new Pager(
            $titles->currentPage(),
            $titles->lastPage(),
            'Game.Reviews',
            $sort !== 'newest' ? ['sort' => $sort] : [],
            'children',
        );

        return $this->tree(
            view('game.reviews', compact('titles', 'pager', 'sort')),
        );
    }

    /**
     * タイトルのレビュー全件
     */
    public function titleReviews(Request $request, string $titleKey): JsonResponse|Application|Factory|View
    {
        $title = GameTitle::findByKey($titleKey);
        if (!$title) {
            abort(404);
        }
        $franchise = $title->getFranchise();

        $title->loadMissing(['reviewStatistic', 'fearMeterStatistic']);

        $reviews = UserGameTitleReview::where('game_title_id', $title->id)
            ->where('is_deleted', false)
            ->where('is_hidden', false)
            ->orderByDesc('updated_at')
            ->with(['user'])
            ->paginate(10);

        $fearMeters = UserGameTitleFearMeter::where('game_title_id', $title->id)
            ->whereIn('user_id', $reviews->pluck('user_id')->filter())
            ->get()
            ->keyBy('user_id');

        $pager = new Pager($reviews->currentPage(), $reviews->lastPage(), 'Game.TitleReviews', ['titleKey' => $title->key], 'children');

        $myReview = null;
        if (Auth::check()) {
            $myReview = UserGameTitleReview::where('user_id', Auth::id())
                ->where('game_title_id', $title->id)
                ->where('is_deleted', false)
                ->first();
        }

        return $this->tree(
            view('game.title_reviews', compact('title', 'franchise', 'reviews', 'fearMeters', 'pager', 'myReview')),
            options: ['ratingCheck' => $title->rating == Rating::R18A],
        );
    }

    /**
     * タイトルのレビュー個別ページ
     */
    public function titleReview(Request $request, string $titleKey, string $reviewKey): JsonResponse|Application|Factory|View
    {
        $title = GameTitle::findByKey($titleKey);
        if (!$title) {
            abort(404);
        }
        $franchise = $title->getFranchise();

        $review = UserGameTitleReview::where('key', $reviewKey)
            ->where('game_title_id', $title->id)
            ->withCount('likes')
            ->with(['user', 'packages.gamePackage.platform'])
            ->first();

        if (!$review) {
            abort(404);
        }

        $reviewUser = $review->user;

        $fearMeter = UserGameTitleFearMeter::where('user_id', $reviewUser->id)
            ->where('game_title_id', $title->id)
            ->first();

        $fearMeterComment = null;
        if ($fearMeter !== null) {
            $fearMeterComment = UserGameTitleFearMeterLog::where('user_id', $reviewUser->id)
                ->where('game_title_id', $title->id)
                ->visibleComments()
                ->latest('id')
                ->value('comment');
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
            view('game.title_review', compact('title', 'franchise', 'review', 'reviewUser', 'userLiked', 'userReported', 'fearMeter', 'fearMeterComment')),
            options: [
                'ratingCheck' => $title->rating == Rating::R18A,
                'url' => route('Game.TitleReview', ['titleKey' => $title->key, 'reviewKey' => $review->key]),
                'components' => [
                    'ReviewReaction' => [],
                    'SpoilerToggle' => [],
                ],
            ],
        );
    }


    /**
     * レビューにいいねする
     *
     * @param string $titleKey
     * @param int $reviewId
     * @return RedirectResponse
     */
    public function like(string $titleKey, int $reviewId): RedirectResponse
    {
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }
        $title = GameTitle::findByKey($titleKey);
        if (!$title) {
            abort(404);
        }

        $review = UserGameTitleReview::where('id', $reviewId)
            ->where('game_title_id', $title->id)
            ->where('is_deleted', false)
            ->where('is_hidden', false)
            ->firstOrFail();

        UserGameTitleReviewLike::firstOrCreate([
            'user_id'   => $user->id,
            'review_id' => $review->id,
        ], [
            'review_log_id' => $review->current_log_id,
            'created_at'    => now(),
        ]);

        return redirect()->back()
            ->with('success', 'いいねしました。');
    }

    /**
     * レビューのいいねを解除する
     *
     * @param string $titleKey
     * @param int $reviewId
     * @return RedirectResponse
     */
    public function unlike(string $titleKey, int $reviewId): RedirectResponse
    {
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }

        UserGameTitleReviewLike::where('user_id', $user->id)
            ->where('review_id', $reviewId)
            ->delete();

        return redirect()->back()
            ->with('success', 'いいねを解除しました。');
    }

    /**
     * レビューを通報する
     *
     * @param ReviewReportRequest $request
     * @param string $titleKey
     * @param int $reviewId
     * @return RedirectResponse
     */
    public function report(ReviewReportRequest $request, string $titleKey, int $reviewId): RedirectResponse
    {
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }
        $title = GameTitle::findByKey($titleKey);
        if (!$title) {
            abort(404);
        }

        $review = UserGameTitleReview::where('id', $reviewId)
            ->where('game_title_id', $title->id)
            ->where('is_deleted', false)
            ->where('is_hidden', false)
            ->firstOrFail();

        $reasonParts = $request->validated('reason_types', []) ?? [];
        $note = $request->validated('reason_note');
        if ($note !== null && $note !== '') {
            $reasonParts[] = '詳細: ' . $note;
        }
        $reason = !empty($reasonParts) ? implode('、', $reasonParts) : null;

        $report = UserGameTitleReviewReport::firstOrCreate(
            [
                'user_id'   => $user->id,
                'review_id' => $review->id,
            ],
            [
                'review_log_id' => $review->current_log_id,
                'reason'        => $reason,
                'is_resolved'   => false,
            ]
        );

        if ($report->wasRecentlyCreated) {
            try {
                $reasonText = $reason ? "\n通報理由: {$reason}" : '';
                $adminUrl = route('Admin.Manage.Review.Reports', $review->id);
                app(DiscordWebhookService::class)
                    ->to(DiscordChannel::Contact)
                    ->username('HGN 通報Bot')
                    ->send(
                        "レビューへの通報がありました。\n" .
                        "タイトル: {$title->name}\n" .
                        "レビューID: {$review->id}\n" .
                        "通報者ID: {$user->id}" .
                        $reasonText .
                        "\n通報一覧: {$adminUrl}"
                    );
            } catch (\Throwable) {
                // 通知失敗は無視
            }
        }

        return redirect()->back()
            ->with('success', '通報しました。');
    }
}
