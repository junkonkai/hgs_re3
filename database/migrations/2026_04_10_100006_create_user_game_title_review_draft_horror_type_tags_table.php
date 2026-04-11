<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_title_review_draft_horror_type_tags', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->unsignedBigInteger('draft_id')->comment('下書きID');
            $table->string('tag', 32)->comment('ホラー種別タグ（HorrorTypeTag enum）');

            $table->unique(['draft_id', 'tag'], 'ugtrevdfthtt_dft_tag_unique');
            $table->index('draft_id', 'ugtrevdfthtt_dft_idx');

            $table->foreign('draft_id', 'ugtrevdfthtt_dft_fk')
                ->references('id')
                ->on('user_game_title_review_drafts')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_title_review_draft_horror_type_tags');
    }
};
