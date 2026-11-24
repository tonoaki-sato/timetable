<?php

namespace Tests\Feature;

use App\Enums\TimetableTaskStatus;
use App\Enums\TimetableTaskType;
use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use Database\Seeders\LaneSeeder;
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

        $this->seed(LaneSeeder::class);
        $this->seed(TimetableSeeder::class);

        // 0. 担当班マスター・日別編成・現場要請の件数が正しくシードされていること
        $this->assertSame(17, Lane::count());
        $this->assertSame(50, TimetableLane::count());
        $this->assertSame(3, LaneRequest::count());
        $this->assertSame(0, TimetableTask::whereNull('timetable_lane_id')->count());
        $this->assertGreaterThan(0, TimetableTask::where('task_type', TimetableTaskType::Routine->value)->count());
        $this->assertGreaterThan(0, TimetableTask::where('task_type', TimetableTaskType::Spot->value)->count());

        // 1. スタッフが20名以上登録されていること
        $this->assertGreaterThanOrEqual(20, Staff::count());
        $this->assertDatabaseHas('staff', ['name' => 'ヨーコ・サトウ']);
        $this->assertDatabaseHas('staff', ['name' => '宮永 健一']);
        $this->assertDatabaseHas('staff', ['name' => '有江 浩二']);
        $this->assertDatabaseHas('staff', ['name' => '漆原 拓也']);
        $this->assertDatabaseHas('staff', ['name' => '佐藤 秀樹']);
        $this->assertDatabaseHas('staff', ['id' => 38, 'name' => '山口 日出夏', 'affiliation' => 'S&S']);

        // 2. 運行日（Day 1: 宿場まつり 1日目, Day 2: 宿場まつり 2日目, Day 3: 幹事会反省会（デモ））が登録されていること
        $this->assertDatabaseHas('timetables', ['target_date' => '2026-10-11', 'name' => '宿場まつり 1日目']);
        $this->assertDatabaseHas('timetables', ['target_date' => '2026-10-12', 'name' => '宿場まつり 2日目']);
        $this->assertDatabaseHas('timetables', ['target_date' => '2026-11-07', 'name' => '幹事会反省会（デモ）']);

        // 3. 各日のタスク・レーンが存在し、リレーションが機能していること
        $timetableDay1 = Timetable::where('target_date', '2026-10-11')->firstOrFail();
        $this->assertSame('completed', $timetableDay1->status);
        $this->assertSame(24, $timetableDay1->tasks()->count());

        $eiseiMaster = Lane::where('name', '衛生')->firstOrFail();
        $coneMaster = Lane::where('name', 'カラーコーン')->firstOrFail();
        $patrolMaster = Lane::where('name', '巡回')->firstOrFail();
        $sweepMaster = Lane::where('name', '道路清掃')->firstOrFail();

        // レーン表示順のカスタム設定（衛生=20, カラーコーン=40等）が反映されていること
        $this->assertSame(20, TimetableLane::where('timetable_id', $timetableDay1->id)->where('lane_id', $eiseiMaster->id)->value('display_order'));
        $this->assertSame(40, TimetableLane::where('timetable_id', $timetableDay1->id)->where('lane_id', $coneMaster->id)->value('display_order'));

        // 主担当スタッフの設定（衛生=27: 有エ 喜一郎, カラーコーン=35: Jouko Karvonen, 道路清掃=null 等）が反映されていること
        $this->assertSame(27, TimetableLane::where('timetable_id', $timetableDay1->id)->where('lane_id', $eiseiMaster->id)->value('lead_staff_id'));
        $this->assertSame(35, TimetableLane::where('timetable_id', $timetableDay1->id)->where('lane_id', $coneMaster->id)->value('lead_staff_id'));
        $this->assertSame(1, TimetableLane::where('timetable_id', $timetableDay1->id)->where('lane_id', $patrolMaster->id)->value('lead_staff_id'));
        $this->assertNull(TimetableLane::where('timetable_id', $timetableDay1->id)->where('lane_id', $sweepMaster->id)->value('lead_staff_id'));

        // 主担当が決まっているレーンのタスクには、その主担当がデフォルトでアサインされていること
        $eiseiLane = TimetableLane::where('timetable_id', $timetableDay1->id)->where('lane_id', $eiseiMaster->id)->firstOrFail();
        foreach ($eiseiLane->tasks as $eiseiTask) {
            $this->assertDatabaseHas('task_staff_assignments', [
                'timetable_task_id' => $eiseiTask->id,
                'staff_id' => 27,
                'role_description' => '班長',
                'attendance_status' => 'assigned',
            ]);
        }

        // 最優先タスクの存在確認
        $criticalTask = TimetableTask::where('timetable_id', $timetableDay1->id)
            ->where('is_critical', true)
            ->first();
        $this->assertNotNull($criticalTask);

        $this->assertDatabaseHas('lanes', [
            'name' => '税務署ステージ',
            'description' => '税務署ステージのイベント運行管理',
        ]);
        $this->assertDatabaseMissing('lanes', ['name' => 'イベント']);

        // 第35回（Day 4）追加レーン（S&S 等）と最新タスクがシードされていること
        $timetableDay4 = Timetable::where('target_date', '2027-10-10')
            ->where('name', '第35回保土ケ谷宿場まつり（1日目）')
            ->firstOrFail();
        $this->assertNull($timetableDay4->description);

        $itMaster = Lane::where('name', 'IT')->firstOrFail();
        $this->assertDatabaseMissing('timetable_lanes', [
            'timetable_id' => $timetableDay4->id,
            'lane_id' => $itMaster->id,
        ]);

        $shukubaMaster = Lane::where('name', '宿場くん/宿場ちゃん')->firstOrFail();
        $shukubaLane = TimetableLane::where('timetable_id', $timetableDay4->id)
            ->where('lane_id', $shukubaMaster->id)
            ->firstOrFail();
        $this->assertNotContains(7, $shukubaLane->fixedStaff()->pluck('staff.id')->all());

        $snsMaster = Lane::where('name', 'S&S')->firstOrFail();
        $kigyoMaster = Lane::where('name', '企業ブース')->firstOrFail();

        $snsLane = TimetableLane::where('timetable_id', $timetableDay4->id)->where('lane_id', $snsMaster->id)->firstOrFail();
        $this->assertSame(38, $snsLane->lead_staff_id);
        $this->assertSame(170, $snsLane->display_order);
        $this->assertDatabaseHas('timetable_tasks', [
            'timetable_id' => $timetableDay4->id,
            'timetable_lane_id' => $snsLane->id,
            'title' => '管理・イベント運営',
            'task_type' => TimetableTaskType::Routine->value,
        ]);

        $kigyoLane = TimetableLane::where('timetable_id', $timetableDay4->id)->where('lane_id', $kigyoMaster->id)->firstOrFail();
        $this->assertSame(24, $kigyoLane->lead_staff_id);
        $this->assertSame(180, $kigyoLane->display_order);
        $this->assertDatabaseHas('timetable_tasks', [
            'timetable_id' => $timetableDay4->id,
            'timetable_lane_id' => $kigyoLane->id,
            'title' => '搬入対応',
            'task_type' => TimetableTaskType::Spot->value,
        ]);

        // 第35回（Day 5）がシードされていること
        $timetableDay5 = Timetable::where('target_date', '2027-10-11')
            ->where('name', '第35回保土ケ谷宿場まつり（2日目）')
            ->firstOrFail();
        $this->assertSame(54, $timetableDay5->tasks()->count());

        // レーン間支援要請（LaneRequest）が最新状態でシードされていること
        $this->assertSame(3, LaneRequest::where('status', 'resolved')->count());
        $this->assertSame(
            2,
            LaneRequest::whereNotNull('resolved_by_user_id')->whereNotNull('resolved_at')->count()
        );

        // レーンおよびステータスが定義に合致していること
        $validLanes = Lane::pluck('name')->toArray();
        $validStatuses = TimetableTaskStatus::values();

        foreach (TimetableTask::with('timetableLane.lane')->get() as $task) {
            $laneNameLabel = $task->presenter()->laneNameLabel();
            $this->assertContains($laneNameLabel, $validLanes, "Invalid lane: {$laneNameLabel}");
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
        $this->seed(LaneSeeder::class);
        $this->seed(TimetableSeeder::class);

        // 0. 担当班マスター・日別編成・現場要請の件数が正しくシードされていること
        $this->assertSame(17, Lane::count());
        $this->assertSame(50, TimetableLane::count());
        $this->assertSame(3, LaneRequest::count());
        $this->assertSame(0, TimetableTask::whereNull('timetable_lane_id')->count());
        $this->assertGreaterThan(0, TimetableTask::where('task_type', TimetableTaskType::Routine->value)->count());
        $this->assertGreaterThan(0, TimetableTask::where('task_type', TimetableTaskType::Spot->value)->count());

        $initialTimetableCount = Timetable::count();
        $initialTaskCount = TimetableTask::count();
        $initialAssignmentCount = TaskStaffAssignment::count();

        // 2回目の実行
        $this->seed(StaffSeeder::class);
        $this->seed(LaneSeeder::class);
        $this->seed(TimetableSeeder::class);

        // 0. 担当班マスター・日別編成・現場要請の件数が正しくシードされていること
        $this->assertSame(17, Lane::count());
        $this->assertSame(50, TimetableLane::count());
        $this->assertSame(3, LaneRequest::count());
        $this->assertSame(0, TimetableTask::whereNull('timetable_lane_id')->count());
        $this->assertGreaterThan(0, TimetableTask::where('task_type', TimetableTaskType::Routine->value)->count());
        $this->assertGreaterThan(0, TimetableTask::where('task_type', TimetableTaskType::Spot->value)->count());

        $this->assertSame($initialTimetableCount, Timetable::count());
        $this->assertSame($initialTaskCount, TimetableTask::count());
        $this->assertSame($initialAssignmentCount, TaskStaffAssignment::count());
    }
}
