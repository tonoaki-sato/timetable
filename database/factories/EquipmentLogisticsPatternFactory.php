<?php

namespace Database\Factories;

use App\Models\EquipmentLogisticsPattern;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EquipmentLogisticsPattern>
 */
class EquipmentLogisticsPatternFactory extends Factory
{
    protected $model = EquipmentLogisticsPattern::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'カラーコーン（交通規制連動・9ステップ）',
                '一般自前資材（2日間設置サイクル）',
                '私物資材（個人持ち込み〜最終日持ち帰り）',
                'レンタル機材・委員会設置（受取・回収立会要）',
                'イベント会社一任（設営・解体）',
                '放送設備（事前配線・現地夜間保管）',
            ]),
            'target_type' => 'any',
            'description' => fake()->optional()->sentence(),
            'display_order' => fake()->numberBetween(1, 50),
            'created_by' => User::factory(),
        ];
    }
}

