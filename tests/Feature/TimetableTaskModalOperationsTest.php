<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableTaskModalOperationsTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private User $viewer;

    private Timetable $timetable;

    private TimetableLane $lane;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->viewer = User::factory()->create([
            'role' => 'viewer',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'name' => '2026年本祭運行',
            'target_date' => '2026-10-18',
            'start_time' => '08:00:00',
            'end_time' => '19:00:00',
            'status' => 'active',
        ]);

        $masterLane = Lane::factory()->create([
            'name' => '警備・誘導班',
            'display_order' => 1,
        ]);

        $this->lane = TimetableLane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $masterLane->id,
            'display_order' => 1,
        ]);
    }

    /**
     * 一覧画面で3つのモーダルコンポーネントおよびトリガー属性が描画されること
     */
    public function test_tasks_index_renders_all_modals_and_trigger_attributes(): void
    {
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '交差点誘導',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'location' => '北交差点',
            'content' => '迂回路案内とカラーコーン設置',
            'required_people_count' => 2,
            'is_critical' => true,
            'status' => 'not_started',
        ]);

        $response = $this->actingAs($this->operator)
            ->get(route('timetables.tasks.index', $this->timetable));

        $response->assertOk();

        // 3つのモーダル要素が存在すること
        $response->assertSee('id="task-create-modal"', false);
        $response->assertSee('id="task-edit-modal"', false);
        $response->assertSee('id="task-detail-modal"', false);

        // 各トリガーボタン・リンクが存在すること
        $response->assertSee('data-action="open-task-create"', false);
        $response->assertSee('data-action="open-task-detail"', false);
        $response->assertSee('data-action="open-task-edit"', false);

        // 行データ属性（data-task）にタスク情報が含まれること
        $response->assertSee('data-task-id="'.$task->id.'"', false);
        $response->assertSee('data-task=', false);

        // 詳細モーダル内: 「このタスクを編集」ボタンは削除され、「閉じる」ボタンのみ存在すること
        $response->assertDontSee('id="btn-detail-switch-edit"', false);
        $response->assertDontSee('このタスクを編集');

        // 各モーダル内にスリム2段フォームの必須項目が存在すること
        // 追加モーダル
        $response->assertSee('id="create-modal-title"', false);
        $response->assertSee('id="create-modal-start-time"', false);
        $response->assertSee('id="create-modal-end-time"', false);
        $response->assertSee('id="create-modal-people"', false);
        $response->assertSee('id="create-modal-critical"', false);
        $response->assertSee('id="create-modal-location"', false);
        $response->assertSee('id="create-modal-status"', false);
        $response->assertSee('id="create-modal-content"', false);

        // 編集モーダル
        $response->assertSee('id="edit-modal-title"', false);
        $response->assertSee('id="edit-modal-start-time"', false);
        $response->assertSee('id="edit-modal-end-time"', false);
        $response->assertSee('id="edit-modal-people"', false);
        $response->assertSee('id="edit-modal-critical"', false);
        $response->assertSee('id="edit-modal-location"', false);
        $response->assertSee('id="edit-modal-status"', false);
        $response->assertSee('id="edit-modal-content"', false);
    }

    /**
     * 追加モーダルから単一タスクを正常に登録できること
     */
    public function test_can_create_task_via_modal_submission(): void
    {
        $payload = [
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '正面ゲート案内',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'required_people_count' => 3,
            'is_critical' => '1',
            'is_at_least' => '1',
            'location' => '正面ゲート前',
            'status' => 'not_started',
            'content' => '入場整理券の確認と検温',
            'auto_assign_lead' => '1',
        ];

        $response = $this->actingAs($this->operator)
            ->post(route('timetable-tasks.store'), $payload);

        $response->assertRedirect(route('timetables.tasks.index', $this->timetable->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('timetable_tasks', [
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '正面ゲート案内',
            'required_people_count' => 3,
            'is_critical' => true,
            'is_at_least' => true,
            'location' => '正面ゲート前',
            'status' => 'not_started',
            'content' => '入場整理券の確認と検温',
        ]);
    }

    /**
     * 追加モーダルからの登録時、同一レーン内で時間帯が重複（一部重複含む）しても登録でき、
     * 並行タスク作成時は班長のみ自動アサインされ固定スタッフは自動アサインされないこと
     */
    public function test_can_create_parallel_tasks_with_time_overlap_in_same_lane_and_only_assigns_lead_staff(): void
    {
        $leadStaff = Staff::factory()->create(['name' => 'テスト班長']);
        $fixedStaff = Staff::factory()->create(['name' => '固定メンバー']);
        $this->lane->update(['lead_staff_id' => $leadStaff->id]);
        $this->lane->fixedStaff()->attach($fixedStaff->id);

        // 既存タスク: 09:00 〜 11:00
        TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '既存誘導タスク',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        // 10:00 〜 12:00（一部重複）と 10:30 〜 11:30（完全包含重複）を登録
        $payloadA = [
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '新規並行タスクA',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'required_people_count' => 2,
            'status' => 'not_started',
            'auto_assign_lead' => '1',
        ];
        $payloadB = [
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '新規並行タスクB',
            'start_time' => '10:30',
            'end_time' => '11:30',
            'required_people_count' => 1,
            'status' => 'not_started',
            'auto_assign_lead' => '1',
        ];

        $responseA = $this->actingAs($this->operator)
            ->post(route('timetable-tasks.store'), $payloadA);
        $responseA->assertRedirect(route('timetables.tasks.index', $this->timetable->id));
        $responseA->assertSessionHasNoErrors();

        $responseB = $this->actingAs($this->operator)
            ->post(route('timetable-tasks.store'), $payloadB);
        $responseB->assertRedirect(route('timetables.tasks.index', $this->timetable->id));
        $responseB->assertSessionHasNoErrors();

        $taskA = TimetableTask::where('title', '新規並行タスクA')->firstOrFail();
        $taskB = TimetableTask::where('title', '新規並行タスクB')->firstOrFail();

        // 班長は全タスクに自動アサインされること
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $taskA->id,
            'staff_id' => $leadStaff->id,
            'role_description' => '班長',
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $taskB->id,
            'staff_id' => $leadStaff->id,
            'role_description' => '班長',
        ]);

        // 並行タスクには固定スタッフは自動アサインされないこと
        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $taskA->id,
            'staff_id' => $fixedStaff->id,
        ]);
        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $taskB->id,
            'staff_id' => $fixedStaff->id,
        ]);

        // 時間帯が重複しない単独タスク（13:00 〜 14:00）作成時は班長＋固定スタッフの両方が自動アサインされること
        $standalonePayload = [
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '午後単独タスク',
            'start_time' => '13:00',
            'end_time' => '14:00',
            'required_people_count' => 2,
            'status' => 'not_started',
            'auto_assign_lead' => '1',
        ];

        $this->actingAs($this->operator)
            ->post(route('timetable-tasks.store'), $standalonePayload)
            ->assertSessionHasNoErrors();

        $standaloneTask = TimetableTask::where('title', '午後単独タスク')->firstOrFail();
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $standaloneTask->id,
            'staff_id' => $leadStaff->id,
            'role_description' => '班長',
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $standaloneTask->id,
            'staff_id' => $fixedStaff->id,
            'role_description' => '固定スタッフ',
        ]);
    }

    /**
     * 編集モーダルからタスクを正常に更新できること
     */
    public function test_can_update_task_via_modal_submission(): void
    {
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '旧タスク名',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'required_people_count' => 1,
            'status' => 'not_started',
        ]);

        $payload = [
            'timetable_lane_id' => $this->lane->id,
            'title' => '新タスク名（更新後）',
            'start_time' => '09:00',
            'end_time' => '10:30',
            'required_people_count' => 4,
            'is_critical' => '1',
            'is_at_least' => '1',
            'location' => '第2ゲート横',
            'status' => 'in_progress',
            'content' => '更新後の指示内容',
        ];

        $response = $this->actingAs($this->operator)
            ->put(route('timetable-tasks.update', $task), $payload);

        $response->assertRedirect(route('timetables.tasks.index', $this->timetable->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('timetable_tasks', [
            'id' => $task->id,
            'title' => '新タスク名（更新後）',
            'required_people_count' => 4,
            'is_critical' => true,
            'is_at_least' => true,
            'location' => '第2ゲート横',
            'status' => 'in_progress',
            'content' => '更新後の指示内容',
        ]);
    }

    /**
     * 編集モーダルでの更新時、自身以外の同一レーン内タスクと時間帯の一部が重複する時刻へ変更しても正常に更新できること
     */
    public function test_update_task_succeeds_when_overlapping_with_another_task_in_same_lane(): void
    {
        TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '既存他タスク',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $taskToUpdate = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '更新対象タスク',
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
        ]);

        // 11:30 〜 13:30（既存他タスクと一部重複）
        $payload = [
            'timetable_lane_id' => $this->lane->id,
            'title' => '更新対象タスク（並行時刻）',
            'start_time' => '11:30',
            'end_time' => '13:30',
            'required_people_count' => 2,
            'status' => 'not_started',
        ];

        $response = $this->actingAs($this->operator)
            ->put(route('timetable-tasks.update', $taskToUpdate), $payload);

        $response->assertRedirect(route('timetables.tasks.index', $this->timetable->id));
        $response->assertSessionHasNoErrors();

        $taskToUpdate->refresh();
        $this->assertSame('更新対象タスク（並行時刻）', $taskToUpdate->title);
        $this->assertSame('11:30', substr($taskToUpdate->start_time, 0, 5));
        $this->assertSame('13:30', substr($taskToUpdate->end_time, 0, 5));
    }

    /**
     * 編集時、自タスクの既存時間帯との重複判定は除外され正常に更新できること
     */
    public function test_update_task_permits_self_time_overlap(): void
    {
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => 'タイトルのみ変更',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'required_people_count' => 2,
            'status' => 'not_started',
        ]);

        $payload = [
            'timetable_lane_id' => $this->lane->id,
            'title' => '変更後タイトル',
            'start_time' => '09:00',
            'end_time' => '11:00',
            'required_people_count' => 2,
            'status' => 'not_started',
        ];

        $response = $this->actingAs($this->operator)
            ->put(route('timetable-tasks.update', $task), $payload);

        $response->assertRedirect(route('timetables.tasks.index', $this->timetable->id));
        $response->assertSessionHasNoErrors();

        $task->refresh();
        $this->assertSame('変更後タイトル', $task->title);
    }

    /**
     * 別レーンであれば同一時間帯であっても重複とならず登録・更新できること
     */
    public function test_tasks_in_different_lanes_can_have_overlapping_times(): void
    {
        $otherMasterLane = Lane::factory()->create([
            'name' => '衛生・救護班',
            'display_order' => 2,
        ]);
        $otherLane = TimetableLane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $otherMasterLane->id,
            'display_order' => 2,
        ]);

        TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '警備タスク',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        // 別レーンで同一時刻 10:00 〜 12:00
        $payload = [
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $otherLane->id,
            'title' => '救護待機タスク',
            'start_time' => '10:00',
            'end_time' => '12:00',
            'required_people_count' => 2,
            'status' => 'not_started',
        ];

        $response = $this->actingAs($this->operator)
            ->post(route('timetable-tasks.store'), $payload);

        $response->assertRedirect(route('timetables.tasks.index', $this->timetable->id));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('timetable_tasks', [
            'timetable_lane_id' => $otherLane->id,
            'title' => '救護待機タスク',
        ]);
    }

    /**
     * 閲覧者（viewer）はモーダル操作トリガーが表示されず、更新・削除権限を持たないこと
     */
    public function test_viewer_cannot_view_triggers_or_mutate_tasks(): void
    {
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $this->lane->id,
            'title' => '閲覧専用タスク',
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('timetables.tasks.index', $this->timetable));

        $response->assertOk();
        // 追加・編集トリガーは表示されないこと
        $response->assertDontSee('data-action="open-task-create"', false);
        $response->assertDontSee('data-action="open-task-edit"', false);

        // 詳細トリガーは閲覧者でも表示されること
        $response->assertSee('data-action="open-task-detail"', false);

        // 更新リクエストは 403 Forbidden
        $this->actingAs($this->viewer)
            ->put(route('timetable-tasks.update', $task), [
                'timetable_lane_id' => $this->lane->id,
                'title' => '改変試行',
                'start_time' => '09:00',
                'end_time' => '10:00',
                'required_people_count' => 1,
                'status' => 'not_started',
            ])->assertForbidden();

        // 削除リクエストは 403 Forbidden
        $this->actingAs($this->viewer)
            ->delete(route('timetable-tasks.destroy', $task))
            ->assertForbidden();
    }
}
