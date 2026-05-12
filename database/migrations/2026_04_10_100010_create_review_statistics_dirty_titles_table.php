<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_statistics_dirty_titles', function (Blueprint $table) {
            $table->unsignedInteger('game_title_id')->primary()->comment('ゲームタイトルID');
            $table->timestamps();

            $table->index('updated_at');

            $table->foreign('game_title_id', 'revdirt_title_fk')
                ->references('id')
                ->on('game_titles')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_statistics_dirty_titles');
    }
};
