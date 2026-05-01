<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_game_title_fear_meter_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('action')->default(1)->after('deleted_by_admin_id')->comment('操作種別（1=新規登録, 2=編集, 3=削除）');
        });
    }

    public function down(): void
    {
        Schema::table('user_game_title_fear_meter_logs', function (Blueprint $table) {
            $table->dropColumn('action');
        });
    }
};
