<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use Database\Seeders\StaffSeeder;
use Database\Seeders\TimetableSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * StaffSeeder および TimetableSeeder が正常に動作し、タイムテーブルと登録済みタスク・レーンが正しく登録されること
     */
    public function test_timetable_and_staff_seeders_seed_data_correctly(): void
    {
        $this->seed(StaffSeeder::class);

        $sampleStaff = [
            'ヨーコ・サトウ', '宮永 健一', '有江 浩二', '漆原 拓也', '佐藤 秀樹',
            '中村 亮', '高橋 美咲', '佐々木 直人', '小林 翔太', '渡辺 健司',
            '萩原 誠', '姉川 雅代', '北川 慎一郎', '吉田 浩介', '山口 雄大',
            '鈴木 恵', '井上 蓮', '伊藤 裕美', '加藤 雅之', '松本 さくら',
        ];
        foreach ($sampleStaff as $name) {
            Staff::factory()->create(['name' => $name]);
        }

        $this->seed(TimetableSeeder::class);

        // 1. スタッフが20名以上登録されていること
        $this->assertGreaterThanOrEqual(20, Staff::count());
        $this->assertDatabaseHas('staff', ['name' => 'ヨーコ・サトウ']);
        $this->assertDatabaseHas('staff', ['name' => '宮永 健一']);
        $this->assertDatabaseHas('staff', ['name' => '有江 浩二']);
        $this->assertDatabaseHas('staff', ['name' => '漆原 拓也']);
        $this->assertDatabaseHas('staff', ['name' => '佐藤 秀樹']);

        // 2. 運行日（Day 1: 宿場まつり 1日目, Day 2: 宿場まつり 2日目）が登録されていること
        $this->assertDatabaseHas('timetables', ['target_date' => '2026-10-11', 'name' => '宿場まつり 1日目']);
        $this->assertDatabaseHas('timetables', ['target_date' => '2026-10-12', 'name' => '宿場まつり 2日目']);

        // 3. 各日のタスク・レーンが存在し、リレーションが機能していること
        $timetableDay1 = Timetable::where('target_date', '2026-10-11')->firstOrFail();
        $this->assertSame('active', $timetableDay1->status);
        $this->assertSame(24, $timetableDay1->tasks()->count());

        // レーン表示順のカスタム設定（衛生=20, カラーコーン=40等）が反映されていること
        $this->assertSame(20, Lane::where('timetable_id', $timetableDay1->id)->where('name', '衛生')->value('display_order'));
        $this->assertSame(40, Lane::where('timetable_id', $timetableDay1->id)->where('name', 'カラーコーン')->value('display_order'));

        // 主担当スタッフの設定（衛生=27: 有エ 喜一郎, カラーコーン=35: Jouko Karvonen, 道路清掃=null 等）が反映されていること
        $this->assertSame(27, Lane::where('timetable_id', $timetableDay1->id)->where('name', '衛生')->value('lead_staff_id'));
        $this->assertSame(35, Lane::where('timetable_id', $timetableDay1->id)->where('name', 'カラーコーン')->value('lead_staff_id'));
        $this->assertSame(1, Lane::where('timetable_id', $timetableDay1->id)->where('name', '巡回')->value('lead_staff_id'));
        $this->assertNull(Lane::where('timetable_id', $timetableDay1->id)->where('name', '道路清掃')->value('lead_staff_id'));

        // 主担当が決まっているレーンのタスクには、その主担当がデフォルトでアサインされていること
        $eiseiLane = Lane::where('timetable_id', $timetableDay1->id)->where('name', '衛生')->firstOrFail();
        foreach ($eiseiLane->tasks as $eiseiTask) {
            $this->assertDatabaseHas('task_staff_assignments', [
                'timetable_task_id' => $eiseiTask->id,
                'staff_id' => 27,
                'role_description' => '班リーダー',
                'attendance_status' => 'assigned',
            ]);
        }

        // 最優先タスクの存在確認
        $criticalTask = TimetableTask::where('timetable_id', $timetableDay1->id)
            ->where('is_critical', true)
            ->first();
        $this->assertNotNull($criticalTask);

        // レーンおよびステータスが定義に合致していること
        $validLanes = collect(Lane::DEFAULT_LANES)->pluck('name')->toArray();
        $validStatuses = array_keys(TimetableTask::STATUSES);

        foreach (TimetableTask::all() as $task) {
            $this->assertContains($task->lane, $validLanes, "Invalid lane: {$task->lane}");
            $this->assertContains($task->status, $validStatuses, "Invalid status: {$task->status}");
            $this->assertNotNull($task->start_time);
            $this->assertNotNull($task->end_time);
        }
    }

    /**
     * シーダーが複数回実行されても重複登録されず羃等性が担保されること
     */
    public function test_timetable_seeder_is_idempotent(): void
    {
        $this->seed(StaffSeeder::class);
        $this->seed(TimetableSeeder::class);

        $initialTimetableCount = Timetable::count();
        $initialTaskCount = TimetableTask::count();
        $initialAssignmentCount = TaskStaffAssignment::count();

        // 2回目の実行
        $this->seed(StaffSeeder::class);
        $this->seed(TimetableSeeder::class);

        $this->assertSame($initialTimetableCount, Timetable::count());
        $this->assertSame($initialTaskCount, TimetableTask::count());
        $this->assertSame($initialAssignmentCount, TaskStaffAssignment::count());
    }
}
