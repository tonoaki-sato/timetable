<?php

namespace Database\Factories;

use App\Models\Timetable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Timetable>
 */
class TimetableFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'target_date' => fake()->unique()->date(),
            'name' => fake()->randomElement([
                '前日設営・交通予告看板配置',
                'まつり本番運行 1日目 (パレード・神輿)',
                'まつり本番運行 2日目 (例大祭式典)',
                '撤収・道路原状回復',
            ]),
            'description' => fake()->realText(50),
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'status' => 'active',
            'created_by' => User::factory(),
        ];
    }

    /**
     * 下書き状態
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    /**
     * 運行完了状態
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
