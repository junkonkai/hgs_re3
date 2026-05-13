<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_link_sold_out_results', function (Blueprint $table) {
            $table->id();
            $table->string('source_table', 50);
            $table->unsignedInteger('source_id');
            $table->unsignedInteger('shop_id');
            $table->text('url');
            $table->string('reason', 10);
            $table->string('matched_keyword', 255)->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();

            $table->unique(['source_table', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_link_sold_out_results');
    }
};
