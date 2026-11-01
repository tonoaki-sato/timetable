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
            'category' => 'heavy_equipment',
            'checkout_rule' => 'free',
            'type' => $isCommittee ? 'committee' : 'rental',
            'quantity' => fake()->numberBetween(2, 50),
            'unit' => fake()->randomElement(['個', '張', '台', '脚', '組']),
            'storage_location' => $isCommittee ? fake()->randomElement(['番所地下倉庫', 'コミュニティルーム']) : null,
            'storage_detail' => null,
            'event_company_name' => $isCommittee ? null : '○○イベント企画株式会社',
            'event_company_contact' => $isCommittee ? null : fake()->phoneNumber(),
            'lane_id' => Lane::factory(),
            'description' => fake()->optional()->sentence(),
            'display_order' => fake()->numberBetween(1, 100),
            'created_by' => User::factory(),
        ];
    }

    /**
     * 台車・運搬具
     */
    public function carrier(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'carrier',
            'checkout_rule' => 'permission_required',
            'storage_location' => '番所地下倉庫',
            'storage_detail' => '地下倉庫入口右手・台車置き場',
        ]);
    }

    /**
     * 工具・消耗品
     */
    public function toolConsumable(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'tool_consumable',
            'checkout_rule' => 'free',
            'storage_location' => 'コミュニティルーム',
            'storage_detail' => '本陣受付裏 備品棚2段目',
        ]);
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
