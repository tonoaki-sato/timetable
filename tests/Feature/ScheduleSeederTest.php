<?php

namespace Tests\Feature;

use App\Models\Schedule;
use Database\Seeders\ScheduleSeeder;
use Database\Seeders\StaffSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScheduleSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ScheduleSeeder が正常に実行され、実データが正しく登録されること
     */
    public function test_schedule_seeder_seeds_data_correctly(): void
    {
        $this->seed([StaffSeeder::class, ScheduleSeeder::class]);

        $schedules = Schedule::all();
        $this->assertGreaterThanOrEqual(40, $schedules->count());

        // 主要行事・マイルストーンが存在すること
        $this->assertDatabaseHas('schedules', [
            'title' => '前年度会計年度末締め調整',
            'category' => 'other',
        ]);

        $this->assertDatabaseHas('schedules', [
            'title' => '宿場まつり新聞 広告・協賛金募集開始',
            'category' => 'briefing',
        ]);

        // 会議データも統合されて存在すること
        $this->assertDatabaseHas('schedules', [
            'title' => '第1回幹事会（年間日程案策定）',
            'category' => 'committee',
        ]);

        // 全レコードのカテゴリとステータスが定義定数に合致していること
        $validCategories = array_keys(Schedule::CATEGORIES);
        $validStatuses = array_keys(Schedule::STATUSES);

        foreach ($schedules as $schedule) {
            $this->assertContains($schedule->category, $validCategories, "Invalid category: {$schedule->category}");
            $this->assertContains($schedule->status, $validStatuses, "Invalid status: {$schedule->status}");
            $this->assertNotNull($schedule->start_date);
            $this->assertNotNull($schedule->created_by);
        }
    }

    /**
     * シーダーが複数回実行されても重複登録されず羃等性が担保されること
     */
    public function test_schedule_seeder_is_idempotent(): void
    {
        $this->seed([StaffSeeder::class, ScheduleSeeder::class]);
        $firstCount = Schedule::count();

        // 2回目の実行
        $this->seed(ScheduleSeeder::class);
        $secondCount = Schedule::count();

        $this->assertSame($firstCount, $secondCount);
    }
}

