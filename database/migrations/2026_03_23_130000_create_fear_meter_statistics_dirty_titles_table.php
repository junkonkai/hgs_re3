<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('fear_meter_statistics_dirty_titles', function (Blueprint $table) {
            $table->unsignedInteger('game_title_id')->primary()->comment('ゲームタイトルID');
            $table->timestamps();

            $table->index('updated_at');
            $table->foreign('game_title_id')
                ->references('id')
                ->on('game_titles')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fear_meter_statistics_dirty_titles');
    }
};
