<?php

namespace App\Console\Commands;

use App\Models\ReviewStatisticsDirtyTitle;
use App\Models\ReviewStatisticsRunLog;
use App\Models\GameTitleReviewStatistic;
use App\Models\UserGameTitleReview;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class RecalculateReviewStatisticsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'review:recalculate-statistics {--force-full : 強制全部再集計（全データを再集計する）} {--no-progress : プログレスバーを表示しない（API等から呼び出す場合に使用）}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'レビューが登録されている全てのゲームタイトルのレビュー統計を再集計する（前回実行以降に更新があったもののみ対象）';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $runLog = ReviewStatisticsRunLog::first();
        $lastCompletedAt = $runLog?->last_completed_at;

        if ($this->option('force-full')) {
            $lastCompletedAt = null;
        }

        // 前回集計以降に更新があったgame_title_idのみ取得（初回は全件）
        $query = UserGameTitleReview::query()->select('game_title_id')->distinct();
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
            $message = '再集計する対象がありません。';
            $this->info($message);
            Log::info($message);

            $this->updateRunLog();

            return 0;
        }

        $totalCount = count($gameTitleIds);
        $message = "合計 {$totalCount} 件のゲームタイトルのレビュー統計を再集計します。";
        $this->info($message);
        Log::info($message);

        $useProgressBar = !$this->option('no-progress');
        $bar = $useProgressBar ? $this->output->createProgressBar($totalCount) : null;
        if ($bar !== null) {
            $bar->start();
        }

        $successCount = 0;
        $errorCount = 0;

        foreach ($gameTitleIds as $gameTitleId) {
            try {
                $statistic = GameTitleReviewStatistic::firstOrNew(['game_title_id' => $gameTitleId]);
                $statistic->game_title_id = $gameTitleId;
                $statistic->recalculate();
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $errorMessage = "ゲームタイトル ID: {$gameTitleId} の再集計中にエラーが発生しました: " . $e->getMessage();
                $this->newLine();
                $this->error($errorMessage);
                Log::error($errorMessage, [
                    'game_title_id' => $gameTitleId,
                    'exception' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }

            if ($bar !== null) {
                $bar->advance();
            }
        }

        if (!empty($gameTitleIds) && Schema::hasTable('review_statistics_dirty_titles')) {
            ReviewStatisticsDirtyTitle::whereIn('game_title_id', $gameTitleIds)->delete();
        }

        if ($bar !== null) {
            $bar->finish();
            $this->newLine(2);
        }

        $completionMessage = "再集計が完了しました。成功: {$successCount} 件";
        $this->info("再集計が完了しました。");
        $this->info("成功: {$successCount} 件");
        Log::info($completionMessage, [
            'success_count' => $successCount,
            'error_count' => $errorCount,
        ]);

        if ($errorCount > 0) {
            $errorMessage = "エラー: {$errorCount} 件";
            $this->warn($errorMessage);
            Log::warning($errorMessage, ['error_count' => $errorCount]);
        }

        $this->updateRunLog();

        return 0;
    }

    /**
     * 集計完了時刻を run_log に記録する
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
}
