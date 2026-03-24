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
        Schema::create('user_fear_meter_restrictions', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->unsignedBigInteger('user_id')->comment('対象ユーザーID');
            $table->string('reason', 255)->nullable()->comment('理由');
            $table->string('source', 30)->default('manual')->comment('制限ソース');
            $table->boolean('is_active')->default(true)->comment('有効フラグ');
            $table->timestamp('started_at')->comment('開始日時');
            $table->timestamp('ended_at')->nullable()->comment('終了日時');
            $table->unsignedBigInteger('created_by_admin_id')->nullable()->comment('作成管理者ID');
            $table->unsignedBigInteger('released_by_admin_id')->nullable()->comment('解除管理者ID');
            $table->timestamps();

            $table->index(['user_id', 'is_active', 'started_at'], 'ufmr_user_active_started_idx');

            $table->foreign('user_id', 'ufmr_user_fk')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
            $table->foreign('created_by_admin_id', 'ufmr_created_admin_fk')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
            $table->foreign('released_by_admin_id', 'ufmr_released_admin_fk')
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
        Schema::dropIfExists('user_fear_meter_restrictions');
    }
};
