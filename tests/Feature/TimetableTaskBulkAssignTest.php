<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use App\Services\ConflictDetectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableTaskBulkAssignTest extends TestCase
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
            'name' => 'まつり運行テスト日',
        ]);

        $this->lane = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '巡回警備班',
        ]);

        $this->task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => 'エリア警戒巡回',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'required_people_count' => 3,
        ]);
    }

    /**
     * 複数スタッフの一括アサインが正常に完了すること
     */
    public function test_can_bulk_assign_staff_successfully(): void
    {
        $this->actingAs($this->operator);

        $staff1 = Staff::factory()->create(['name' => 'スタッフA', 'is_active' => true]);
        $staff2 = Staff::factory()->create(['name' => 'スタッフB', 'is_active' => true]);
        $staff3 = Staff::factory()->create(['name' => 'スタッフC', 'is_active' => true]);

        $response = $this->post(route('timetable-tasks.assign-bulk', $this->task), [
            'staff_ids' => [$staff1->id, $staff2->id, $staff3->id],
            'role_description' => '巡回担当',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staff1->id,
            'role_description' => '巡回担当',
            'attendance_status' => 'assigned',
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staff2->id,
            'role_description' => '巡回担当',
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staff3->id,
            'role_description' => '巡回担当',
        ]);

        $this->assertEquals(3, $this->task->fresh()->assigned_count);
        $this->assertTrue($this->task->fresh()->is_satisfied);
    }

    /**
     * 重複スタッフが含まれる場合、forceなしでは警告セッションでリダイレクトされ未アサインであること
     */
    public function test_bulk_assign_detects_conflict_and_stops_without_force(): void
    {
        $this->actingAs($this->operator);

        $staffAvailable = Staff::factory()->create(['name' => '空きスタッフ', 'is_active' => true]);
        $staffBusy = Staff::factory()->create(['name' => '別タスク中スタッフ', 'is_active' => true]);

        // 10:30〜11:30の別タスクにアサイン済み（重複）
        $otherTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '本部待機',
            'start_time' => '10:30:00',
            'end_time' => '11:30:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $otherTask->id,
            'staff_id' => $staffBusy->id,
            'attendance_status' => 'assigned',
        ]);

        $response = $this->post(route('timetable-tasks.assign-bulk', $this->task), [
            'staff_ids' => [$staffAvailable->id, $staffBusy->id],
            'role_description' => '巡回担当',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('bulk_conflict_warning');

        // 未アサインであること
        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staffAvailable->id,
        ]);
        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staffBusy->id,
        ]);
    }

    /**
     * force=1 を指定した場合は重複を承認して一括アサインが完了すること
     */
    public function test_can_force_bulk_assign_staff_with_conflict(): void
    {
        $this->actingAs($this->operator);

        $staffBusy = Staff::factory()->create(['name' => '重複スタッフ', 'is_active' => true]);

        $otherTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $otherTask->id,
            'staff_id' => $staffBusy->id,
            'attendance_status' => 'assigned',
        ]);

        $response = $this->post(route('timetable-tasks.assign-bulk', $this->task), [
            'staff_ids' => [$staffBusy->id],
            'role_description' => '兼務担当',
            'force' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staffBusy->id,
            'role_description' => '兼務担当',
        ]);
    }

    /**
     * JSON リクエスト時のレスポンス検証（通常及び競合時）
     */
    public function test_bulk_assign_json_responses(): void
    {
        $this->actingAs($this->operator);

        $staff1 = Staff::factory()->create(['name' => 'JSONスタッフ1', 'is_active' => true]);
        $staff2 = Staff::factory()->create(['name' => 'JSONスタッフ2', 'is_active' => true]);

        // 競合タスク作成
        $otherTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $otherTask->id,
            'staff_id' => $staff2->id,
            'attendance_status' => 'assigned',
        ]);

        // 競合発生時 (409)
        $conflictResponse = $this->postJson(route('timetable-tasks.assign-bulk', $this->task), [
            'staff_ids' => [$staff1->id, $staff2->id],
        ]);
        $conflictResponse->assertStatus(409);
        $conflictResponse->assertJsonPath('has_conflict', true);
        $this->assertCount(1, $conflictResponse->json('conflicts'));

        // 競合強制時 (200)
        $forceResponse = $this->postJson(route('timetable-tasks.assign-bulk', $this->task), [
            'staff_ids' => [$staff1->id, $staff2->id],
            'force' => 1,
        ]);
        $forceResponse->assertOk();
        $forceResponse->assertJsonPath('success', true);
        $forceResponse->assertJsonPath('assigned_count', 2);
    }

    /**
     * ConflictDetectionService::getPrioritizedStaffForTask による優先度ソート検証
     */
    public function test_prioritizes_staff_correctly(): void
    {
        /** @var ConflictDetectionService $service */
        $service = app(ConflictDetectionService::class);

        // スタッフ1: 同班所属・空き（最優先 Tier 1）
        $staff1 = Staff::factory()->create([
            'name' => '巡回ベテラン',
            'kana' => 'アアア',
            'affiliation' => '巡回警備班',
            'is_active' => true,
        ]);

        // スタッフ2: 別班所属・空き（通常空き Tier 2）
        $staff2 = Staff::factory()->create([
            'name' => '本部メンバー',
            'kana' => 'イイイ',
            'affiliation' => '本部連絡班',
            'is_active' => true,
        ]);

        // スタッフ3: 重複あり（最下位 Tier 3）
        $staff3 = Staff::factory()->create([
            'name' => '重複メンバー',
            'kana' => 'ウウウ',
            'affiliation' => '巡回警備班',
            'is_active' => true,
        ]);
        $otherTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $otherTask->id,
            'staff_id' => $staff3->id,
            'attendance_status' => 'assigned',
        ]);

        $allStaff = collect([$staff3, $staff2, $staff1]);
        $sorted = $service->getPrioritizedStaffForTask($this->task, $allStaff);

        $this->assertEquals($staff1->id, $sorted[0]->id, '同班・空きスタッフが1位であること');
        $this->assertEquals(1, $sorted[0]->priority_tier);

        $this->assertEquals($staff2->id, $sorted[1]->id, '別班・空きスタッフが2位であること');
        $this->assertEquals(2, $sorted[1]->priority_tier);

        $this->assertEquals($staff3->id, $sorted[2]->id, '重複スタッフが最下位であること');
        $this->assertEquals(3, $sorted[2]->priority_tier);
    }
}
