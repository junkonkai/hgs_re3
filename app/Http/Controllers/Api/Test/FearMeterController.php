<?php

namespace App\Http\Controllers\Api\Test;

use App\Models\FearMeterStatisticsRunLog;
use App\Models\FearMeterStatisticsDirtyTitle;
use App\Models\GameTitle;
use App\Models\GameTitleFearMeterStatistic;
use App\Models\UserGameTitleFearMeter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;

class FearMeterController extends BaseTestController
{
    /**
     * ローカル環境専用：怖さメーター統計の再集計を実行するAPI
     * （Artisan::call は Web リクエスト時に問題を起こすため、ここでは同じロジックを直接実行する）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recalculate(Request $request): JsonResponse
    {
        $forceFull = $request->boolean('force_full', false);

        $runLog = FearMeterStatisticsRunLog::first();
        $lastCompletedAt = $forceFull ? null : $runLog?->last_completed_at;

        $query = UserGameTitleFearMeter::query()->distinct();
        if ($lastCompletedAt !== null) {
            $query->where('updated_at', '>', $lastCompletedAt);
        }
        $meterUpdatedGameTitleIds = $query->pluck('game_title_id')->toArray();
        $dirtyGameTitleIds = [];
        if (Schema::hasTable('fear_meter_statistics_dirty_titles')) {
            $dirtyGameTitleIds = FearMeterStatisticsDirtyTitle::query()
                ->pluck('game_title_id')
                ->toArray();
        }
        $gameTitleIds = array_values(array_unique(array_merge($meterUpdatedGameTitleIds, $dirtyGameTitleIds)));

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
                $statistic = GameTitleFearMeterStatistic::firstOrNew(['game_title_id' => $gameTitleId]);
                $statistic->game_title_id = $gameTitleId;
                $statistic->recalculate();
                $successCount++;
            } catch (\Throwable $e) {
                report($e);
            }
        }

        if (!empty($gameTitleIds) && Schema::hasTable('fear_meter_statistics_dirty_titles')) {
            FearMeterStatisticsDirtyTitle::whereIn('game_title_id', $gameTitleIds)->delete();
        }

        $this->updateRunLog();

        return response()->json([
            'message' => '再集計が完了しました。',
            'success_count' => $successCount,
        ]);
    }

    /**
     * 集計完了時刻を run_log に記録する
     */
    private function updateRunLog(): void
    {
        $runLog = FearMeterStatisticsRunLog::first();
        if ($runLog === null) {
            FearMeterStatisticsRunLog::create(['last_completed_at' => now()]);
            return;
        }
        $runLog->last_completed_at = now();
        $runLog->save();
    }

    /**
     * ローカル環境専用：指定タイトルの怖さメーター集計結果を取得するAPI
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

        $statistic = GameTitleFearMeterStatistic::find($title->id);

        if (!$statistic) {
            return response()->json([
                'message' => '集計結果がありません。',
            ], 404);
        }

        return response()->json([
            'title_key' => $title->key,
            'title_name' => $title->name,
            'fear_meter' => $statistic->fear_meter->value,
            'fear_meter_text' => $statistic->fear_meter->text(),
            'average_rating' => (float) $statistic->average_rating,
            'total_count' => $statistic->total_count,
        ]);
    }
}
