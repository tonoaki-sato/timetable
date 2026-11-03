<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use App\Services\StaffAllocationService;
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

        // 班長設定（staff1 を神輿誘導班の班長に任命）
        $this->lane->update(['lead_staff_id' => $staff1->id]);

        // 未ログイン時はログイン画面へリダイレクト
        $response = $this->get(route('timetables.staff-timeline', $this->timetable));
        $response->assertRedirect(route('login'));

        // ログイン時は正常表示
        $this->actingAs($this->operator);
        $response = $this->get(route('timetables.staff-timeline', $this->timetable));
        $response->assertStatus(200);
        $response->assertSee('まつり運行 タイムライン検証 人員配置');
        $response->assertSee('スタッフ稼働タイムライン');
        $response->assertSee('山下 達也');
        $response->assertSee('佐藤 健太');
        $response->assertSee('神輿先導警備');
        // タスク枠からはロール（先導誘導チーフ等の班長・担当）が削除されること
        $response->assertDontSee('先導誘導チーフ');
        // staff1自身が神輿誘導班の班長のため、タスク枠内の班長名表示は非表示であること
        $response->assertDontSee('班長: 山下 達也');

        // 所属団体セレクトボックスは削除され、班長表示切り替えチェックボックスが存在すること
        $response->assertDontSee('timeline-affiliation-select');
        $response->assertSee('id="timeline-leader-filter"', false);
        $response->assertSee('班長を表示');

        // クイックアサインモーダルから担当役割フォームが削除されていること
        $response->assertDontSee('timelineRoleInput');
        $response->assertDontSee('担当役割（任意）');

        // staff1 には班長属性とバッジ（👑アイコンなし）、staff2 には非班長属性と一般バッジが出力されていること
        $response->assertSee('data-is-leader="1"', false);
        $response->assertSee('data-is-leader="0"', false);
        $response->assertSee('班長（担当班: 神輿誘導班）');
        $response->assertDontSee('<span>👑</span>', false);
        $response->assertSee('一般スタッフ');
        $response->assertSee('title="神輿誘導班"', false);
        $response->assertSee('title="ボランティアA"', false);

        // ビュー変数に tasksWithShortage, leadStaffIds が含まれていること
        $response->assertViewHas('tasksWithShortage');
        $response->assertViewHas('leadStaffIds', [$staff1->id]);
        $response->assertViewHas('laneNamesByLeadStaff', [$staff1->id => '神輿誘導班']);
        $tasksWithShortage = $response->viewData('tasksWithShortage');
        $this->assertCount(1, $tasksWithShortage);
        $this->assertSame($this->task->id, $tasksWithShortage[0]['id']);
    }

    /**
     * 自身がそのタスクの班長の場合はタスク枠の班長名が非表示になり、一般スタッフや他班応援時には表示されること
     */
    public function test_task_leader_is_hidden_when_self_is_leader_and_shown_for_other_staff(): void
    {
        $leader = Staff::factory()->create([
            'name' => 'リーダー 太郎',
            'affiliation' => '神輿班',
            'is_active' => true,
        ]);
        $general = Staff::factory()->create([
            'name' => '一般 次郎',
            'affiliation' => 'ボランティア',
            'is_active' => true,
        ]);

        $this->lane->update(['lead_staff_id' => $leader->id]);

        // 班長と一般スタッフの両方を同じ神輿タスクにアサイン
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $this->task->id,
            'staff_id' => $leader->id,
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $this->task->id,
            'staff_id' => $general->id,
        ]);

        // 班長未設定レーンのタスクも作成し一般スタッフにアサイン
        $laneWithoutLead = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '清掃班',
            'lead_staff_id' => null,
        ]);
        $taskWithoutLead = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $laneWithoutLead->id,
            'title' => '清掃作業',
            'start_time' => '13:00:00',
            'end_time' => '14:00:00',
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $taskWithoutLead->id,
            'staff_id' => $general->id,
        ]);

        $this->actingAs($this->operator);
        $response = $this->get(route('timetables.staff-timeline', $this->timetable));
        $response->assertStatus(200);

        // 一般スタッフの行では「班長: リーダー 太郎」が表示されること
        $response->assertSee('班長: リーダー 太郎');
        // 班長未設定タスクでは「班長未定」が表示されること
        $response->assertSee('班長未定');

        $content = $response->getContent();

        // リーダー太郎の行（data-staff-name="リーダー 太郎"）のHTMLブロック内には "班長: リーダー 太郎" が含まれないこと
        preg_match('/<tr[^>]*data-staff-name="リーダー 太郎"[^>]*>(.*?)<\/tr>/s', $content, $leaderRowMatches);
        $this->assertNotEmpty($leaderRowMatches);
        $leaderRowHtml = $leaderRowMatches[1];
        $this->assertStringNotContainsString('班長: リーダー 太郎', $leaderRowHtml);

        // 一般次郎の行（data-staff-name="一般 次郎"）のHTMLブロック内には "班長: リーダー 太郎" が含まれること
        preg_match('/<tr[^>]*data-staff-name="一般 次郎"[^>]*>(.*?)<\/tr>/s', $content, $generalRowMatches);
        $this->assertNotEmpty($generalRowMatches);
        $generalRowHtml = $generalRowMatches[1];
        $this->assertStringContainsString('班長: リーダー 太郎', $generalRowHtml);
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

        /** @var StaffAllocationService $service */
        $service = app(StaffAllocationService::class);
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

    /**
     * クイックアサインモーダルからの配置（role_description省略時）が正常に完了すること
     */
    public function test_can_quick_assign_staff_without_role_description(): void
    {
        $this->actingAs($this->operator);

        $staff = Staff::factory()->create([
            'name' => '配置テスト スタッフ',
            'is_active' => true,
        ]);

        $response = $this->postJson(route('timetable-tasks.assign-bulk', $this->task), [
            'staff_ids' => [$staff->id],
            'force' => 0,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'assigned_count' => 1,
        ]);

        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $this->task->id,
            'staff_id' => $staff->id,
            'role_description' => null,
            'attendance_status' => 'assigned',
        ]);
    }
}
