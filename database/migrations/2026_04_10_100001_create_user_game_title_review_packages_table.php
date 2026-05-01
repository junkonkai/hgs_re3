<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_title_review_packages', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->unsignedBigInteger('review_id')->comment('レビューID');
            $table->unsignedInteger('game_package_id')->comment('ゲームパッケージID');

            $table->unique(['review_id', 'game_package_id'], 'ugtrevpkg_rev_pkg_unique');
            $table->index('review_id', 'ugtrevpkg_rev_idx');

            $table->foreign('review_id', 'ugtrevpkg_rev_fk')
                ->references('id')
                ->on('user_game_title_reviews')
                ->cascadeOnDelete();
            $table->foreign('game_package_id', 'ugtrevpkg_pkg_fk')
                ->references('id')
                ->on('game_packages')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_title_review_packages');
    }
};
