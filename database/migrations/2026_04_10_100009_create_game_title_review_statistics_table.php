<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('game_title_review_statistics', function (Blueprint $table) {
            $table->unsignedInteger('game_title_id')->primary()->comment('ゲームタイトルID');
            $table->unsignedInteger('review_count')->default(0)->comment('公開済みレビュー件数');
            $table->decimal('avg_total_score', 5, 2)->nullable()->comment('総合スコア平均（0〜100）');
            $table->decimal('avg_story', 4, 2)->nullable()->comment('ストーリー平均（0〜4）');
            $table->decimal('avg_atmosphere', 4, 2)->nullable()->comment('雰囲気・演出平均（0〜4）');
            $table->decimal('avg_gameplay', 4, 2)->nullable()->comment('ゲーム性平均（0〜4）');
            $table->timestamp('updated_at')->nullable()->comment('最終更新日時');

            $table->foreign('game_title_id', 'gtrstat_title_fk')
                ->references('id')
                ->on('game_titles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('game_title_review_statistics');
    }
};
