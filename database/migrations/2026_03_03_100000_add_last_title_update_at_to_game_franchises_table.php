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
        Schema::table('game_franchises', function (Blueprint $table) {
            $table->timestamp('last_title_update_at')->nullable()->comment('紐づくGameTitleの最終更新日時');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('game_franchises', function (Blueprint $table) {
            $table->dropColumn('last_title_update_at');
        });
    }
};
