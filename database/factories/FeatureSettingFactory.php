<?php

namespace Database\Factories;

use App\Models\FeatureSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FeatureSetting>
 */
class FeatureSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'feature_key' => fake()->unique()->slug(),
            'name' => fake()->words(2, true),
            'description' => fake()->sentence(),
            'is_enabled' => true,
            'opened_at' => null,
            'closed_at' => null,
            'notice_message' => null,
        ];
    }

    /**
     * スタッフセルフ登録用のステート
     */
    public function staffSelfRegistration(): static
    {
        return $this->state(fn (array $attributes) => [
            'feature_key' => FeatureSetting::FEATURE_STAFF_SELF_REGISTRATION,
            'name' => 'スタッフセルフ登録',
            'description' => 'ボランティア・現場スタッフによるWebセルフ登録受付機能',
            'is_enabled' => true,
        ]);
    }

    /**
     * 手動停止中ステート
     */
    public function disabled(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_enabled' => false,
        ]);
    }
}
