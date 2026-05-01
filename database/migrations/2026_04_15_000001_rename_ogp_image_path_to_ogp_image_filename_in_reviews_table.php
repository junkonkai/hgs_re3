<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('user_game_title_reviews', function (Blueprint $table) {
            $table->renameColumn('ogp_image_path', 'ogp_image_filename');
        });
    }

    public function down(): void
    {
        Schema::table('user_game_title_reviews', function (Blueprint $table) {
            $table->renameColumn('ogp_image_filename', 'ogp_image_path');
        });
    }
};
