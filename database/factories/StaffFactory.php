<?php

namespace Database\Factories;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'kana' => 'ヤマダ タロウ',
            'affiliation' => fake()->randomElement(['中央商店街振興会', '市民ボランティア連合', '青葉大学ボランティア部', '北町青年団', '一般公募']),
            'is_active' => true,
            'access_token' => Str::random(32),
            'registered_via' => 'admin',
        ];
    }

    /**
     * 停止中状態
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * セルフ登録状態
     */
    public function selfRegistered(): static
    {
        return $this->state(fn (array $attributes) => [
            'registered_via' => 'self',
        ]);
    }

    /**
     * LINE連携状態
     */
    public function withLine(?string $displayName = null, ?string $userId = null): static
    {
        return $this->state(fn (array $attributes) => [
            'line_user_id' => $userId ?? 'U'.Str::random(32),
            'line_display_name' => $displayName ?? fake()->userName(),
            'line_picture_url' => 'https://profile.line-scdn.net/sample',
        ]);
    }
}
