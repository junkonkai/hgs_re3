<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('user_game_title_review_draft_horror_type_tags');
        Schema::dropIfExists('user_game_title_review_horror_type_tags');

        Schema::table('user_game_title_review_logs', function (Blueprint $table) {
            $table->dropColumn('horror_type_tags');
        });
    }

    public function down(): void
    {
        Schema::create('user_game_title_review_horror_type_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('review_id');
            $table->string('tag', 64);
            $table->timestamps();
            $table->foreign('review_id')->references('id')->on('user_game_title_reviews')->cascadeOnDelete();
        });

        Schema::create('user_game_title_review_draft_horror_type_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('draft_id');
            $table->string('tag', 64);
            $table->timestamps();
            $table->foreign('draft_id')->references('id')->on('user_game_title_review_drafts')->cascadeOnDelete();
        });

        Schema::table('user_game_title_review_logs', function (Blueprint $table) {
            $table->json('horror_type_tags')->nullable()->after('total_score')->comment('ホラー種別タグの配列（スナップショット）');
        });
    }
};
