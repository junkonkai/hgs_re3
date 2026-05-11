<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Sqids\Sqids;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('user_game_title_reviews', 'key')) {
            Schema::table('user_game_title_reviews', function (Blueprint $table) {
                $table->string('key', 20)->nullable()->unique()->after('id');
            });
        }

        $sqids = new Sqids(
            alphabet: config('services.sqids.alphabet'),
            minLength: config('services.sqids.min_length'),
        );

        DB::table('user_game_title_reviews')->orderBy('id')->each(function ($row) use ($sqids) {
            DB::table('user_game_title_reviews')
                ->where('id', $row->id)
                ->update(['key' => $sqids->encode([$row->game_title_id, $row->user_id])]);
        });
    }

    public function down(): void
    {
        Schema::table('user_game_title_reviews', function (Blueprint $table) {
            $table->dropColumn('key');
        });
    }
};
