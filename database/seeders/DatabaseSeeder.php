<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\ReviewSeeder;
use Database\Seeders\FearMeterSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::firstOrCreate(
            ['email' => 'test@example.com'],
            User::factory()->make(['name' => 'Test User', 'email' => 'test@example.com'])->toArray(),
        );

        $this->call([
            UserSeeder::class,
            ReviewSeeder::class,
            FearMeterSeeder::class,
        ]);
    }
}
