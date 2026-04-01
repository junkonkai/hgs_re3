<?php

namespace App\Http\Controllers;

use App\Http\Requests\FearMeterCommentReportRequest;
use App\Models\GameTitle;
use App\Models\UserGameTitleFearMeterCommentLike;
use App\Models\UserGameTitleFearMeterCommentReport;
use App\Models\UserGameTitleFearMeterLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class GameFearMeterCommentController extends Controller
{
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

        UserGameTitleFearMeterCommentReport::firstOrCreate([
            'fear_meter_log_id' => $log->id,
            'reporter_user_id' => $user->id,
        ], [
            'reason' => $request->validated('reason'),
            'status' => 'open',
        ]);

        return redirect()->back()
            ->with('success', '通報しました。');
    }
}
