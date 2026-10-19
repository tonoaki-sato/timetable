<?php

namespace Tests\Feature;

use App\Models\AnnualSchedule;
use Database\Seeders\AnnualScheduleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnualScheduleSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AnnualScheduleSeeder が正常に実行され、2026年および2025年の実データが正しく登録されること
     */
    public function test_annual_schedule_seeder_seeds_data_correctly(): void
    {
        $this->seed(AnnualScheduleSeeder::class);

        // 2026年度（今年度）のデータが登録されていること
        $schedules2026 = AnnualSchedule::where('target_year', 2026)->get();
        $this->assertGreaterThanOrEqual(30, $schedules2026->count());

        // 2025年度（前年度実績）のデータが登録されていること
        $schedules2025 = AnnualSchedule::where('target_year', 2025)->get();
        $this->assertGreaterThanOrEqual(10, $schedules2025->count());

        // 必須の主要行事・マイルストーンが存在すること
        $this->assertDatabaseHas('annual_schedules', [
            'target_year' => 2026,
            'title' => '保土ケ谷宿場まつり本番運行 1日目',
            'category' => 'operation',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('annual_schedules', [
            'target_year' => 2026,
            'title' => '保土ケ谷宿場まつり本番運行 2日目＆当日打ち上げ',
            'category' => 'operation',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('annual_schedules', [
            'target_year' => 2026,
            'title' => '第1回総会（新年度発足・役員選出・前年度会計報告）',
            'category' => 'committee',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('annual_schedules', [
            'target_year' => 2026,
            'title' => '道路使用許可申請本提出・保健所届出協議',
            'category' => 'permit',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('annual_schedules', [
            'target_year' => 2026,
            'title' => '宿場まつり新聞 広告・協賛金募集開始',
            'category' => 'briefing',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('annual_schedules', [
            'target_year' => 2026,
            'title' => '総会／反省会・慰労会（打ち上げ）',
            'category' => 'committee',
            'status' => 'planned',
        ]);

        // 全レコードのカテゴリとステータスが定義定数に合致していること
        $validCategories = array_keys(AnnualSchedule::CATEGORIES);
        $validStatuses = array_keys(AnnualSchedule::STATUSES);

        foreach (AnnualSchedule::all() as $schedule) {
            $this->assertContains($schedule->category, $validCategories, "Invalid category: {$schedule->category}");
            $this->assertContains($schedule->status, $validStatuses, "Invalid status: {$schedule->status}");
            $this->assertNotNull($schedule->start_date);
            $this->assertNotNull($schedule->created_by);
        }
    }

    /**
     * シーダーが複数回実行されても重複登録されず羃等性が担保されること
     */
    public function test_annual_schedule_seeder_is_idempotent(): void
    {
        $this->seed(AnnualScheduleSeeder::class);
        $firstRunCount = AnnualSchedule::count();

        // 2回目の実行
        $this->seed(AnnualScheduleSeeder::class);
        $secondRunCount = AnnualSchedule::count();

        $this->assertSame($firstRunCount, $secondRunCount);
    }
}
