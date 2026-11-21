<?php

namespace Database\Factories;

use App\Models\SiteBaseMap;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SiteBaseMap>
 */
class SiteBaseMapFactory extends Factory
{
    protected $model = SiteBaseMap::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => '商店街平面図 '.fake()->unique()->numerify('######'),
            'cad_version' => '260813',
            'paper_size' => 'A3',
            'scale_ratio' => 7.0866,
            'width_meters' => 118.80,
            'height_meters' => 168.00,
            'svg_path' => 'maps/shopping_street_base_map_260813.svg',
            'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
            'viewbox' => '0 0 842 1191',
            'is_default' => false,
            'is_active' => true,
            'notes' => null,
        ];
    }

    /**
     * デフォルト白地図状態
     */
    public function asDefault(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * 無効化状態
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_default' => false,
            'is_active' => false,
        ]);
    }
}
