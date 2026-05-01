<?php

namespace Database\Factories;

use App\Models\UserGameTitleFearMeter;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserGameTitleFearMeter>
 */
class UserGameTitleFearMeterFactory extends Factory
{
    protected $model = UserGameTitleFearMeter::class;

    public function definition(): array
    {
        return [
            'fear_meter' => fake()->numberBetween(0, 4),
        ];
    }
}
