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
        Schema::create('user_game_title_fear_meter_comment_reports', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->unsignedBigInteger('fear_meter_log_id')->comment('怖さメーターログID');
            $table->unsignedBigInteger('reporter_user_id')->comment('通報者ユーザーID');
            $table->string('reason', 255)->nullable()->comment('通報理由');
            $table->string('status', 20)->default('open')->comment('通報ステータス');
            $table->unsignedBigInteger('reviewed_by_admin_id')->nullable()->comment('レビューした管理者ID');
            $table->timestamp('reviewed_at')->nullable()->comment('レビュー日時');
            $table->timestamps();

            $table->unique(['fear_meter_log_id', 'reporter_user_id'], 'ugtfmcr_log_reporter_unique');
            $table->index(['status', 'created_at'], 'ugtfmcr_status_created_at_idx');
            $table->index('fear_meter_log_id', 'ugtfmcr_log_idx');

            $table->foreign('fear_meter_log_id', 'ugtfmcr_log_fk')
                ->references('id')
                ->on('user_game_title_fear_meter_logs')
                ->cascadeOnDelete();
            $table->foreign('reporter_user_id', 'ugtfmcr_reporter_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('reviewed_by_admin_id', 'ugtfmcr_reviewed_admin_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_game_title_fear_meter_comment_reports');
    }
};
