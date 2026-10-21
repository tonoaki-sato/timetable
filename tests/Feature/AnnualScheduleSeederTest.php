<?php

namespace Tests\Feature;

use App\Models\AnnualSchedule;
use Database\Seeders\AnnualScheduleSeeder;
use Database\Seeders\StaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnualScheduleSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * AnnualScheduleSeeder が正常に実行され、2027年の実データおよび担当スタッフが正しく登録されること
     */
    public function test_annual_schedule_seeder_seeds_data_correctly(): void
    {
        $this->seed([StaffSeeder::class, AnnualScheduleSeeder::class]);

        // 2027年度のデータが登録されていること
        $schedules2027 = AnnualSchedule::where('target_year', 2027)->get();
        $this->assertGreaterThanOrEqual(30, $schedules2027->count());

        // 必須の主要行事・マイルストーンが存在すること
        $this->assertDatabaseHas('annual_schedules', [
            'target_year' => 2027,
            'title' => '第1回幹事会（年間日程案策定）',
            'category' => 'committee',
        ]);

        $this->assertDatabaseHas('annual_schedules', [
            'target_year' => 2027,
            'title' => '第1回総会（新年度発足・役員選出・前年度会計報告）',
            'category' => 'committee',
        ]);

        $this->assertDatabaseHas('annual_schedules', [
            'target_year' => 2027,
            'title' => '宿場まつり新聞 広告・協賛金募集開始',
            'category' => 'briefing',
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
