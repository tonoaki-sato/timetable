<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Timetable;
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

    private Lane $lane;

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

        $this->lane = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '警備・誘導班',
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
            'lane_id' => $this->lane->id,
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
            'lane_id' => $this->lane->id,
            'tasks' => [
                [
                    'title' => '正面ゲート案内',
                    'start_time' => '09:00',
                    'end_time' => '11:00',
                    'required_people_count' => 3,
                    'is_critical' => '1',
                    'is_at_least' => '1',
                    'location' => '正面ゲート前',
                    'status' => 'not_started',
                    'content' => '入場整理券の確認と検温',
                ],
            ],
            'auto_assign_lead' => '1',
        ];

        $response = $this->actingAs($this->operator)
            ->post(route('timetable-tasks.store'), $payload);

        $response->assertRedirect(route('timetables.tasks.index', $this->timetable->id));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('timetable_tasks', [
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
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
     * 追加モーダルからの登録時、同一レーン内で時間帯が重複する場合はバリデーションエラーとなること
     */
    public function test_create_task_fails_when_time_overlaps_in_same_lane(): void
    {
        TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '既存誘導タスク',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
        ]);

        // 10:00 〜 12:00（重複）
        $payload = [
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'tasks' => [
                [
                    'title' => '新規重複タスク',
                    'start_time' => '10:00',
                    'end_time' => '12:00',
                    'required_people_count' => 2,
                    'status' => 'not_started',
                ],
            ],
        ];

        $response = $this->actingAs($this->operator)
            ->post(route('timetable-tasks.store'), $payload);

        $response->assertSessionHasErrors(['tasks']);
        $this->assertDatabaseMissing('timetable_tasks', [
            'title' => '新規重複タスク',
        ]);
    }

    /**
     * 編集モーダルからタスクを正常に更新できること
     */
    public function test_can_update_task_via_modal_submission(): void
    {
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '旧タスク名',
            'start_time' => '08:30:00',
            'end_time' => '10:00:00',
            'required_people_count' => 1,
            'status' => 'not_started',
        ]);

        $payload = [
            'lane_id' => $this->lane->id,
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
     * 編集モーダルでの更新時、自身以外の同一レーン内タスクと重複する場合はエラーとなること
     */
    public function test_update_task_fails_when_overlapping_with_another_task_in_same_lane(): void
    {
        $existingTask = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '既存他タスク',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        $taskToUpdate = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '更新対象タスク',
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
        ]);

        // 11:30 〜 13:30（既存他タスクと重複）
        $payload = [
            'lane_id' => $this->lane->id,
            'title' => '更新対象タスク（重複時刻）',
            'start_time' => '11:30',
            'end_time' => '13:30',
            'required_people_count' => 2,
            'status' => 'not_started',
        ];

        $response = $this->actingAs($this->operator)
            ->put(route('timetable-tasks.update', $taskToUpdate), $payload);

        $response->assertSessionHasErrors(['start_time']);

        $taskToUpdate->refresh();
        $this->assertSame('13:00:00', $taskToUpdate->start_time);
    }

    /**
     * 編集時、自タスクの既存時間帯との重複判定は除外され正常に更新できること
     */
    public function test_update_task_permits_self_time_overlap(): void
    {
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => 'タイトルのみ変更',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'required_people_count' => 2,
            'status' => 'not_started',
        ]);

        $payload = [
            'lane_id' => $this->lane->id,
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
        $otherLane = Lane::factory()->create([
            'timetable_id' => $this->timetable->id,
            'name' => '衛生・救護班',
            'display_order' => 2,
        ]);

        TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $this->lane->id,
            'title' => '警備タスク',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
        ]);

        // 別レーンで同一時刻 10:00 〜 12:00
        $payload = [
            'timetable_id' => $this->timetable->id,
            'lane_id' => $otherLane->id,
            'tasks' => [
                [
                    'title' => '救護待機タスク',
                    'start_time' => '10:00',
                    'end_time' => '12:00',
                    'required_people_count' => 2,
                    'status' => 'not_started',
                ],
            ],
        ];

        $response = $this->actingAs($this->operator)
            ->post(route('timetable-tasks.store'), $payload);

        $response->assertRedirect(route('timetables.tasks.index', $this->timetable->id));
        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('timetable_tasks', [
            'lane_id' => $otherLane->id,
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
            'lane_id' => $this->lane->id,
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
                'lane_id' => $this->lane->id,
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
