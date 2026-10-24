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

class ConflictDetectionTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private Timetable $timetable;

    private Lane $lane;

    private Staff $staff;

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
            'name' => '運行管理班',
        ]);

        $this->staff = Staff::factory()->create([
            'name' => '佐藤 健太',
            'kana' => 'サトウ ケンタ',
            'is_active' => true,
        ]);
    }

    /**
     * 重複がない場合は通常アサインが成功すること
     */
    public function test_can_assign_staff_when_no_time_conflict_exists(): void
    {
        $this->actingAs($this->operator);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        $response = $this->post(route('timetable-tasks.assign', $task), [
            'staff_id' => $this->staff->id,
            'role_description' => '本部連絡係',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'スタッフをタスクに割り当てました。');
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task->id,
            'staff_id' => $this->staff->id,
            'role_description' => '本部連絡係',
        ]);
    }

    /**
     * 同一時間帯に別タスクへアサインされている場合、forceなしでは重複警告となりアサインされないこと
     */
    public function test_assign_staff_fails_with_warning_when_time_conflict_exists_without_force(): void
    {
        $this->actingAs($this->operator);

        // 既存タスクA (09:00 - 11:00) にスタッフを配置
        $existingTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '本部テント設営',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $existingTask->id,
            'staff_id' => $this->staff->id,
            'attendance_status' => 'assigned',
        ]);

        // 新規タスクB (10:00 - 12:00) に同じスタッフをアサイン試行
        $newTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '給水所巡回',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        // 通常HTMLフォーム送信
        $response = $this->post(route('timetable-tasks.assign', $newTask), [
            'staff_id' => $this->staff->id,
            'role_description' => '巡回補助',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('conflict_warning');
        $response->assertSessionHas('conflict_staff_id', $this->staff->id);

        // アサインされていないこと
        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $newTask->id,
            'staff_id' => $this->staff->id,
        ]);

        // JSONリクエストの場合409 Conflictとなること
        $jsonResponse = $this->postJson(route('timetable-tasks.assign', $newTask), [
            'staff_id' => $this->staff->id,
        ]);

        $jsonResponse->assertStatus(409)
            ->assertJson([
                'has_conflict' => true,
                'conflicting_task' => [
                    'id' => $existingTask->id,
                    'title' => '本部テント設営',
                ],
            ]);
    }

    /**
     * 重複があっても force=1 を指定した場合は重複承認の上でアサインが完了すること
     */
    public function test_assign_staff_succeeds_when_force_flag_is_true_despite_conflict(): void
    {
        $this->actingAs($this->operator);

        // 既存タスク (09:00 - 11:00)
        $existingTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $existingTask->id,
            'staff_id' => $this->staff->id,
        ]);

        // 重複する別タスク (10:00 - 12:00)
        $newTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $response = $this->post(route('timetable-tasks.assign', $newTask), [
            'staff_id' => $this->staff->id,
            'role_description' => '兼務担当',
            'force' => '1',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'スタッフをタスクに割り当てました（重複を承認）。');
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $newTask->id,
            'staff_id' => $this->staff->id,
            'role_description' => '兼務担当',
        ]);
    }

    /**
     * 前後の時間が連続している場合（09:00-10:00 と 10:00-11:00）は重複と判定されないこと
     */
    public function test_consecutive_tasks_without_time_overlap_do_not_trigger_conflict(): void
    {
        $this->actingAs($this->operator);

        // タスクA (09:00 - 10:00)
        $taskA = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $taskA->id,
            'staff_id' => $this->staff->id,
        ]);

        // タスクB (10:00 - 11:00): 終了時刻と開始時刻が同一で重複なし
        $taskB = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
        ]);

        $response = $this->post(route('timetable-tasks.assign', $taskB), [
            'staff_id' => $this->staff->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'スタッフをタスクに割り当てました。');
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $taskB->id,
            'staff_id' => $this->staff->id,
        ]);
    }

    /**
     * 異なる運行日（別タイムテーブル）の同時刻タスクは重複と判定されないこと
     */
    public function test_tasks_on_different_timetables_do_not_trigger_conflict(): void
    {
        $this->actingAs($this->operator);

        $otherTimetable = Timetable::factory()->create([
            'target_date' => now()->addDay()->format('Y-m-d'),
            'name' => 'まつり本番 2日目',
        ]);
        $otherLane = Lane::factory()->create(['timetable_id' => $otherTimetable->id]);

        // 2日目のタスク (09:00 - 11:00) にアサイン
        $day2Task = TimetableTask::factory()->create([
            'timetable_id' => $otherTimetable->id,
            'lane_id' => $otherLane->id,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $day2Task->id,
            'staff_id' => $this->staff->id,
        ]);

        // 1日目の同時刻タスク (09:00 - 11:00) にアサイン
        $day1Task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        $response = $this->post(route('timetable-tasks.assign', $day1Task), [
            'staff_id' => $this->staff->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'スタッフをタスクに割り当てました。');
    }

    /**
     * 既存タスクの出欠が欠席（absent）の場合は重複と判定されないこと
     */
    public function test_absent_assignment_does_not_trigger_conflict(): void
    {
        $this->actingAs($this->operator);

        // 既存タスク (09:00 - 11:00) だが欠席
        $taskA = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $taskA->id,
            'staff_id' => $this->staff->id,
            'attendance_status' => 'absent',
        ]);

        // 新規タスク (10:00 - 12:00)
        $taskB = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $response = $this->post(route('timetable-tasks.assign', $taskB), [
            'staff_id' => $this->staff->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'スタッフをタスクに割り当てました。');
    }

    /**
     * タスク詳細画面で重複中スタッフの選択肢ラベルに警告が表示されること
     */
    public function test_task_show_view_renders_conflicts_map(): void
    {
        $this->actingAs($this->operator);

        // 既存タスク (09:00 - 11:00)
        $taskA = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '神輿先導',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $taskA->id,
            'staff_id' => $this->staff->id,
        ]);

        // 詳細閲覧対象の重複タスク (10:00 - 12:00)
        $taskB = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '交通整理B',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $response = $this->get(route('timetable-tasks.show', $taskB));

        $response->assertStatus(200);
        $response->assertSee('重複中: 神輿先導');
    }
}
