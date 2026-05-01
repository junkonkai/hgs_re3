<?php

namespace App\Http\Controllers;

use App\Enums\DiscordChannel;
use App\Enums\Rating;
use App\Http\Requests\FearMeterCommentReportRequest;
use App\Models\GameTitle;
use App\Models\UserGameTitleFearMeterCommentLike;
use App\Models\UserGameTitleFearMeterCommentReport;
use App\Models\UserGameTitleFearMeterLog;
use App\Services\Discord\DiscordWebhookService;
use App\Support\Pager;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class GameFearMeterCommentController extends Controller
{
    /**
     * タイトルの怖さメーターコメントログ
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
     * コメントにいいねする
     *
     * @param string $titleKey
     * @param int $logId
     * @return RedirectResponse
     */
    public function like(string $titleKey, int $logId): RedirectResponse
    {
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }
        $title = GameTitle::findByKey($titleKey);
        if (!$title) {
            abort(404);
        }

        $log = UserGameTitleFearMeterLog::query()
            ->visibleComments()
            ->where('id', $logId)
            ->where('game_title_id', $title->id)
            ->firstOrFail();

        UserGameTitleFearMeterCommentLike::firstOrCreate([
            'fear_meter_log_id' => $log->id,
            'user_id' => $user->id,
        ], [
            'created_at' => now(),
        ]);

        return redirect()->back()
            ->with('success', 'いいねしました。');
    }

    /**
     * コメントのいいねを解除
     *
     * @param string $titleKey
     * @param int $logId
     * @return RedirectResponse
     */
    public function unlike(string $titleKey, int $logId): RedirectResponse
    {
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }
        $title = GameTitle::findByKey($titleKey);
        if (!$title) {
            abort(404);
        }

        UserGameTitleFearMeterCommentLike::query()
            ->where('fear_meter_log_id', $logId)
            ->where('user_id', $user->id)
            ->delete();

        return redirect()->back()
            ->with('success', 'いいねを解除しました。');
    }

    /**
     * コメントを通報
     *
     * @param FearMeterCommentReportRequest $request
     * @param string $titleKey
     * @param int $logId
     * @return RedirectResponse
     */
    public function report(FearMeterCommentReportRequest $request, string $titleKey, int $logId): RedirectResponse
    {
        $user = Auth::user();
        if ($user === null) {
            abort(403);
        }
        $title = GameTitle::findByKey($titleKey);
        if (!$title) {
            abort(404);
        }

        $log = UserGameTitleFearMeterLog::query()
            ->visibleComments()
            ->where('id', $logId)
            ->where('game_title_id', $title->id)
            ->firstOrFail();

        $report = UserGameTitleFearMeterCommentReport::firstOrCreate([
            'fear_meter_log_id' => $log->id,
            'reporter_user_id' => $user->id,
        ], [
            'reason' => $request->validated('reason'),
            'status' => 'open',
        ]);

        if ($report->wasRecentlyCreated) {
            try {
                $reason = $report->reason;
                $reasonText = $reason ? "\n通報理由: {$reason}" : '';
                $adminUrl = route('Admin.Manage.FearMeter.Reports', [$log->user_id, $log->game_title_id]);
                app(DiscordWebhookService::class)
                    ->to(DiscordChannel::Contact)
                    ->username('HGN 通報Bot')
                    ->send(
                        "怖さメーターコメントへの通報がありました。\n" .
                        "タイトル: {$title->name}\n" .
                        "コメントID: {$log->id}\n" .
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
