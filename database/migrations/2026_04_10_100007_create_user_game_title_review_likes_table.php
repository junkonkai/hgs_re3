<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_title_review_likes', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->unsignedBigInteger('user_id')->comment('いいねしたユーザーID');
            $table->unsignedBigInteger('review_id')->comment('レビューID');
            $table->unsignedBigInteger('review_log_id')->comment('いいねした時点のバージョンID');
            $table->timestamp('created_at')->comment('作成日時');

            $table->unique(['user_id', 'review_id'], 'ugtrevlk_user_rev_unique');
            $table->index('review_id', 'ugtrevlk_rev_idx');
            $table->index('user_id', 'ugtrevlk_user_idx');

            $table->foreign('user_id', 'ugtrevlk_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('review_id', 'ugtrevlk_rev_fk')
                ->references('id')
                ->on('user_game_title_reviews')
                ->cascadeOnDelete();
            $table->foreign('review_log_id', 'ugtrevlk_log_fk')
                ->references('id')
                ->on('user_game_title_review_logs')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_title_review_likes');
    }
};
