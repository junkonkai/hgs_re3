<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory(30)->create();

        $this->command->info('UserSeeder 完了: ' . User::count() . ' 件のユーザーが存在します。');
    }
}
