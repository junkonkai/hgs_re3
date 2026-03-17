<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * game_titles の updated_at の最大値を、紐づく game_franchises.last_title_update_at に反映する。
     * 紐づきは game_franchise_id 直付け または game_series 経由のいずれか。
     */
    public function up(): void
    {
        DB::statement("
            UPDATE game_franchises gf
            SET gf.last_title_update_at = (
                SELECT MAX(gt.updated_at)
                FROM game_titles gt
                LEFT JOIN game_series gs ON gt.game_series_id = gs.id
                WHERE gt.game_franchise_id = gf.id
                   OR gs.game_franchise_id = gf.id
            )
        ");
    }

    /**
     * Reverse the migrations.
     * データ反映のロールバックは last_title_update_at を null に戻す。
     */
    public function down(): void
    {
        DB::statement('UPDATE game_franchises SET last_title_update_at = NULL');
    }
};
