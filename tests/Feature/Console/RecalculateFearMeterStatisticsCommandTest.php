<?php

namespace Tests\Feature\Console;

use App\Enums\FearMeter;
use App\Models\FearMeterStatisticsDirtyTitle;
use App\Models\FearMeterStatisticsRunLog;
use App\Models\GameTitle;
use App\Models\GameTitleFearMeterStatistic;
use App\Models\User;
use App\Models\UserGameTitleFearMeter;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * 前提: テスト用DB（hgs_re3_test）に game_titles が少なくとも3件存在すること。
 * スキーマ復元で game_titles が空になる環境ではスキップされる。
 */
class RecalculateFearMeterStatisticsCommandTest extends TestCase
{
    use DatabaseTransactions;

    /**
     * 初回集計の実行と結果検証、続けて差分のみ再集計されることを検証する
     */
    public function test_recalculate_statistics_first_run_then_only_delta_recalculated(): void
    {
        $gameTitleIds = GameTitle::orderBy('id')->take(5)->pluck('id')->toArray();
        if (count($gameTitleIds) < 3) {
            $this->markTestSkipped('テストには少なくとも3件の既存 game_titles が必要です。hgs_re3_test に game_titles のデータがあることを確認してください。');
        }

        $firstRunTitleIds = array_slice($gameTitleIds, 0, 2);
        $secondRunTitleId = $gameTitleIds[2];

        $users = User::factory()->count(3)->create();

        // 初回用: 2タイトルに評価を登録（値は固定で期待値を計算しやすくする）
        // タイトル1: 0, 1, 2 -> 平均 1.00, floor=1, total=3
        // タイトル2: 2, 2, 4 -> 平均 2.67, round=3, total=3
        $ratingsFirstTitle = [0, 1, 2];
        $ratingsSecondTitle = [2, 2, 4];

        foreach ($firstRunTitleIds as $index => $gameTitleId) {
            $ratings = $index === 0 ? $ratingsFirstTitle : $ratingsSecondTitle;
            foreach ($users as $i => $user) {
                UserGameTitleFearMeter::create([
                    'user_id' => $user->id,
                    'game_title_id' => $gameTitleId,
                    'fear_meter' => $ratings[$i],
                ]);
            }
        }

        // 初回集計実行
        $this->artisan('fear-meter:recalculate-statistics')->assertSuccessful();

        // 初回 assert: 2タイトル分の統計が作成されている
        $stat1 = GameTitleFearMeterStatistic::find($firstRunTitleIds[0]);
        $this->assertNotNull($stat1);
        $this->assertSame(3, $stat1->total_count);
        $this->assertSame('1.00', (string) $stat1->average_rating);
        $this->assertSame(1, $stat1->fear_meter->value);
        $this->assertSame(1, $stat1->rating_0_count);
        $this->assertSame(1, $stat1->rating_1_count);
        $this->assertSame(1, $stat1->rating_2_count);
        $this->assertSame(0, $stat1->rating_3_count);
        $this->assertSame(0, $stat1->rating_4_count);

        $stat2 = GameTitleFearMeterStatistic::find($firstRunTitleIds[1]);
        $this->assertNotNull($stat2);
        $this->assertSame(3, $stat2->total_count);
        $this->assertSame('2.67', (string) $stat2->average_rating);
        $this->assertSame(3, $stat2->fear_meter->value);
        $this->assertSame(0, $stat2->rating_0_count);
        $this->assertSame(0, $stat2->rating_1_count);
        $this->assertSame(2, $stat2->rating_2_count);
        $this->assertSame(0, $stat2->rating_3_count);
        $this->assertSame(1, $stat2->rating_4_count);

        $runLog = FearMeterStatisticsRunLog::first();
        $this->assertNotNull($runLog);
        $this->assertNotNull($runLog->last_completed_at);

        // last_completed_at より後に更新が入ったと判定されるよう、1秒待ってから追加する
        sleep(1);

        // 2回目用: 別の既存タイトルに評価を追加（1ユーザーで 3 を登録 -> 平均 3.00）
        UserGameTitleFearMeter::create([
            'user_id' => $users[0]->id,
            'game_title_id' => $secondRunTitleId,
            'fear_meter' => FearMeter::VeryScary->value,
        ]);

        $updatedAt1Before = $stat1->getRawOriginal('updated_at');
        $updatedAt2Before = $stat2->getRawOriginal('updated_at');

        // 2回目集計実行
        $this->artisan('fear-meter:recalculate-statistics')->assertSuccessful();

        // 2回目 assert: 追加したタイトルのみ統計が作成されている
        $statNew = GameTitleFearMeterStatistic::find($secondRunTitleId);
        $this->assertNotNull($statNew);
        $this->assertSame(1, $statNew->total_count);
        $this->assertSame('3.00', (string) $statNew->average_rating);
        $this->assertSame(3, $statNew->fear_meter->value);

        // 初回で集計した2タイトルの updated_at は変わっていない（差分のみ再集計のため）
        $stat1->refresh();
        $stat2->refresh();
        $this->assertSame($updatedAt1Before, $stat1->getRawOriginal('updated_at'));
        $this->assertSame($updatedAt2Before, $stat2->getRawOriginal('updated_at'));
    }

    /**
     * 削除で dirty 登録されたタイトルが再集計対象となり、件数0なら統計が消えることを検証する
     */
    public function test_recalculate_statistics_handles_dirty_title_and_deletes_zero_count_statistic(): void
    {
        $gameTitleId = GameTitle::orderBy('id')->value('id');
        if ($gameTitleId === null) {
            $this->markTestSkipped('テストには少なくとも1件の既存 game_titles が必要です。');
        }
        if (!Schema::hasTable('fear_meter_statistics_dirty_titles')) {
            $this->markTestSkipped('fear_meter_statistics_dirty_titles テーブルが存在しないためスキップします。');
        }

        $user = User::factory()->create();
        UserGameTitleFearMeter::create([
            'user_id' => $user->id,
            'game_title_id' => $gameTitleId,
            'fear_meter' => FearMeter::VeryScary->value,
        ]);

        $this->artisan('fear-meter:recalculate-statistics', ['--force-full' => true])->assertSuccessful();
        $this->assertNotNull(GameTitleFearMeterStatistic::find($gameTitleId));

        UserGameTitleFearMeter::where('user_id', $user->id)
            ->where('game_title_id', $gameTitleId)
            ->delete();
        FearMeterStatisticsDirtyTitle::updateOrCreate(['game_title_id' => $gameTitleId], []);

        $this->artisan('fear-meter:recalculate-statistics')->assertSuccessful();

        $this->assertNull(GameTitleFearMeterStatistic::find($gameTitleId));
        $this->assertFalse(FearMeterStatisticsDirtyTitle::where('game_title_id', $gameTitleId)->exists());
    }
}
