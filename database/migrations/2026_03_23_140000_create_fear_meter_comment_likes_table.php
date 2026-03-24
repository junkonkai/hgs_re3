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
        Schema::create('user_game_title_fear_meter_comment_likes', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->unsignedBigInteger('fear_meter_log_id')->comment('怖さメーターログID');
            $table->unsignedBigInteger('user_id')->comment('いいねしたユーザーID');
            $table->timestamp('created_at')->comment('作成日時');

            $table->unique(['fear_meter_log_id', 'user_id'], 'ugtfmcl_log_user_unique');
            $table->index('fear_meter_log_id', 'ugtfmcl_log_idx');
            $table->index('user_id', 'ugtfmcl_user_idx');

            $table->foreign('fear_meter_log_id', 'ugtfmcl_log_fk')
                ->references('id')
                ->on('user_game_title_fear_meter_logs')
                ->cascadeOnDelete();
            $table->foreign('user_id', 'ugtfmcl_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_game_title_fear_meter_comment_likes');
    }
};
