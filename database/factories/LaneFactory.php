<?php

namespace Database\Factories;

use App\Models\Lane;
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
            'name' => fake()->unique()->word() . '班',
            'color' => fake()->randomElement(array_keys(Lane::COLOR_OPTIONS)),
            'icon' => fake()->randomElement(['🔺', '🚰', '🚻', '🚨', '🎈', '🏮', '🧺', '🏛️', '🧹', '🪧']),
            'description' => fake()->sentence(),
            'display_order' => fake()->numberBetween(10, 100),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn(array $attributes): array => [
            'is_active' => false,
        ]);
    }
}
