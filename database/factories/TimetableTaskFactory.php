<?php

namespace Database\Factories;

use App\Models\Lane;
use App\Models\Timetable;
use App\Models\TimetableTask;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimetableTask>
 */
class TimetableTaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'timetable_id' => Timetable::factory(),
            'lane_id' => fn (array $attributes) => Lane::factory()->create([
                'timetable_id' => $attributes['timetable_id'],
            ])->id,
            'title' => fake()->randomElement([
                '通行止め出入り口・支道接続点 最優先コーン設置',
                '一般・センシティブ箇所本設置（ガスト前・金子クリーニング前）',
                'ござ市飲食店エリアへの給水設備本設置',
                'ゴミ箱チェック（圧縮・袋交換）',
                '給水設備 定期点検 (第1回)',
                '交差点交通誘導・歩行者安全確保',
            ]),
            'content' => '現場責任者の指示に従い、配置完了後に本部へ無線連絡すること。',
            'location' => fake()->randomElement(['出入り口・支道接続点', 'ガスト前', '中央交差点', '全ゴミステーション']),
            'start_time' => '10:00:00',
            'end_time' => '10:45:00',
            'required_people_count' => fake()->numberBetween(2, 4),
            'is_at_least' => false,
            'description' => fake()->realText(40),
            'is_critical' => false,
            'requires_review' => false,
            'status' => 'not_started',
        ];
    }

    /**
     * 「〇〇名以上」フラグを有効化
     */
    public function atLeast(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_at_least' => true,
        ]);
    }

    /**
     * 最優先タスク
     */
    public function critical(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_critical' => true,
        ]);
    }

    /**
     * 進行中
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * 完了
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }
}
