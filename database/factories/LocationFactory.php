<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\LocationType;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'timetable_id' => Timetable::factory(),
            'location_type_id' => LocationType::factory(),
            'name' => fake()->city().'待機所',
            'notes' => fake()->paragraph(),
            'is_all_day' => false,
            'display_order' => fake()->numberBetween(1, 50),
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
        ]);
    }
}
