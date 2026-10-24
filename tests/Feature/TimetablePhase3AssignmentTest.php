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

class TimetablePhase3AssignmentTest extends TestCase
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
            'name' => 'まつり運行 タイムライン検証',
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
        ]);

        $this->lane = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '神輿誘導班',
            'color' => 'indigo',
        ]);

        $this->task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '神輿先導警備',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'required_people_count' => 2,
        ]);
    }

    /**
     * スタッフ別リソースタイムラインビューが正しくレンダリングされること
     */
    public function test_can_render_staff_timeline_view(): void
    {
        $staff1 = Staff::factory()->create([
            'name' => '山下 達也',
            'affiliation' => '神輿誘導班',
            'checked_in_at' => now(),
            'is_active' => true,
        ]);

        $staff2 = Staff::factory()->create([
            'name' => '佐藤 健太',
            'affiliation' => 'ボランティアA',
            'is_active' => true,
        ]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staff1->id,
            'role_description' => '先導誘導チーフ',
            'attendance_status' => 'assigned',
        ]);

        // 未ログイン時はログイン画面へリダイレクト
        $response = $this->get(route('timetables.staff-timeline', $this->timetable));
        $response->assertRedirect(route('login'));

        // ログイン時は正常表示
        $this->actingAs($this->operator);
        $response = $this->get(route('timetables.staff-timeline', $this->timetable));
        $response->assertStatus(200);
        $response->assertSee('まつり運行 タイムライン検証 - スタッフ稼働タイムライン');
        $response->assertSee('山下 達也');
        $response->assertSee('佐藤 健太');
        $response->assertSee('神輿先導警備');
        $response->assertSee('先導誘導チーフ');

        // ビュー変数に tasksWithShortage が含まれていること
        $response->assertViewHas('tasksWithShortage');
        $tasksWithShortage = $response->viewData('tasksWithShortage');
        $this->assertCount(1, $tasksWithShortage);
        $this->assertSame($this->task->id, $tasksWithShortage[0]['id']);
    }

    /**
     * チェックイン済みスタッフが優先ソート（上位サジェスト）されること
     */
    public function test_checked_in_staff_is_prioritized_in_conflict_detection_service(): void
    {
        // 2名とも同等条件（同一所属、同一稼働時間0分、空き）
        $staffNotCheckedIn = Staff::factory()->create([
            'name' => '未着 スタッフ',
            'affiliation' => '一般広報',
            'is_active' => true,
            'checked_in_at' => null,
        ]);

        $staffCheckedIn = Staff::factory()->create([
            'name' => '到着済 スタッフ',
            'affiliation' => '一般広報',
            'is_active' => true,
            'checked_in_at' => now(),
        ]);

        /** @var ConflictDetectionService $service */
        $service = app(ConflictDetectionService::class);
        $prioritized = $service->getPrioritizedStaffForTask($this->task, collect([$staffNotCheckedIn, $staffCheckedIn]));

        $this->assertCount(2, $prioritized);
        // 到着済みスタッフが1番目に来ること
        $this->assertSame($staffCheckedIn->id, $prioritized[0]->id);
        $this->assertTrue($prioritized[0]->is_checked_in);
        $this->assertSame($staffNotCheckedIn->id, $prioritized[1]->id);
        $this->assertFalse($prioritized[1]->is_checked_in);
    }

    /**
     * スタッフのワンタップ交代（代替補充）が正常に行えること
     */
    public function test_can_swap_staff_successfully(): void
    {
        $this->actingAs($this->operator);

        $oldStaff = Staff::factory()->create(['name' => '体調不良スタッフ', 'is_active' => true]);
        $newStaff = Staff::factory()->create(['name' => '元気な代替スタッフ', 'is_active' => true]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $this->task->id,
            'staff_id' => $oldStaff->id,
            'role_description' => '誘導補佐',
            'attendance_status' => 'absent',
        ]);

        $response = $this->post(route('timetable-tasks.swap-staff', $this->task), [
            'old_staff_id' => $oldStaff->id,
            'new_staff_id' => $newStaff->id,
            'role_description' => '誘導補佐（代替）',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // 旧スタッフのアサインが削除され、新スタッフのアサインが作成されていること
        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $oldStaff->id,
        ]);

        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $newStaff->id,
            'role_description' => '誘導補佐（代替）',
        ]);
    }

    /**
     * 交代候補に重複がある場合、警告が返り、force=1で強制承認できること
     */
    public function test_swap_staff_detects_conflict_and_allows_force(): void
    {
        $this->actingAs($this->operator);

        $oldStaff = Staff::factory()->create(['name' => '外れるスタッフ', 'is_active' => true]);
        $newStaff = Staff::factory()->create(['name' => '他タスク配置中のスタッフ', 'is_active' => true]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $this->task->id,
            'staff_id' => $oldStaff->id,
            'role_description' => '担当A',
        ]);

        // 同一時間帯（09:00〜11:00）に重なる別タスクを作成し新スタッフを配置
        $overlappingTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'title' => '重複タスク: 交通整理',
            'start_time' => '09:30:00',
            'end_time' => '10:30:00',
        ]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $overlappingTask->id,
            'staff_id' => $newStaff->id,
        ]);

        // 1. forceなし（JSONリクエスト）: 409 Conflict が返ること
        $responseJson = $this->postJson(route('timetable-tasks.swap-staff', $this->task), [
            'old_staff_id' => $oldStaff->id,
            'new_staff_id' => $newStaff->id,
        ]);

        $responseJson->assertStatus(409);
        $responseJson->assertJson([
            'success' => false,
            'has_conflict' => true,
        ]);

        // 2. forceなし（通常のWeb POST）: 重複警告セッション付きでリダイレクト
        $responseWeb = $this->post(route('timetable-tasks.swap-staff', $this->task), [
            'old_staff_id' => $oldStaff->id,
            'new_staff_id' => $newStaff->id,
        ]);

        $responseWeb->assertRedirect();
        $responseWeb->assertSessionHas('swap_conflict_warning');

        // 3. force=1 指定: 重複を承認して交代完了
        $responseForce = $this->post(route('timetable-tasks.swap-staff', $this->task), [
            'old_staff_id' => $oldStaff->id,
            'new_staff_id' => $newStaff->id,
            'force' => 1,
        ]);

        $responseForce->assertRedirect();
        $responseForce->assertSessionHas('success');

        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $oldStaff->id,
        ]);

        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $newStaff->id,
        ]);
    }

    /**
     * スワップ処理のバリデーション検証（同一人物や未アサイン者）
     */
    public function test_swap_staff_validations(): void
    {
        $this->actingAs($this->operator);

        $staff = Staff::factory()->create(['is_active' => true]);

        // 同一スタッフ同士の指定はバリデーションエラー
        $response = $this->postJson(route('timetable-tasks.swap-staff', $this->task), [
            'old_staff_id' => $staff->id,
            'new_staff_id' => $staff->id,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['new_staff_id']);

        // 当該タスクにアサインされていないスタッフを old_staff_id に指定した場合はエラー
        $otherStaff = Staff::factory()->create(['is_active' => true]);
        $responseNotAssigned = $this->postJson(route('timetable-tasks.swap-staff', $this->task), [
            'old_staff_id' => $staff->id,
            'new_staff_id' => $otherStaff->id,
        ]);

        $responseNotAssigned->assertStatus(422);
        $responseNotAssigned->assertJson([
            'success' => false,
            'message' => '交代対象のスタッフはこのタスクにアサインされていません。',
        ]);
    }
}
