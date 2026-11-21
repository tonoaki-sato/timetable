<?php

namespace Database\Factories;

use App\Models\Lane;
use App\Models\Timetable;
use App\Models\TimetableLane;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimetableLane>
 */
class TimetableLaneFactory extends Factory
{
    protected $model = TimetableLane::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'timetable_id' => Timetable::factory(),
            'lane_id' => Lane::factory(),
            'lead_staff_id' => null,
            'display_order' => fake()->numberBetween(10, 100),
            'notes' => null,
        ];
    }
}
