<?php

namespace App\Http\Controllers;

use App\Enums\DiscordChannel;
use App\Http\Requests\ReviewReportRequest;
use App\Models\GameTitle;
use App\Models\UserGameTitleReview;
use App\Models\UserGameTitleReviewLike;
use App\Models\UserGameTitleReviewReport;
use App\Services\Discord\DiscordWebhookService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class GameReviewController extends Controller
{
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

        [$report, $created] = UserGameTitleReviewReport::firstOrCreate(
            [
                'user_id'   => $user->id,
                'review_id' => $review->id,
            ],
            [
                'review_log_id' => $review->current_log_id,
                'reason'        => $request->validated('reason'),
                'is_resolved'   => false,
            ]
        );

        if ($created) {
            try {
                app(DiscordWebhookService::class)
                    ->to(DiscordChannel::Contact)
                    ->username('HGN 通報Bot')
                    ->send(
                        "レビューへの通報がありました。\n" .
                        "タイトル: {$title->name}\n" .
                        "レビューID: {$review->id}\n" .
                        "通報者ID: {$user->id}"
                    );
            } catch (\Throwable) {
                // 通知失敗は無視
            }
        }

        return redirect()->back()
            ->with('success', '通報しました。');
    }
}
