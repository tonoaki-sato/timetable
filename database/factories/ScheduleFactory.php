<?php

namespace Database\Factories;

use App\Models\Schedule;
use App\Models\Staff;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('-1 month', '+6 months');

        return [
            'title' => fake()->randomElement([
                '第1回 実行委員会・基本方針策定',
                '警察・消防・行政合同 道路使用事前協議',
                'ボランティア・運行スタッフ募集開始',
                '参加団体・露店出店者説明会',
                '前日会場設営・機材搬入・動線確認',
                'まつり本番運行・神輿渡御',
                '機材撤収・清掃・反省会',
                '第1回 幹事会',
                '第2回 幹事会',
            ]),
            'category' => fake()->randomElement(array_keys(Schedule::CATEGORIES)),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => null,
            'start_time' => null,
            'end_time' => null,
            'location' => fake()->randomElement(['市民会館 大ホール', 'まつり運行本部', '市役所第2会議室', 'コミュニティルーム']),
            'status' => 'planned',
            'description' => fake()->realText(80),
            'staff_id' => null,
            'created_by' => User::factory(),
        ];
    }

    /**
     * 担当スタッフの指定
     */
    public function assignedTo(?Staff $staff): static
    {
        return $this->state(fn (array $attributes) => [
            'staff_id' => $staff?->id,
        ]);
    }

    /**
     * 主催スタッフの指定（assignedToのエイリアス）
     */
    public function hostedBy(?Staff $staff): static
    {
        return $this->assignedTo($staff);
    }

    /**
     * 会議・打ち合わせスタイル
     */
    public function asMeeting(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'committee',
            'start_time' => '18:00',
            'end_time' => '20:00',
        ]);
    }

    /**
     * 期間（複数日）スケジュール
     */
    public function period(int $days = 3): static
    {
        return $this->state(function (array $attributes) use ($days) {
            $start = isset($attributes['start_date']) ? Carbon::parse($attributes['start_date']) : now();

            return [
                'start_date' => $start->toDateString(),
                'end_date' => $start->copy()->addDays($days)->toDateString(),
            ];
        });
    }

    /**
     * ステータス: 予定
     */
    public function planned(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'planned',
        ]);
    }

    /**
     * ステータス: 進行中
     */
    public function inProgress(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'in_progress',
        ]);
    }

    /**
     * ステータス: 完了
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * ステータス: 中止
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'cancelled',
        ]);
    }
}
