<?php

namespace Database\Factories;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'kana' => 'ヤマダ タロウ',
            'affiliation' => fake()->randomElement(['中央商店街振興会', '市民ボランティア連合', '青葉大学ボランティア部', '北町青年団', '一般公募']),
            'is_active' => true,
        ];
    }

    /**
     * 停止中状態
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
