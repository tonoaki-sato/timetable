<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableTaskManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private Timetable $timetable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'target_date' => '2026-10-17',
            'name' => '1日目 本祭運行',
        ]);
    }

    /**
     * 未認証ユーザーはタスク操作不可
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $task = TimetableTask::factory()->create(['timetable_id' => $this->timetable->id]);

        $this->get(route('timetable-tasks.create', ['timetable_id' => $this->timetable->id]))
            ->assertRedirect(route('login'));
        $this->get(route('timetable-tasks.show', $task))
            ->assertRedirect(route('login'));
    }

    /**
     * 作業タスクを新規登録できること（〇〇名以上フラグ・作業内容 content 対応）
     */
    public function test_can_create_task_with_at_least_flag_and_content(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('timetable-tasks.store'), [
            'timetable_id' => $this->timetable->id,
            'title' => '西口ゲート カラーコーン設置',
            'content' => '西口ゲートから交差点までコーンバーを2m間隔で配置すること。',
            'lane' => 'カラーコーン班',
            'location' => '西口交差点',
            'start_time' => '07:30',
            'end_time' => '09:00',
            'required_people_count' => 3,
            'is_at_least' => '1',
            'is_critical' => '1',
            'requires_review' => '0',
            'status' => 'not_started',
        ]);

        $response->assertRedirect(route('timetables.show', $this->timetable));

        $this->assertDatabaseHas('timetable_tasks', [
            'timetable_id' => $this->timetable->id,
            'title' => '西口ゲート カラーコーン設置',
            'content' => '西口ゲートから交差点までコーンバーを2m間隔で配置すること。',
            'lane' => 'カラーコーン班',
            'required_people_count' => 3,
            'is_at_least' => true,
            'is_critical' => true,
            'status' => 'not_started',
        ]);
    }

    /**
     * 終了時刻が開始時刻以前の場合はバリデーションエラーとなること
     */
    public function test_end_time_must_be_after_start_time(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('timetable-tasks.store'), [
            'timetable_id' => $this->timetable->id,
            'title' => '不正時刻タスク',
            'lane' => 'カラーコーン班',
            'start_time' => '10:00',
            'end_time' => '09:00',
            'required_people_count' => 2,
            'status' => 'not_started',
        ]);

        $response->assertSessionHasErrors('end_time');
    }

    /**
     * 作業タスクを更新できること
     */
    public function test_can_update_task(): void
    {
        $this->actingAs($this->operator);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'title' => '更新前タスク',
            'lane' => '給水設備班',
        ]);

        $response = $this->put(route('timetable-tasks.update', $task), [
            'timetable_id' => $this->timetable->id,
            'title' => '更新後タスク名称',
            'content' => '給水タンクを10個追加配置する',
            'lane' => '給水設備班',
            'start_time' => '08:00',
            'end_time' => '10:00',
            'required_people_count' => 5,
            'is_at_least' => '1',
            'status' => 'in_progress',
        ]);

        $response->assertRedirect(route('timetables.show', $this->timetable));

        $this->assertDatabaseHas('timetable_tasks', [
            'id' => $task->id,
            'title' => '更新後タスク名称',
            'content' => '給水タンクを10個追加配置する',
            'required_people_count' => 5,
            'is_at_least' => true,
            'status' => 'in_progress',
        ]);
    }

    /**
     * 作業タスクを削除できること
     */
    public function test_can_delete_task(): void
    {
        $this->actingAs($this->operator);

        $task = TimetableTask::factory()->create(['timetable_id' => $this->timetable->id]);

        $response = $this->delete(route('timetable-tasks.destroy', $task));
        $response->assertRedirect(route('timetables.show', $this->timetable));

        $this->assertDatabaseMissing('timetable_tasks', ['id' => $task->id]);
    }

    /**
     * スタッフをタスクに割り当て（アサイン）できること
     */
    public function test_can_assign_staff_to_task(): void
    {
        $this->actingAs($this->operator);

        $task = TimetableTask::factory()->create(['timetable_id' => $this->timetable->id]);
        $staff = Staff::factory()->create(['name' => 'アサイン被験者']);

        $response = $this->post(route('timetable-tasks.assign', $task), [
            'staff_id' => $staff->id,
            'role_description' => '誘導リーダー',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task->id,
            'staff_id' => $staff->id,
            'role_description' => '誘導リーダー',
            'attendance_status' => 'assigned',
        ]);
    }

    /**
     * 出欠ステータスを更新できること
     */
    public function test_can_update_attendance_status(): void
    {
        $this->actingAs($this->operator);

        $task = TimetableTask::factory()->create(['timetable_id' => $this->timetable->id]);
        $staff = Staff::factory()->create();
        $assignment = TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $staff->id,
            'attendance_status' => 'assigned',
        ]);

        $response = $this->patch(route('timetable-tasks.update-attendance', [
            'task' => $task,
            'assignment' => $assignment,
        ]), [
            'attendance_status' => 'checked_in',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('task_staff_assignments', [
            'id' => $assignment->id,
            'attendance_status' => 'checked_in',
        ]);
    }

    /**
     * スタッフのアサインを解除できること
     */
    public function test_can_remove_staff_assignment(): void
    {
        $this->actingAs($this->operator);

        $task = TimetableTask::factory()->create(['timetable_id' => $this->timetable->id]);
        $staff = Staff::factory()->create();
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $staff->id,
        ]);

        $response = $this->delete(route('timetable-tasks.remove-staff', [
            'task' => $task,
            'staff' => $staff,
        ]));

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $task->id,
            'staff_id' => $staff->id,
        ]);
    }

    /**
     * 「〇〇名以上」設定時の人員充足ステータス判定の検証
     */
    public function test_at_least_satisfaction_logic(): void
    {
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'required_people_count' => 2,
            'is_at_least' => true,
        ]);

        $staff1 = Staff::factory()->create();
        $staff2 = Staff::factory()->create();
        $staff3 = Staff::factory()->create();

        // 0名配置: 不足2名
        $this->assertFalse($task->is_satisfied);
        $this->assertSame(2, $task->shortage_count);
        $this->assertSame('2名以上', $task->required_people_label);

        // 1名配置: 不足1名
        TaskStaffAssignment::factory()->create(['timetable_task_id' => $task->id, 'staff_id' => $staff1->id]);
        $task->unsetRelation('assignments');
        $this->assertFalse($task->is_satisfied);
        $this->assertSame(1, $task->shortage_count);

        // 2名配置: 充足（ジャスト）
        TaskStaffAssignment::factory()->create(['timetable_task_id' => $task->id, 'staff_id' => $staff2->id]);
        $task->unsetRelation('assignments');
        $this->assertTrue($task->is_satisfied);
        $this->assertSame(0, $task->shortage_count);

        // 3名配置: 「2名以上」なので充足（過剰でもOK）
        TaskStaffAssignment::factory()->create(['timetable_task_id' => $task->id, 'staff_id' => $staff3->id]);
        $task->unsetRelation('assignments');
        $this->assertTrue($task->is_satisfied);
        $this->assertSame(0, $task->shortage_count);
        $this->assertSame(3, $task->assigned_count);
    }
}
