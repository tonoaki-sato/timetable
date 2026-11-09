<?php

namespace Database\Factories;

use App\Models\Equipment;
use App\Models\Lane;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Equipment>
 */
class EquipmentFactory extends Factory
{
    protected $model = Equipment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $isCommittee = fake()->boolean(60);

        return [
            'name' => fake()->randomElement(['カラーコーン', '足踏み式給水設備', 'パイプテント 2K×3K', 'ござ市用テント', '折りたたみ大型台車', '放送設備一式']),
            'type' => $isCommittee ? 'committee' : 'rental',
            'checkout_rule' => 'free',
            'quantity' => fake()->numberBetween(2, 60),
            'is_approximate_quantity' => false,
            'unit' => fake()->randomElement(['個', '本', '張', '台', '組', '式']),
            'storage_location' => $isCommittee ? fake()->randomElement(['番所地下倉庫', '番所倉庫', 'コミュニティルーム']) : null,
            'lane_id' => Lane::factory(),
            'description' => fake()->optional()->sentence(),
            'display_order' => fake()->numberBetween(1, 100),
            'created_by' => User::factory(),
        ];
    }

    /**
     * 委員会管理機材
     */
    public function committee(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'committee',
            'storage_location' => '番所地下倉庫',
        ]);
    }

    /**
     * レンタル機材
     */
    public function rental(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'rental',
            'storage_location' => null,
        ]);
    }

    /**
     * 私物（個人持ち込み）資材
     */
    public function personal(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'personal',
            'storage_location' => '所有者（個人持ち込み・持ち帰り）',
            'checkout_rule' => 'leader_only',
        ]);
    }

    /**
     * 概数管理資材
     */
    public function approximate(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_approximate_quantity' => true,
        ]);
    }
}
