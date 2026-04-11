<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_title_reviews', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->unsignedBigInteger('user_id')->comment('ユーザーID');
            $table->unsignedInteger('game_title_id')->comment('ゲームタイトルID');
            $table->boolean('is_hidden')->default(false)->comment('管理者による非表示フラグ');
            $table->unsignedBigInteger('hidden_by_admin_id')->nullable()->comment('非表示にした管理者ID');
            $table->timestamp('hidden_at')->nullable()->comment('非表示にした日時');
            $table->string('play_status', 32)->comment('プレイ状況');
            $table->string('play_time', 32)->nullable()->comment('プレイ時間');
            $table->text('body')->comment('本文（〜2000文字）');
            $table->boolean('has_spoiler')->default(false)->comment('ネタバレフラグ');
            $table->unsignedTinyInteger('score_story')->nullable()->comment('ストーリースコア（0〜4）');
            $table->unsignedTinyInteger('score_atmosphere')->nullable()->comment('雰囲気・演出スコア（0〜4）');
            $table->unsignedTinyInteger('score_gameplay')->nullable()->comment('ゲーム性スコア（0〜4）');
            $table->smallInteger('user_score_adjustment')->nullable()->comment('ユーザー調整値（−20〜+20）');
            $table->unsignedTinyInteger('base_score')->nullable()->comment('ベーススコア（0〜100）');
            $table->unsignedTinyInteger('total_score')->nullable()->comment('総合スコア（0〜100）');
            $table->unsignedBigInteger('current_log_id')->nullable()->comment('現在の公開バージョンのログID');
            $table->string('ogp_image_path')->nullable()->comment('OGP画像パス');
            $table->boolean('is_deleted')->default(false)->comment('ユーザーによるソフトデリートフラグ');
            $table->timestamps();

            $table->unique(['user_id', 'game_title_id'], 'ugtrev_user_title_unique');
            $table->index('user_id', 'ugtrev_user_idx');
            $table->index('game_title_id', 'ugtrev_title_idx');
            $table->index(['game_title_id', 'is_deleted', 'is_hidden'], 'ugtrev_title_visible_idx');

            $table->foreign('user_id', 'ugtrev_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('game_title_id', 'ugtrev_title_fk')
                ->references('id')
                ->on('game_titles')
                ->cascadeOnDelete();
            $table->foreign('hidden_by_admin_id', 'ugtrev_admin_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_title_reviews');
    }
};
