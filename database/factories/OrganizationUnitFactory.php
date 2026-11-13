<?php

namespace Database\Factories;

use App\Models\OrganizationUnit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrganizationUnit>
 */
class OrganizationUnitFactory extends Factory
{
    protected $model = OrganizationUnit::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'parent_id' => null,
            'name' => fake()->randomElement(['総務', '渉外', 'イベント', '広報', '本陣', '設営・設備', '規制・警備']),
            'short_description' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'display_order' => fake()->numberBetween(1, 100),
        ];
    }
}

