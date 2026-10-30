<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\LocationSchedule;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationSchedule>
 */
class LocationScheduleFactory extends Factory
{
    protected $model = LocationSchedule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = Carbon::parse('2026-10-10 09:00:00');
        $end = $start->copy()->addHours(3);

        return [
            'location_id' => Location::factory(),
            'start_at' => $start,
            'end_at' => $end,
            'application_status' => 'unapplied',
            'title' => '午前利用枠',
            'note' => fake()->sentence(),
        ];
    }
}
