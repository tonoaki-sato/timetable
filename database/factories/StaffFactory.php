<?php

namespace Database\Factories;

use App\Enums\StaffAttribute;
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
            'staff_attribute' => StaffAttribute::General->value,
            'is_active' => true,
            'access_token' => Str::random(32),
            'registered_via' => 'admin',
        ];
    }

    /**
     * 幹事状態
     */
    public function executive(): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_attribute' => StaffAttribute::Executive->value,
        ]);
    }

    /**
     * 幹事会状態
     */
    public function executiveBoard(): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_attribute' => StaffAttribute::ExecutiveBoard->value,
        ]);
    }

    /**
     * 外部状態
     */
    public function external(): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_attribute' => StaffAttribute::External->value,
        ]);
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
     * 再発行済み状態
     */
    public function reissued(int $count = 1): static
    {
        return $this->state(fn (array $attributes) => [
            'token_reissued_at' => now(),
            'reissue_count' => $count,
        ]);
    }
}
