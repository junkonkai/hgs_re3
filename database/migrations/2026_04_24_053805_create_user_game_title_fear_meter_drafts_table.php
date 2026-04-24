<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_title_fear_meter_drafts', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->unsignedBigInteger('user_id')->comment('ユーザーID');
            $table->unsignedInteger('game_title_id')->comment('ゲームタイトルID');
            $table->unsignedTinyInteger('fear_meter')->comment('怖さ評価値（0-4）');
            $table->string('comment', 100)->nullable()->comment('一言コメント');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('game_title_id')->references('id')->on('game_titles')->onDelete('cascade');
            $table->unique(['user_id', 'game_title_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_title_fear_meter_drafts');
    }
};
