<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_game_title_review_horror_type_tags', function (Blueprint $table) {
            $table->id()->comment('ID');
            $table->unsignedBigInteger('review_id')->comment('レビューID');
            $table->string('tag', 32)->comment('ホラー種別タグ（HorrorTypeTag enum）');

            $table->unique(['review_id', 'tag'], 'ugtrevhtt_rev_tag_unique');
            $table->index('review_id', 'ugtrevhtt_rev_idx');

            $table->foreign('review_id', 'ugtrevhtt_rev_fk')
                ->references('id')
                ->on('user_game_title_reviews')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_game_title_review_horror_type_tags');
    }
};
