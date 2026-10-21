<?php

namespace Database\Factories;

use App\Models\Meeting;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meeting>
 */
class MeetingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('now', '+3 months');

        return [
            'title' => fake()->randomElement([
                '第1回 幹事会',
                '第2回 幹事会',
                'ござ市部門 打ち合わせ',
                '給水設備・衛生班 合同会議',
                '警備誘導・警察署事前協議',
                'キッズ村企画会議',
                '本番直前 全体リーダー会議',
            ]),
            'meeting_date' => $date->format('Y-m-d'),
            'start_time' => '18:00',
            'end_time' => '20:00',
            'location' => fake()->randomElement(['コミュニティルーム', '市民会館 2F 会議室', 'まつり運行本部', 'オンライン (Zoom)']),
            'host_staff_id' => Staff::factory(),
            'description' => fake()->realText(100),
            'notice_note' => '出欠のご連絡は前日17時までにお願いいたします。',
            'created_by' => User::factory(),
        ];
    }

    /**
     * 過去の会議ステート
     */
    public function past(): static
    {
        return $this->state(fn (array $attributes) => [
            'meeting_date' => fake()->dateTimeBetween('-3 months', '-1 days')->format('Y-m-d'),
        ]);
    }

    /**
     * 開催予定の会議ステート
     */
    public function upcoming(): static
    {
        return $this->state(fn (array $attributes) => [
            'meeting_date' => fake()->dateTimeBetween('+1 days', '+3 months')->format('Y-m-d'),
        ]);
    }
}
