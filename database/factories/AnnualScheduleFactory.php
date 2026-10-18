<?php

namespace Database\Factories;

use App\Models\AnnualSchedule;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnualSchedule>
 */
class AnnualScheduleFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = fake()->dateTimeBetween('2026-01-01', '2026-11-30');

        return [
            'target_year' => 2026,
            'title' => fake()->randomElement([
                '第1回 実行委員会・基本方針策定',
                '警察・消防・行政合同 道路使用事前協議',
                'ボランティア・運行スタッフ募集開始',
                '参加団体・露店出店者説明会',
                '前日会場設営・機材搬入・動線確認',
                'まつり本番運行・神輿渡御',
                '機材撤収・清掃・反省会',
            ]),
            'category' => fake()->randomElement(array_keys(AnnualSchedule::CATEGORIES)),
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => null,
            'location' => fake()->randomElement(['市民会館 大ホール', 'まつり運行本部', '市役所第2会議室', 'メイン通り特設会場']),
            'status' => 'planned',
            'description' => fake()->realText(80),
            'created_by' => User::factory(),
        ];
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

    /**
     * 対象年度の指定
     */
    public function year(int $year): static
    {
        return $this->state(fn (array $attributes) => [
            'target_year' => $year,
        ]);
    }

    /**
     * カテゴリの指定
     */
    public function category(string $category): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => $category,
        ]);
    }
}
