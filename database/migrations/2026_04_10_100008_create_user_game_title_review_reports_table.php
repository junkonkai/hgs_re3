<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_title_review_reports', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->unsignedBigInteger('user_id')->comment('通報者ユーザーID');
            $table->unsignedBigInteger('review_id')->comment('レビューID');
            $table->unsignedBigInteger('review_log_id')->comment('通報時点のバージョンID');
            $table->text('reason')->nullable()->comment('通報理由');
            $table->boolean('is_resolved')->default(false)->comment('対応済みフラグ');
            $table->unsignedBigInteger('resolved_by_admin_id')->nullable()->comment('対応した管理者ID');
            $table->timestamp('resolved_at')->nullable()->comment('対応日時');
            $table->timestamps();

            $table->unique(['user_id', 'review_id'], 'ugtrevrpt_user_rev_unique');
            $table->index('review_id', 'ugtrevrpt_rev_idx');
            $table->index(['is_resolved', 'created_at'], 'ugtrevrpt_resolved_created_idx');

            $table->foreign('user_id', 'ugtrevrpt_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('review_id', 'ugtrevrpt_rev_fk')
                ->references('id')
                ->on('user_game_title_reviews')
                ->cascadeOnDelete();
            $table->foreign('review_log_id', 'ugtrevrpt_log_fk')
                ->references('id')
                ->on('user_game_title_review_logs')
                ->cascadeOnDelete();
            $table->foreign('resolved_by_admin_id', 'ugtrevrpt_admin_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_title_review_reports');
    }
};
