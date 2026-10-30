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
            'name' => fake()->randomElement(['カラーコーン', '足踏み式給水設備', 'パイプテント 2K×3K', 'ござ市用テント', '折りたたみ長テーブル', 'パイプ椅子']),
            'type' => $isCommittee ? 'committee' : 'rental',
            'quantity' => fake()->numberBetween(2, 50),
            'unit' => fake()->randomElement(['個', '張', '台', '脚', '組']),
            'storage_location' => $isCommittee ? fake()->randomElement(['番所地下倉庫', 'コミュニティルーム']) : null,
            'event_company_name' => $isCommittee ? null : '○○イベント企画株式会社',
            'event_company_contact' => $isCommittee ? null : fake()->phoneNumber(),
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
            'event_company_name' => null,
            'event_company_contact' => null,
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
            'event_company_name' => '○○イベント企画株式会社',
            'event_company_contact' => '03-1234-5678 (担当: 田中)',
        ]);
    }
}
