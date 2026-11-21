<?php

namespace Database\Factories;

use App\Models\LayoutArea;
use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LayoutArea>
 */
class LayoutAreaFactory extends Factory
{
    protected $model = LayoutArea::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'location_id' => null,
            'name' => 'エリア '.fake()->unique()->numerify('###'),
            'notes' => null,
            'display_order' => 10,
            'is_active' => true,
        ];
    }

    /**
     * 無効化状態
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_active' => false,
        ]);
    }

    /**
     * 特定拠点に紐づく状態
     */
    public function forLocation(Location $location): static
    {
        return $this->state(fn (array $attributes): array => [
            'location_id' => $location->id,
        ]);
    }
}
