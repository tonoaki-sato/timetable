<?php

namespace Database\Factories;

use App\Models\Lane;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lane>
 */
class LaneFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'timetable_id' => Timetable::factory(),
            'name' => fake()->unique()->word().'班',
            'color' => fake()->randomElement(array_keys(Lane::COLOR_OPTIONS)),
            'icon' => fake()->randomElement(['🔺', '🚰', '🚻', '🚨', '🎈', '🏮', '🧺', '🏛️', '🧹', '🪧']),
            'lead_staff_id' => null, // デフォルトは後付け可能な未設定
            'is_core' => false,
            'is_active' => true,
            'description' => fake()->sentence(),
            'display_order' => fake()->numberBetween(10, 100),
        ];
    }
}
