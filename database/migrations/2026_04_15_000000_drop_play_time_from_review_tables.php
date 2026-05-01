<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_game_title_reviews', function (Blueprint $table) {
            $table->dropColumn('play_time');
        });

        Schema::table('user_game_title_review_drafts', function (Blueprint $table) {
            $table->dropColumn('play_time');
        });

        Schema::table('user_game_title_review_logs', function (Blueprint $table) {
            $table->dropColumn('play_time');
        });
    }

    public function down(): void
    {
        Schema::table('user_game_title_reviews', function (Blueprint $table) {
            $table->string('play_time')->nullable()->after('play_status');
        });

        Schema::table('user_game_title_review_drafts', function (Blueprint $table) {
            $table->string('play_time')->nullable()->after('play_status');
        });

        Schema::table('user_game_title_review_logs', function (Blueprint $table) {
            $table->string('play_time')->nullable()->after('play_status');
        });
    }
};
