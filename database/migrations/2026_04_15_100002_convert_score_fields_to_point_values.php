<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('UPDATE user_game_title_reviews
            SET score_story      = score_story * 5,
                score_atmosphere = score_atmosphere * 5,
                score_gameplay   = score_gameplay * 5');

        DB::statement('UPDATE user_game_title_review_drafts
            SET score_story      = score_story * 5,
                score_atmosphere = score_atmosphere * 5,
                score_gameplay   = score_gameplay * 5');

        DB::statement('UPDATE user_game_title_review_logs
            SET score_story      = score_story * 5,
                score_atmosphere = score_atmosphere * 5,
                score_gameplay   = score_gameplay * 5');
    }

    public function down(): void
    {
        DB::statement('UPDATE user_game_title_reviews
            SET score_story      = score_story / 5,
                score_atmosphere = score_atmosphere / 5,
                score_gameplay   = score_gameplay / 5');

        DB::statement('UPDATE user_game_title_review_drafts
            SET score_story      = score_story / 5,
                score_atmosphere = score_atmosphere / 5,
                score_gameplay   = score_gameplay / 5');

        DB::statement('UPDATE user_game_title_review_logs
            SET score_story      = score_story / 5,
                score_atmosphere = score_atmosphere / 5,
                score_gameplay   = score_gameplay / 5');
    }
};
