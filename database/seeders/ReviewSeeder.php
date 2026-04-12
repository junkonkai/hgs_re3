<?php

namespace Database\Seeders;

use App\Models\GameTitle;
use App\Models\User;
use App\Models\UserGameTitleReview;
use Illuminate\Database\Seeder;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::inRandomOrder()->limit(20)->get();

        if ($users->isEmpty()) {
            $this->command->warn('ユーザーが存在しないためレビューを作成できませんでした。先に UserSeeder を実行してください。');
            return;
        }

        $gameTitleIds = GameTitle::limit(100)->pluck('id')->toArray();

        if (empty($gameTitleIds)) {
            $this->command->warn('game_titles が存在しないためレビューを作成できませんでした。');
            return;
        }

        foreach ($users as $user) {
            $existingIds = UserGameTitleReview::where('user_id', $user->id)
                ->pluck('game_title_id')
                ->toArray();

            $availableIds = array_values(array_diff($gameTitleIds, $existingIds));

            if (empty($availableIds)) {
                continue;
            }

            $count = fake()->numberBetween(3, 8);
            $selectedIds = (array) array_rand(array_flip($availableIds), min($count, count($availableIds)));

            foreach ($selectedIds as $gameTitleId) {
                UserGameTitleReview::factory()->create([
                    'user_id'       => $user->id,
                    'game_title_id' => $gameTitleId,
                ]);
            }
        }

        $this->command->info('ReviewSeeder 完了: ' . UserGameTitleReview::count() . ' 件のレビューを登録しました。');
    }
}
