<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_title_review_logs', function (Blueprint $table) {
            $table->id()->comment('ID（バージョンIDとして使用）');
            $table->unsignedBigInteger('review_id')->comment('レビューID');
            $table->unsignedBigInteger('user_id')->comment('ユーザーID');
            $table->unsignedInteger('version')->comment('レビューごとの連番（1始まり）');
            $table->string('play_status', 32)->comment('プレイ状況');
            $table->string('play_time', 32)->nullable()->comment('プレイ時間');
            $table->json('game_package_ids')->nullable()->comment('ゲームパッケージIDの配列（スナップショット）');
            $table->text('body')->comment('本文');
            $table->boolean('has_spoiler')->default(false)->comment('ネタバレフラグ');
            $table->unsignedTinyInteger('score_story')->nullable()->comment('ストーリースコア（0〜4）');
            $table->unsignedTinyInteger('score_atmosphere')->nullable()->comment('雰囲気・演出スコア（0〜4）');
            $table->unsignedTinyInteger('score_gameplay')->nullable()->comment('ゲーム性スコア（0〜4）');
            $table->smallInteger('user_score_adjustment')->nullable()->comment('ユーザー調整値（−20〜+20）');
            $table->unsignedTinyInteger('base_score')->nullable()->comment('ベーススコア（0〜100）');
            $table->unsignedTinyInteger('total_score')->nullable()->comment('総合スコア（0〜100）');
            $table->json('horror_type_tags')->nullable()->comment('ホラー種別タグの配列（スナップショット）');
            $table->timestamp('created_at')->comment('編集日時');

            $table->unique(['review_id', 'version'], 'ugtrevlog_rev_ver_unique');
            $table->index('review_id', 'ugtrevlog_rev_idx');
            $table->index('user_id', 'ugtrevlog_user_idx');

            $table->foreign('review_id', 'ugtrevlog_rev_fk')
                ->references('id')
                ->on('user_game_title_reviews')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'ugtrevlog_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_title_review_logs');
    }
};
