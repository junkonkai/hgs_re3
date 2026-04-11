<?php

namespace App\Http\Controllers\Api\Test;

use App\Models\GameTitle;
use App\Models\GameTitleReviewStatistic;
use App\Models\ReviewStatisticsDirtyTitle;
use App\Models\ReviewStatisticsRunLog;
use App\Models\UserGameTitleReview;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class ReviewController extends BaseTestController
{
    /**
     * ローカル環境専用：レビュー統計の再集計を実行するAPI
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recalculate(Request $request): JsonResponse
    {
        $forceFull = $request->boolean('force_full', false);

        $runLog = ReviewStatisticsRunLog::first();
        $lastCompletedAt = $forceFull ? null : $runLog?->last_completed_at;

        $query = UserGameTitleReview::query()->distinct();
        if ($lastCompletedAt !== null) {
            $query->where('updated_at', '>', $lastCompletedAt);
        }
        $reviewUpdatedGameTitleIds = $query->pluck('game_title_id')->toArray();

        $dirtyGameTitleIds = [];
        if (Schema::hasTable('review_statistics_dirty_titles')) {
            $dirtyGameTitleIds = ReviewStatisticsDirtyTitle::query()
                ->pluck('game_title_id')
                ->toArray();
        }

        $gameTitleIds = array_values(array_unique(array_merge($reviewUpdatedGameTitleIds, $dirtyGameTitleIds)));

        if (empty($gameTitleIds)) {
            $this->updateRunLog();

            return response()->json([
                'message' => '再集計する対象がありません。',
                'success_count' => 0,
            ]);
        }

        $successCount = 0;
        foreach ($gameTitleIds as $gameTitleId) {
            try {
                $statistic = GameTitleReviewStatistic::firstOrNew(['game_title_id' => $gameTitleId]);
                $statistic->game_title_id = $gameTitleId;
                $statistic->recalculate();
                $successCount++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (!empty($gameTitleIds) && Schema::hasTable('review_statistics_dirty_titles')) {
            ReviewStatisticsDirtyTitle::whereIn('game_title_id', $gameTitleIds)->delete();
        }

        $this->updateRunLog();

        return response()->json([
            'message' => '再集計が完了しました。',
            'success_count' => $successCount,
        ]);
    }

    /**
     * run_log の完了時刻を更新する
     */
    private function updateRunLog(): void
    {
        $runLog = ReviewStatisticsRunLog::first();
        if ($runLog === null) {
            ReviewStatisticsRunLog::create(['last_completed_at' => now()]);
            return;
        }
        $runLog->last_completed_at = now();
        $runLog->save();
    }

    /**
     * ローカル環境専用：指定タイトルのレビュー集計結果を取得するAPI
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function statistics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title_key' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => '無効な入力です。',
                'errors' => $validator->errors(),
            ], 422);
        }

        $title = GameTitle::findByKey($validator->validated()['title_key']);

        if (!$title) {
            return response()->json([
                'message' => 'タイトルが見つかりません。',
            ], 404);
        }

        $statistic = GameTitleReviewStatistic::find($title->id);

        if (!$statistic) {
            return response()->json([
                'message' => '集計結果がありません。',
            ], 404);
        }

        return response()->json([
            'title_key'       => $title->key,
            'title_name'      => $title->name,
            'review_count'    => (int) $statistic->review_count,
            'avg_total_score' => $statistic->avg_total_score !== null ? (float) $statistic->avg_total_score : null,
            'avg_story'       => $statistic->avg_story !== null ? (float) $statistic->avg_story : null,
            'avg_atmosphere'  => $statistic->avg_atmosphere !== null ? (float) $statistic->avg_atmosphere : null,
            'avg_gameplay'    => $statistic->avg_gameplay !== null ? (float) $statistic->avg_gameplay : null,
        ]);
    }
}
