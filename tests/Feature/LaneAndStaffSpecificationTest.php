<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Models\User;
use App\Services\StaffAllocationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaneAndStaffSpecificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_lane_leader_and_fixed_staff_are_automatically_assigned_to_all_lane_tasks(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $timetable = Timetable::factory()->create(['created_by' => $admin->id]);

        $leader = Staff::factory()->create(['name' => '班長 太郎']);
        $fixedStaff1 = Staff::factory()->create(['name' => '固定 一郎']);
        $fixedStaff2 = Staff::factory()->create(['name' => '固定 二郎']);
        $fulfillmentStaff = Staff::factory()->create(['name' => '充足 三郎']);

        $masterLane = Lane::factory()->create([
            'name' => 'カラーコーン',
            'color' => 'amber',
            'icon' => '🔺',
        ]);
        $lane = TimetableLane::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $masterLane->id,
            'lead_staff_id' => null,
        ]);

        $task1 = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'timetable_lane_id' => $lane->id,
            'title' => 'コーン搬入',
            'start_time' => '08:00:00',
            'end_time' => '09:00:00',
            'required_people_count' => 4,
        ]);

        // 1. 班長と固定スタッフをレーンに設定すると既存タスクに自動アサインされる
        $response = $this->actingAs($admin)->put(route('timetables.lanes.update', [$timetable, $lane]), [
            'lead_staff_id' => $leader->id,
            'fixed_staff_ids' => [$fixedStaff1->id, $fixedStaff2->id],
        ]);

        $response->assertRedirect(route('timetables.lanes.index', $timetable));

        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task1->id,
            'staff_id' => $leader->id,
            'role_description' => '班長',
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task1->id,
            'staff_id' => $fixedStaff1->id,
            'role_description' => '固定スタッフ',
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task1->id,
            'staff_id' => $fixedStaff2->id,
            'role_description' => '固定スタッフ',
        ]);
        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $task1->id,
            'staff_id' => $fulfillmentStaff->id,
        ]);

        // 2. 新規タスク作成時にも班長と固定スタッフが自動的に基本アサインされる
        $this->actingAs($admin)->post(route('timetable-tasks.store'), [
            'timetable_id' => $timetable->id,
            'timetable_lane_id' => $lane->id,
            'auto_assign_lead' => 1,
            'title' => 'コーン撤去',
            'start_time' => '17:00',
            'end_time' => '18:00',
            'required_people_count' => 3,
            'status' => 'not_started',
        ]);

        $task2 = TimetableTask::where('title', 'コーン撤去')->firstOrFail();

        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task2->id,
            'staff_id' => $leader->id,
            'role_description' => '班長',
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task2->id,
            'staff_id' => $fixedStaff1->id,
            'role_description' => '固定スタッフ',
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task2->id,
            'staff_id' => $fixedStaff2->id,
            'role_description' => '固定スタッフ',
        ]);
    }

    public function test_surplus_fixed_staff_can_be_reassigned_to_other_lane_task_without_conflict(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $timetable = Timetable::factory()->create(['created_by' => $admin->id]);

        $leaderA = Staff::factory()->create(['name' => 'A班長']);
        $fixedA1 = Staff::factory()->create(['name' => 'A固定1']);
        $fixedA2 = Staff::factory()->create(['name' => 'A固定2']);

        $masterA = Lane::factory()->create(['name' => '衛生']);
        $laneA = TimetableLane::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $masterA->id,
            'lead_staff_id' => $leaderA->id,
        ]);
        $laneA->fixedStaff()->sync([$fixedA1->id, $fixedA2->id]);

        $masterB = Lane::factory()->create(['name' => '巡回']);
        $laneB = TimetableLane::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $masterB->id,
        ]);

        // A班のタスクは必要人数2名に対し、班長+固定2名の計3名が基本アサインされており1名余剰（定員超過）状態
        $taskA = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'timetable_lane_id' => $laneA->id,
            'title' => '衛生点検',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'required_people_count' => 2,
            'is_at_least' => false,
        ]);
        $taskA->assignments()->create(['staff_id' => $leaderA->id, 'role_description' => '班長', 'attendance_status' => 'assigned']);
        $taskA->assignments()->create(['staff_id' => $fixedA1->id, 'role_description' => '固定スタッフ', 'attendance_status' => 'assigned']);
        $taskA->assignments()->create(['staff_id' => $fixedA2->id, 'role_description' => '固定スタッフ', 'attendance_status' => 'assigned']);

        // 同時間帯のB班タスク（人員不足）
        $taskB = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'timetable_lane_id' => $laneB->id,
            'title' => '会場パトロール',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'required_people_count' => 2,
        ]);

        // 余剰がある固定スタッフ $fixedA2 を他班タスク $taskB に割り当てると、コンフリクト警告にならず自動融通される
        $response = $this->actingAs($admin)->postJson(route('timetable-tasks.assign', $taskB), [
            'staff_id' => $fixedA2->id,
        ]);

        $response->assertOk()->assertJson(['success' => true]);

        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $taskB->id,
            'staff_id' => $fixedA2->id,
            'role_description' => '他班応援',
        ]);
        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $taskA->id,
            'staff_id' => $fixedA2->id,
        ]);
    }

    public function test_lane_leader_can_manage_lane_tasks_and_submit_staffing_and_equipment_requests(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $timetable = Timetable::factory()->create(['created_by' => $admin->id]);
        $leader = Staff::factory()->create(['name' => '班長 佐藤', 'is_active' => true]);

        $master = Lane::factory()->create(['name' => '給水設備']);
        $lane = TimetableLane::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $master->id,
            'lead_staff_id' => $leader->id,
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'timetable_lane_id' => $lane->id,
            'title' => '給水タンク設置',
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
            'required_people_count' => 3,
            'status' => 'not_started',
        ]);

        // 1. 班長が担当班タスク全体のステータスを更新
        $statusResponse = $this->postJson(route('staff.myshift.lane-task-status', [
            'token' => $leader->access_token,
            'task' => $task->id,
        ]), [
            'status' => 'in_progress',
        ]);

        $statusResponse->assertOk()->assertJson([
            'success' => true,
            'status' => 'in_progress',
        ]);
        $this->assertDatabaseHas('timetable_tasks', [
            'id' => $task->id,
            'status' => 'in_progress',
        ]);

        // 2. 班長が人員不足のため管理者へ応援要請を送信
        $staffingResponse = $this->postJson(route('staff.myshift.lane-requests.store', [
            'token' => $leader->access_token,
            'timetableLane' => $lane->id,
        ]), [
            'request_type' => 'staffing',
            'timetable_task_id' => $task->id,
            'requested_count' => 2,
            'reason' => '重機搬入のため2名増員をお願いします',
        ]);

        $staffingResponse->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('lane_requests', [
            'timetable_lane_id' => $lane->id,
            'timetable_task_id' => $task->id,
            'requested_by_staff_id' => $leader->id,
            'request_type' => 'staffing',
            'requested_count' => 2,
            'status' => 'pending',
        ]);

        // 3. 班長が必要機材を申請
        $equipmentResponse = $this->postJson(route('staff.myshift.lane-requests.store', [
            'token' => $leader->access_token,
            'timetableLane' => $lane->id,
        ]), [
            'request_type' => 'equipment',
            'requested_count' => 4,
            'equipment_name' => '大型台車',
            'reason' => '本陣裏で受け取り希望',
        ]);

        $equipmentResponse->assertOk()->assertJson(['success' => true]);
        $equipmentRequest = LaneRequest::where('request_type', 'equipment')->firstOrFail();
        $this->assertSame('大型台車', $equipmentRequest->equipment_name);

        // 4. 管理者が要請・申請を承認
        $approveResponse = $this->actingAs($admin)->patchJson(route('lane-requests.update-status', $equipmentRequest), [
            'status' => 'approved',
        ]);

        $approveResponse->assertOk()->assertJson([
            'success' => true,
            'status' => 'approved',
        ]);
    }

    public function test_fixed_and_lead_staff_priority_is_scoped_to_task_timetable(): void
    {
        $day1 = Timetable::factory()->create(['name' => 'Day1']);
        $day2 = Timetable::factory()->create(['name' => 'Day2']);
        $masterLane = Lane::factory()->create(['name' => '交通警備班']);

        $day1Lead = Staff::factory()->create(['name' => 'Day1班長', 'is_active' => true]);
        $day1Fixed = Staff::factory()->create(['name' => 'Day1固定', 'is_active' => true]);
        $day2Lead = Staff::factory()->create(['name' => 'Day2班長', 'is_active' => true]);

        $tlDay1 = TimetableLane::factory()->create([
            'timetable_id' => $day1->id,
            'lane_id' => $masterLane->id,
            'lead_staff_id' => $day1Lead->id,
        ]);
        $tlDay1->fixedStaff()->attach([$day1Lead->id, $day1Fixed->id]);

        $tlDay2 = TimetableLane::factory()->create([
            'timetable_id' => $day2->id,
            'lane_id' => $masterLane->id,
            'lead_staff_id' => $day2Lead->id,
        ]);
        $tlDay2->fixedStaff()->attach([$day2Lead->id]);

        $day2Task = TimetableTask::factory()->create([
            'timetable_id' => $day2->id,
            'timetable_lane_id' => $tlDay2->id,
            'title' => 'Day2交通誘導',
        ]);

        $allocationService = app(StaffAllocationService::class);
        $candidates = $allocationService
            ->getPrioritizedStaffForTask($day2Task, Staff::whereIn('id', [$day1Lead->id, $day1Fixed->id, $day2Lead->id])->get())
            ->keyBy('id');

        $this->assertTrue((bool) $candidates->get($day2Lead->id)->is_lane_lead);
        $this->assertTrue((bool) $candidates->get($day2Lead->id)->is_same_lane);

        // 別運行日(Day1)での同一マスター担当班における班長・固定所属がDay2側へ漏れないこと
        $this->assertFalse((bool) $candidates->get($day1Lead->id)->is_lane_lead);
        $this->assertFalse((bool) $candidates->get($day1Lead->id)->is_same_lane);
        $this->assertFalse((bool) $candidates->get($day1Fixed->id)->is_lane_lead);
        $this->assertFalse((bool) $candidates->get($day1Fixed->id)->is_same_lane);
    }

    public function test_manual_page_displays_lane_leader_and_fixed_fulfillment_staff_specification(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $response = $this->actingAs($admin)->get(route('manual.index'));

        $response->assertOk()
            ->assertSee('班（レーン）・班長・班員（固定スタッフ／充足スタッフ）運用仕様')
            ->assertSee('固定スタッフ（Fixed Staff）')
            ->assertSee('充足スタッフ（Fulfillment Staff）');
    }
}
