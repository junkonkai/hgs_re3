<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_title_review_draft_packages', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->unsignedBigInteger('draft_id')->comment('下書きID');
            $table->unsignedInteger('game_package_id')->comment('ゲームパッケージID');

            $table->unique(['draft_id', 'game_package_id'], 'ugtrevdftpkg_dft_pkg_unique');
            $table->index('draft_id', 'ugtrevdftpkg_dft_idx');

            $table->foreign('draft_id', 'ugtrevdftpkg_dft_fk')
                ->references('id')
                ->on('user_game_title_review_drafts')
                ->cascadeOnDelete();
            $table->foreign('game_package_id', 'ugtrevdftpkg_pkg_fk')
                ->references('id')
                ->on('game_packages')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_title_review_draft_packages');
    }
};
