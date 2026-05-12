<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_title_review_drafts', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->unsignedBigInteger('user_id')->comment('ユーザーID');
            $table->unsignedInteger('game_title_id')->comment('ゲームタイトルID');
            $table->unsignedBigInteger('review_id')->nullable()->comment('レビューID（NULLなら新規投稿の下書き、値ありなら編集中の下書き）');
            $table->string('play_status', 32)->nullable()->comment('プレイ状況');
            $table->string('play_time', 32)->nullable()->comment('プレイ時間');
            $table->text('body')->nullable()->comment('本文');
            $table->boolean('has_spoiler')->default(false)->comment('ネタバレフラグ');
            $table->unsignedTinyInteger('score_story')->nullable()->comment('ストーリースコア（0〜4）');
            $table->unsignedTinyInteger('score_atmosphere')->nullable()->comment('雰囲気・演出スコア（0〜4）');
            $table->unsignedTinyInteger('score_gameplay')->nullable()->comment('ゲーム性スコア（0〜4）');
            $table->smallInteger('user_score_adjustment')->nullable()->comment('ユーザー調整値（−20〜+20）');
            $table->timestamps();

            $table->unique(['user_id', 'game_title_id'], 'ugtrevdft_user_title_unique');
            $table->index('user_id', 'ugtrevdft_user_idx');
            $table->index('game_title_id', 'ugtrevdft_title_idx');

            $table->foreign('user_id', 'ugtrevdft_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('game_title_id', 'ugtrevdft_title_fk')
                ->references('id')
                ->on('game_titles')
                ->cascadeOnDelete();
            $table->foreign('review_id', 'ugtrevdft_rev_fk')
                ->references('id')
                ->on('user_game_title_reviews')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_title_review_drafts');
    }
};
