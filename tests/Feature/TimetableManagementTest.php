<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Schedule;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private User $viewer;

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
    }

    /**
     * 未認証ユーザーはログイン画面へリダイレクトされること
     */
    public function test_unauthenticated_users_are_redirected_to_login(): void
    {
        $this->get(route('timetables.index'))->assertRedirect(route('login'));
        $this->get(route('timetables.create'))->assertRedirect(route('login'));
    }

    /**
     * 停止中ユーザーはアクセス拒否（403）されること
     */
    public function test_inactive_users_are_forbidden(): void
    {
        $inactive = User::factory()->create(['is_active' => false]);
        $this->actingAs($inactive)->get(route('timetables.index'))->assertStatus(403);
    }

    /**
     * 一覧画面が表示され、ステータス絞り込みができること
     */
    public function test_index_displays_timetables_and_filters(): void
    {
        $this->actingAs($this->operator);

        $timetableA = Timetable::factory()->create([
            'name' => '1日目 本祭運行',
            'status' => 'active',
            'target_date' => '2026-10-17',
        ]);
        $timetableB = Timetable::factory()->create([
            'name' => '前日 設営運行',
            'status' => 'draft',
            'target_date' => '2026-10-16',
        ]);

        $response = $this->get(route('timetables.index'));
        $response->assertStatus(200);
        $response->assertSee('1日目 本祭運行');
        $response->assertSee('前日 設営運行');
        $response->assertSee(route('timetables.show', $timetableA));
        $response->assertSee(route('timetable.index', ['timetable_id' => $timetableA->id]));
        $response->assertSee(route('timetables.lanes.index', $timetableA));
        $response->assertSee(route('timetables.tasks.index', $timetableA));
        $response->assertSee(route('timetables.staff-timeline', $timetableA));
        $response->assertSee('タイムテーブル');
        $response->assertSee('班一覧');
        $response->assertSee('タスク一覧');
        $response->assertSee('人員一覧');
        $response->assertDontSee('タスク・人員台帳');
        $response->assertSee('運行時間枠');
        $response->assertDontSee('登録タスク');
        $response->assertSeeInOrder([
            'タイムテーブル名 / 概要',
            '運行日',
            '運行時間枠',
            '状態',
            '操作',
        ]);

        // active のみ絞り込み
        $filterResponse = $this->get(route('timetables.index', ['status' => 'active']));
        $filterResponse->assertStatus(200);
        $filterResponse->assertSee('1日目 本祭運行');
        $filterResponse->assertDontSee(route('timetables.show', $timetableB));
        $filterResponse->assertViewHas('timetables', function ($timetables) use ($timetableA, $timetableB) {
            return $timetables->contains($timetableA) && ! $timetables->contains($timetableB);
        });
    }

    /**
     * 新規タイムテーブルを登録できること
     */
    public function test_can_create_timetable(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('timetables.store'), [
            'target_date' => '2026-10-17',
            'name' => '1日目 (Day 1) 本祭運行',
            'description' => 'パレード及びメインステージ周辺の交通規制含む',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'status' => 'active',
        ]);

        $timetable = Timetable::where('name', '1日目 (Day 1) 本祭運行')->firstOrFail();
        $response->assertRedirect(route('timetables.lanes.index', $timetable));

        $this->assertDatabaseHas('timetables', [
            'id' => $timetable->id,
            'name' => '1日目 (Day 1) 本祭運行',
            'status' => 'active',
            'created_by' => $this->operator->id,
        ]);
        $this->assertCount(count(Lane::DEFAULT_LANES), $timetable->lanes);
    }

    /**
     * create_default_lanes を false にすると初期班が自動生成されないこと
     */
    public function test_can_create_timetable_without_default_lanes(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('timetables.store'), [
            'target_date' => '2026-10-18',
            'name' => '2日目 独自班編成',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'status' => 'active',
            'create_default_lanes' => 0,
        ]);

        $timetable = Timetable::where('name', '2日目 独自班編成')->firstOrFail();
        $response->assertRedirect(route('timetables.lanes.index', $timetable));

        $this->assertCount(0, $timetable->lanes);
    }

    /**
     * 新規作成画面にゆとりあるコンテナ幅、およびデフォルト班自動生成のhidden inputが存在すること（ステップガイドは非表示）
     */
    public function test_create_view_displays_expanded_width_and_hidden_input(): void
    {
        $this->actingAs($this->operator);

        $response = $this->get(route('timetables.create'));
        $response->assertStatus(200);
        $response->assertSee('max-w-5xl');
        $response->assertDontSee('まつり運行準備ステップガイド');
        $response->assertSee('<input type="hidden" name="create_default_lanes" value="0">', false);
        $response->assertSee('まつり標準10班（カラーコーン・給水・衛生・巡回・キッズ村など）を自動生成する（推奨）');
    }

    /**
     * 新規作成画面の開催日初期値が当日の日付（またはクエリ指定値）であること
     */
    public function test_create_view_presets_target_date_to_today_or_query_param(): void
    {
        $this->actingAs($this->operator);

        // 指定なしの場合: 当日の日付がプリセットされる
        $response = $this->get(route('timetables.create'));
        $response->assertStatus(200);
        $today = now()->toDateString();
        $response->assertSee('value="'.$today.'"', false);

        // クエリパラメータで target_date を指定した場合: その日付がプリセットされる
        $customDateResponse = $this->get(route('timetables.create', ['target_date' => '2026-11-20']));
        $customDateResponse->assertStatus(200);
        $customDateResponse->assertSee('value="2026-11-20"', false);
    }

    /**
     * チェックを外して文字列 '0' が送信された場合に標準10班が作成されないこと
     */
    public function test_can_create_timetable_without_default_lanes_with_string_zero(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('timetables.store'), [
            'target_date' => '2026-10-19',
            'name' => '3日目 ゼロ班編成',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'status' => 'active',
            'create_default_lanes' => '0',
        ]);

        $timetable = Timetable::where('name', '3日目 ゼロ班編成')->firstOrFail();
        $response->assertRedirect(route('timetables.lanes.index', $timetable));

        $this->assertCount(0, $timetable->lanes);
    }

    /**
     * 閲覧者ロール（viewer）は登録・編集・削除が禁止されること
     */
    public function test_viewer_cannot_create_or_edit_or_delete_timetable(): void
    {
        $this->actingAs($this->viewer);

        $timetable = Timetable::factory()->create();

        $this->get(route('timetables.create'))->assertStatus(403);
        $this->post(route('timetables.store'), [
            'target_date' => '2026-10-25',
            'name' => 'テスト運行',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'status' => 'draft',
        ])->assertStatus(403);

        $this->get(route('timetables.edit', $timetable))->assertStatus(403);
        $this->put(route('timetables.update', $timetable), [
            'target_date' => $timetable->target_date->format('Y-m-d'),
            'name' => '改変運行',
            'start_time' => '09:00',
            'end_time' => '17:00',
            'status' => 'draft',
        ])->assertStatus(403);

        $this->delete(route('timetables.destroy', $timetable))->assertStatus(403);

        // 詳細閲覧は許可されること
        $this->get(route('timetables.show', $timetable))->assertStatus(200);
    }

    /**
     * タイムテーブル情報を更新できること
     */
    public function test_can_update_timetable(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '更新前運行',
            'status' => 'draft',
        ]);

        $response = $this->put(route('timetables.update', $timetable), [
            'target_date' => $timetable->target_date->format('Y-m-d'),
            'name' => '更新後運行名称',
            'description' => '更新された説明文',
            'start_time' => '07:30',
            'end_time' => '19:00',
            'status' => 'active',
        ]);

        $response->assertRedirect(route('timetables.show', $timetable));
        $this->assertDatabaseHas('timetables', [
            'id' => $timetable->id,
            'name' => '更新後運行名称',
            'status' => 'active',
        ]);
    }

    /**
     * タイムテーブルを削除できること
     */
    public function test_can_delete_timetable(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create(['name' => '削除対象運行']);

        $response = $this->delete(route('timetables.destroy', $timetable));
        $response->assertRedirect(route('timetables.index'));

        $this->assertDatabaseMissing('timetables', ['id' => $timetable->id]);
    }

    /**
     * レーンやタスクが紐付いたタイムテーブルでも外部キー制約エラーを起こさず安全に削除できること
     */
    public function test_can_delete_timetable_with_associated_tasks_and_lanes(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create(['name' => 'タスク付き運行']);
        $timetable->createDefaultLanes();

        $lane = $timetable->lanes->first();
        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '削除テスト用タスク',
        ]);

        $response = $this->delete(route('timetables.destroy', $timetable));
        $response->assertRedirect(route('timetables.index'));

        $this->assertDatabaseMissing('timetables', ['id' => $timetable->id]);
        $this->assertDatabaseMissing('timetable_tasks', ['id' => $task->id]);
        $this->assertDatabaseMissing('lanes', ['timetable_id' => $timetable->id]);
    }

    /**
     * 道路状況・まつり状況の終了時刻が開始時刻より前の場合にバリデーションエラーとなること
     */
    public function test_validation_rejects_inverted_period_times(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('timetables.store'), [
            'target_date' => '2026-10-28',
            'name' => '不正時間運行',
            'start_time' => '08:00',
            'end_time' => '18:00',
            'status' => 'draft',
            'road_periods' => [
                [
                    'start_time' => '15:00',
                    'end_time' => '10:00',
                    'label' => '逆転時間',
                ],
            ],
        ]);

        $response->assertSessionHasErrors(['road_periods.0.end_time']);
    }

    /**
     * 運行スケジュール（タイムテーブル）新規作成時にスケジュール管理へ自動同期されること
     */
    public function test_creating_timetable_automatically_syncs_to_schedules(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('timetables.store'), [
            'target_date' => '2026-10-20',
            'name' => 'Day 1 本番運行スケジュール',
            'description' => '宿場まつり本番運行の全体タイムライン',
            'start_time' => '07:30',
            'end_time' => '20:30',
            'status' => 'active',
            'create_default_lanes' => 1,
        ]);

        $timetable = Timetable::where('name', 'Day 1 本番運行スケジュール')->firstOrFail();
        $response->assertRedirect(route('timetables.lanes.index', $timetable));

        // schedules テーブルに同期されていることを検証
        $this->assertDatabaseHas('schedules', [
            'timetable_id' => $timetable->id,
            'title' => 'Day 1 本番運行スケジュール',
            'category' => 'operation',
            'location' => '会場一帯',
            'status' => 'in_progress', // active -> in_progress
            'created_by' => $this->operator->id,
        ]);

        $syncedSchedule = Schedule::where('timetable_id', $timetable->id)->firstOrFail();
        $this->assertSame('2026-10-20', $syncedSchedule->start_date->format('Y-m-d'));
        $this->assertStringStartsWith('07:30', $syncedSchedule->start_time);
        $this->assertStringStartsWith('20:30', $syncedSchedule->end_time);

        // スケジュール一覧画面にアイコン付きで表示されることを検証（運行枠・進行表リンクは一覧から削除済み）
        $listResponse = $this->get(route('schedules.index'));
        $listResponse->assertStatus(200);
        $listResponse->assertSee('Day 1 本番運行スケジュール');
        $listResponse->assertSee('運行スケジュール');
        $listResponse->assertDontSee('運行枠');
        $listResponse->assertDontSee('現場進行マトリクスへ');
    }

    /**
     * タイムテーブル更新時に連動スケジュールも同期されること
     */
    public function test_updating_timetable_updates_synced_schedule(): void
    {
        $this->actingAs($this->operator);

        $this->post(route('timetables.store'), [
            'target_date' => '2026-10-21',
            'name' => '同期前運行名称',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'draft',
        ]);

        $timetable = Timetable::where('name', '同期前運行名称')->firstOrFail();

        $this->put(route('timetables.update', $timetable), [
            'target_date' => '2026-10-22',
            'name' => '同期後運行名称',
            'description' => '更新後の説明',
            'start_time' => '09:00',
            'end_time' => '18:00',
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('schedules', [
            'timetable_id' => $timetable->id,
            'title' => '同期後運行名称',
            'status' => 'completed', // completed -> completed
        ]);

        $updatedSchedule = Schedule::where('timetable_id', $timetable->id)->firstOrFail();
        $this->assertSame('2026-10-22', $updatedSchedule->start_date->format('Y-m-d'));
        $this->assertStringStartsWith('09:00', $updatedSchedule->start_time);
        $this->assertStringStartsWith('18:00', $updatedSchedule->end_time);
    }

    /**
     * タイムテーブル削除時に連動スケジュールも削除されること
     */
    public function test_deleting_timetable_deletes_synced_schedule(): void
    {
        $this->actingAs($this->operator);

        $this->post(route('timetables.store'), [
            'target_date' => '2026-10-23',
            'name' => '削除連動テスト運行',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'status' => 'draft',
        ]);

        $timetable = Timetable::where('name', '削除連動テスト運行')->firstOrFail();

        $this->assertDatabaseHas('schedules', ['timetable_id' => $timetable->id]);

        $this->delete(route('timetables.destroy', $timetable));

        $this->assertDatabaseMissing('timetables', ['id' => $timetable->id]);
        $this->assertDatabaseMissing('schedules', ['timetable_id' => $timetable->id]);
    }
}
