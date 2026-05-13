<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_link_check_progresses', function (Blueprint $table) {
            $table->id();
            $table->string('source_table', 50);
            $table->unsignedBigInteger('last_checked_id')->default(0);
            $table->timestamps();

            $table->unique('source_table');
        });

        DB::table('shop_link_check_progresses')->insert([
            ['source_table' => 'game_package_shops',          'last_checked_id' => 0, 'created_at' => now(), 'updated_at' => now()],
            ['source_table' => 'game_related_product_shops',  'last_checked_id' => 0, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_link_check_progresses');
    }
};
