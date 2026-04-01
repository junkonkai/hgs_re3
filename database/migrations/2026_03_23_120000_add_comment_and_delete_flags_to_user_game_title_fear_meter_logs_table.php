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
        Schema::table('user_game_title_fear_meter_logs', function (Blueprint $table) {
            $table->string('comment', 100)->nullable()->after('new_fear_meter')->comment('一言コメント');
            $table->boolean('is_deleted')->default(false)->after('comment')->comment('削除フラグ');
            $table->timestamp('deleted_at')->nullable()->after('is_deleted')->comment('削除日時');
            $table->unsignedBigInteger('deleted_by_user_id')->nullable()->after('deleted_at')->comment('削除したユーザーID');
            $table->unsignedBigInteger('deleted_by_admin_id')->nullable()->after('deleted_by_user_id')->comment('削除した管理者ID');

            $table->index(['game_title_id', 'created_at'], 'ugtfml_game_title_created_at_idx');
            $table->index(['game_title_id', 'is_deleted', 'created_at'], 'ugtfml_title_deleted_created_at_idx');
            $table->index(['user_id', 'game_title_id', 'id'], 'ugtfml_user_title_id_idx');

            $table->foreign('deleted_by_user_id', 'ugtfml_deleted_by_user_fk')
                ->references('id')->on('users')->nullOnDelete();
            $table->foreign('deleted_by_admin_id', 'ugtfml_deleted_by_admin_fk')
                ->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_game_title_fear_meter_logs', function (Blueprint $table) {
            $table->dropForeign('ugtfml_deleted_by_user_fk');
            $table->dropForeign('ugtfml_deleted_by_admin_fk');

            $table->dropIndex('ugtfml_game_title_created_at_idx');
            $table->dropIndex('ugtfml_title_deleted_created_at_idx');
            $table->dropIndex('ugtfml_user_title_id_idx');

            $table->dropColumn([
                'comment',
                'is_deleted',
                'deleted_at',
                'deleted_by_user_id',
                'deleted_by_admin_id',
            ]);
        });
    }
};
