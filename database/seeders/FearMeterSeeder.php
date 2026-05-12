<?php

namespace Database\Seeders;

use App\Models\GameTitle;
use App\Models\User;
use App\Models\UserGameTitleFearMeter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FearMeterSeeder extends Seeder
{
    public function run(): void
    {
        $userIds = User::pluck('id')->toArray();
        $gameTitleIds = GameTitle::limit(100)->pluck('id')->toArray();

        if (empty($userIds) || empty($gameTitleIds)) {
            $this->command->warn('ユーザーまたは game_titles が存在しないため怖さメーターを作成できませんでした。');
            return;
        }

        $rows = [];
        $now  = now();

        foreach ($userIds as $userId) {
            $count = fake()->numberBetween(3, 10);
            $selectedIds = (array) array_rand(array_flip($gameTitleIds), min($count, count($gameTitleIds)));

            foreach ($selectedIds as $gameTitleId) {
                $rows[] = [
                    'user_id'       => $userId,
                    'game_title_id' => $gameTitleId,
                    'fear_meter'    => fake()->numberBetween(0, 4),
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ];
            }
        }

        // 複合PKの重複を避けるため insertOrIgnore を使用
        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('user_game_title_fear_meters')->insertOrIgnore($chunk);
        }

        $this->command->info('FearMeterSeeder 完了: ' . UserGameTitleFearMeter::count() . ' 件の怖さメーターを登録しました。');
    }
}
