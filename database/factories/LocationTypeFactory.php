<?php

namespace Database\Factories;

use App\Models\LocationType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LocationType>
 */
class LocationTypeFactory extends Factory
{
    protected $model = LocationType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->slug(2);

        return [
            'name' => $name,
            'label' => fake()->word().'スペース',
            'description' => fake()->sentence(),
            'color' => fake()->randomElement(['indigo', 'emerald', 'amber', 'purple', 'sky', 'rose']),
            'display_order' => fake()->numberBetween(1, 50),
            'is_active' => true,
        ];
    }
}
