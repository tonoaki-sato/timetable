<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetablePhase2AssignmentTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private Timetable $timetable;

    private Lane $lane;

    private TimetableTask $task;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'target_date' => now()->format('Y-m-d'),
            'name' => 'まつり本番 1日目',
        ]);

        $this->lane = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '給水設備班',
        ]);

        $this->task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '給水タンク設置',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'required_people_count' => 2,
        ]);
    }

    /**
     * クイックアサインモーダル用のデータ取得APIが正しいJSONを返却すること
     */
    public function test_can_get_assign_modal_data(): void
    {
        $this->actingAs($this->operator);

        $staffAssigned = Staff::factory()->create(['name' => '既配置スタッフ', 'is_active' => true]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staffAssigned->id,
            'role_description' => '給水チーフ',
            'attendance_status' => 'assigned',
        ]);

        $staffCandidate = Staff::factory()->create(['name' => '候補スタッフ', 'is_active' => true]);

        $response = $this->getJson(route('timetable-tasks.assign-modal-data', $this->task));

        $response->assertOk();
        $response->assertJsonPath('task.id', $this->task->id);
        $response->assertJsonPath('task.title', '給水タンク設置');
        $response->assertJsonPath('task.assigned_count', 1);

        // assignments 配列検証
        $this->assertCount(1, $response->json('assignments'));
        $this->assertEquals('既配置スタッフ', $response->json('assignments.0.staff_name'));

        // candidate_staff 配列検証
        $candidates = collect($response->json('candidate_staff'));
        $candidateItem = $candidates->firstWhere('id', $staffCandidate->id);
        $this->assertNotNull($candidateItem);
        $this->assertFalse($candidateItem['is_already_assigned']);
    }

    /**
     * 班（レーン）専属メンバーの自動一括割り当てが正しく動作すること
     */
    public function test_can_assign_lane_pool_members_automatically(): void
    {
        $this->actingAs($this->operator);

        // 給水設備班のスタッフ2名を作成
        $staff1 = Staff::factory()->create([
            'name' => '給水班員A',
            'affiliation' => '給水設備班',
            'is_active' => true,
        ]);
        $staff2 = Staff::factory()->create([
            'name' => '給水班員B',
            'affiliation' => '給水設備班',
            'is_active' => true,
        ]);
        // 別班のスタッフ
        $staffOther = Staff::factory()->create([
            'name' => '巡回員C',
            'affiliation' => '巡回警備班',
            'is_active' => true,
        ]);

        $response = $this->post(route('timetable-tasks.assign-lane-pool', $this->task));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 不足2名分が自動配置されていること
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staff1->id,
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staff2->id,
        ]);
        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staffOther->id,
        ]);

        $this->assertEquals(2, $this->task->fresh()->assigned_count);
        $this->assertTrue($this->task->fresh()->is_satisfied);
    }

    /**
     * 他日程からのアサイン一括コピーが正常に動作すること（競合重複は安全にスキップ）
     */
    public function test_can_copy_assignments_from_another_timetable(): void
    {
        $this->actingAs($this->operator);

        // コピー元の運行日（宵宮）とタスク作成
        $sourceTimetable = Timetable::factory()->create([
            'target_date' => now()->subDay()->format('Y-m-d'),
            'name' => 'まつり前日（宵宮）',
        ]);
        $sourceLane = Lane::factory()->create([
            'timetable_id' => $sourceTimetable->id,
            'name' => '給水設備班',
        ]);
        $sourceTask = TimetableTask::factory()->create([
            'timetable_id' => $sourceTimetable->id,
            'lane_id' => $sourceLane->id,
            'title' => '給水タンク設置',
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'required_people_count' => 2,
        ]);

        $staff1 = Staff::factory()->create(['name' => 'スタッフ1', 'is_active' => true]);
        $staff2 = Staff::factory()->create(['name' => 'スタッフ2', 'is_active' => true]);

        // コピー元に2名アサイン
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $sourceTask->id,
            'staff_id' => $staff1->id,
            'role_description' => '運搬係',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $sourceTask->id,
            'staff_id' => $staff2->id,
            'role_description' => '点検係',
        ]);

        // スタッフ2は、コピー先（当日）の同一時間帯に別タスクに入っている（競合設定）
        $conflictTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'start_time' => '08:30:00',
            'end_time' => '09:30:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $conflictTask->id,
            'staff_id' => $staff2->id,
        ]);

        // コピー実行
        $response = $this->post(route('timetables.copy-assignments-from', $this->timetable), [
            'source_timetable_id' => $sourceTimetable->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // スタッフ1はコピーされていること
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staff1->id,
            'role_description' => '運搬係',
        ]);

        // スタッフ2は重複のためスキップされていること
        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staff2->id,
        ]);
    }

    /**
     * スタッフの割り当て解除がJSONリクエスト時にも正常に動作すること
     */
    public function test_can_remove_staff_via_json(): void
    {
        $this->actingAs($this->operator);

        $staff = Staff::factory()->create(['is_active' => true]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staff->id,
        ]);

        $response = $this->deleteJson(route('timetable-tasks.remove-staff', [$this->task, $staff]));

        $response->assertOk();
        $response->assertJsonPath('success', true);

        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staff->id,
        ]);
    }
}
