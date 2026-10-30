<?php

namespace Database\Factories;

use App\Models\LogisticsLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LogisticsLocation>
 */
class LogisticsLocationFactory extends Factory
{
    protected $model = LogisticsLocation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['税務署前駐車場', '北川駐車場', '番所地下倉庫', '番所空きスペース', 'コミュニティルーム', '役場東側広場']),
            'type' => fake()->randomElement(['parking', 'facility', 'open_space', 'other']),
            'is_all_day' => false,
            'available_from' => '2026-10-10 12:00:00',
            'available_to' => '2026-10-12 21:00:00',
            'description' => fake()->optional()->sentence(),
            'display_order' => fake()->numberBetween(1, 100),
            'is_active' => true,
            'created_by' => User::factory(),
        ];
    }

    /**
     * 終日利用可能
     */
    public function allDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_all_day' => true,
            'available_from' => null,
            'available_to' => null,
        ]);
    }
}
