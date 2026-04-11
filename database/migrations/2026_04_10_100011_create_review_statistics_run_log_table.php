<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_statistics_run_log', function (Blueprint $table) {
            $table->id();
            $table->timestamp('last_completed_at')->nullable()->comment('前回の集計完了日時');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_statistics_run_log');
    }
};
