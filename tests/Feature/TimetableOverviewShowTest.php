<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableOverviewShowTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    /**
     * 運行計画詳細画面でKPIストリップ、重点タスク、物流配備、現場要請が当該計画スコープで正しく描画されること
     */
    public function test_timetable_show_renders_overview_kpis_and_cockpit_within_timetable_scope(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 10, 30, 0, 'Asia/Tokyo'));

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目 本祭',
            'target_date' => '2026-10-11',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'active',
            'road_periods' => [
                ['start_time' => '10:00', 'end_time' => '18:00', 'status' => 'closed', 'label' => '車両通行止め'],
            ],
            'festival_periods' => [
                ['start_time' => '11:00', 'end_time' => '16:00', 'status' => 'active', 'label' => '本祭進行中'],
            ],
        ]);

        $leader = Staff::factory()->create(['name' => '本部長 太郎']);
        $member = Staff::factory()->create(['name' => '一般スタッフ 花子']);

        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン班',
            'lead_staff_id' => $leader->id,
        ]);

        // クリティカル・最優先タスク
        $criticalTask = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '通行止め直後の最優先コーン設置',
            'start_time' => '10:00:00',
            'end_time' => '10:30:00',
            'required_people_count' => 2,
            'is_critical' => true,
            'status' => 'in_progress',
        ]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $criticalTask->id,
            'staff_id' => $leader->id,
            'attendance_status' => 'assigned',
        ]);

        // 資材搬出入計画
        $equipment = Equipment::factory()->create(['name' => 'カラーコーンセット']);
        EquipmentLogisticsPlan::factory()->create([
            'timetable_id' => $timetable->id,
            'equipment_id' => $equipment->id,
            'action_type' => 'deploy',
            'status' => 'completed',
            'witness_required' => true,
        ]);

        // 現場要請（当該計画のレーンに紐づく）
        LaneRequest::create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'requested_by_staff_id' => $leader->id,
            'request_type' => 'staffing',
            'requested_count' => 2,
            'reason' => '東口コーン設置の補助要請',
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->operator)->get(route('timetables.show', $timetable));

        $response->assertStatus(200);

        // 1. 4大KPIストリップの表示確認
        $response->assertSee('タスク進捗');
        $response->assertSee('人員充足');
        $response->assertSee('資材物流配備');
        $response->assertSee('規制 ＆ 現場要請');

        // 2. 重点管理タスク Spotlightの表示確認
        $response->assertSee('重点管理タスク（最優先・通行止め連動）');
        $response->assertSee('通行止め直後の最優先コーン設置');

        // 3. 当計画の現場要請表示確認
        $response->assertSee('当計画の現場要請（人員・機材申請）');
        $response->assertSee('東口コーン設置の補助要請');
        $response->assertSee('未対応 1件');

        // 4. 通行止め規制中の表示
        $response->assertSee('車両通行止め中');

        // 5. 厳格CSP検証: プログレスバーに inline style が出力されておらず、data-percent が使われていること
        $response->assertSee('data-percent=', false);
        $response->assertDontSee('style="width:', false);
    }
}
