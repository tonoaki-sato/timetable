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
        $this->timetable->createDefaultLanes();
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
            'status' => 'not_started',
        ]);

        $response->assertRedirect(route('timetable.index', ['timetable_id' => $this->timetable->id]));

        $lane = $this->timetable->lanes()->where('name', 'カラーコーン')->firstOrFail();

        $this->assertDatabaseHas('timetable_tasks', [
            'timetable_id' => $this->timetable->id,
            'lane_id' => $lane->id,
            'title' => '西口ゲート カラーコーン設置',
            'content' => '西口ゲートから交差点までコーンバーを2m間隔で配置すること。',
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
     * 単一作業タスク更新ルートが削除され404または405となること（一括編集へ一本化）
     */
    public function test_single_task_update_route_is_removed(): void
    {
        $this->actingAs($this->operator);

        $waterLane = $this->timetable->lanes()->where('name', '給水設備')->firstOrFail();

        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $waterLane->id,
            'title' => '更新前タスク',
        ]);

        $response = $this->put('/timetable-tasks/'.$task->id, [
            'title' => '更新後タスク名称',
        ]);

        $response->assertStatus(405);
    }

    /**
     * 作業タスクを削除できること
     */
    public function test_can_delete_task(): void
    {
        $this->actingAs($this->operator);

        $task = TimetableTask::factory()->create(['timetable_id' => $this->timetable->id]);

        $response = $this->delete(route('timetable-tasks.destroy', $task));
        $response->assertRedirect(route('timetable.index', ['timetable_id' => $this->timetable->id]));

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
     * 作業タスク詳細画面（人員配置・出欠アテンド管理）が正常に表示されること
     */
    public function test_can_view_task_show_page_with_compact_content_and_staff_grid(): void
    {
        $this->actingAs($this->operator);

        $coneLane = $this->timetable->lanes()->where('name', 'カラーコーン')->firstOrFail();

        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $coneLane->id,
            'title' => '中央通り規制コーン設置',
            'content' => "1行目の手順指示です。\n2行目の詳細な注意事項です。",
            'location' => '中央通り交差点',
            'start_time' => '08:00',
            'end_time' => '09:30',
            'required_people_count' => 4,
            'is_at_least' => true,
            'is_critical' => true,
        ]);

        $assignedStaff = Staff::factory()->create([
            'name' => '配置済み太郎',
            'kana' => 'ハイチズミタロウ',
            'affiliation' => 'カラーコーン班',
            'is_active' => true,
        ]);

        $candidateStaff = Staff::factory()->create([
            'name' => '候補者次郎',
            'kana' => 'コウホシャジロウ',
            'affiliation' => '青年部',
            'is_active' => true,
        ]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $assignedStaff->id,
            'role_description' => '班リーダー',
            'attendance_status' => 'checked_in',
        ]);

        $response = $this->get(route('timetable-tasks.show', $task));

        $response->assertOk()
            ->assertSee('カラーコーン 中央通り規制コーン設置')
            ->assertSee('08:00 〜 09:30')
            ->assertSee('中央通り交差点')
            ->assertSee('タスク基本情報・作業指示')
            ->assertSee('1行目の手順指示です。')
            ->assertDontSee('配置スタッフ・出欠アテンド管理')
            ->assertDontSee('スタッフ一括配置・スマートアサイン');
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

    /**
     * 新規登録画面のヘッダー表示（パンくず、一覧に戻るリンク、コンテキストバー）が正常に描画されること
     */
    public function test_create_task_view_renders_header_breadcrumbs_and_back_link(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('timetable-tasks.create', ['timetable_id' => $this->timetable->id]));

        $response->assertStatus(200);

        // パンくずリスト
        $response->assertSee(route('timetables.index'));
        $response->assertSee('タイムテーブル一覧');
        $response->assertSee(route('timetables.show', $this->timetable));
        $response->assertSee($this->timetable->name);
        $response->assertSee(route('timetables.tasks.index', $this->timetable));
        $response->assertSee('タスク一覧');
        $response->assertSee('新規登録');

        // アクション: 一覧に戻るリンク
        $response->assertSee('タスク一覧に戻る');

        // コンテキストバー
        $response->assertSee('対象運行日:');
    }

    /**
     * 詳細画面のヘッダー表示（パンくず、一覧に戻るリンク、タイトル班名付加、コンテキストバー）が正常に描画され、編集・削除ボタンが表示されないこと
     */
    public function test_show_task_view_renders_header_breadcrumbs_and_back_link(): void
    {
        $this->actingAs($this->operator);

        $lane = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '警備班',
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $lane->id,
            'title' => '安全パトロール',
        ]);

        $response = $this->get(route('timetable-tasks.show', $task));

        $response->assertStatus(200);

        // パンくずリスト
        $response->assertSee(route('timetables.index'));
        $response->assertSee('タイムテーブル一覧');
        $response->assertSee(route('timetables.show', $this->timetable));
        $response->assertSee($this->timetable->name);
        $response->assertSee(route('timetables.tasks.index', $this->timetable));
        $response->assertSee('タスク一覧');
        $response->assertSee('警備班 安全パトロール');

        // アクション: 一覧に戻るリンクのみ表示され、タスク情報編集・削除は表示されないこと
        $response->assertSee('タスク一覧に戻る');
        $response->assertDontSee('タスク情報編集');
        $response->assertDontSee('削除');
        $response->assertDontSee('本部確認要');

        // コンテキストバー
        $response->assertSee('対象運行日:');
    }

    /**
     * 単一編集画面へのルートが削除され404となること（一括編集へ一本化）
     */
    public function test_single_task_edit_route_is_removed(): void
    {
        $this->actingAs($this->operator);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'title' => '警備巡回タスク',
        ]);

        $response = $this->get('/timetable-tasks/'.$task->id.'/edit');

        $response->assertStatus(404);
    }
}
