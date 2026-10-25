<?php

namespace Tests\Feature;

use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaneManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $operatorUser;

    protected Timetable $timetable;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->operatorUser = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'name' => 'テスト本番日',
            'target_date' => '2027-10-17',
        ]);
    }

    /**
     * 1. タイムテーブル作成時、デフォルト10レーンが生成され、主担当は未設定（後付け可能）であること
     */
    public function test_default_ten_lanes_are_created_for_timetable(): void
    {
        $this->timetable->createDefaultLanes();

        $lanes = $this->timetable->lanes()->get();
        $this->assertCount(10, $lanes);

        $expectedLaneNames = [
            'カラーコーン',
            '給水設備',
            '衛生',
            '巡回',
            'キッズ村',
            'ちょうちん',
            'ござ市',
            '本陣',
            '道路清掃',
            'ステカン',
        ];

        $this->assertSame($expectedLaneNames, $lanes->pluck('name')->toArray());

        // 初期状態では主担当は未設定（null）であること
        foreach ($lanes as $lane) {
            $this->assertNull($lane->lead_staff_id);
            $this->assertNull($lane->leadStaff);
            $this->assertFalse($lane->hasLead());
        }
    }

    /**
     * 2. レーン管理一覧画面の表示
     */
    public function test_admin_and_operator_can_view_lanes_index(): void
    {
        $this->timetable->createDefaultLanes();

        $response = $this->actingAs($this->adminUser)
            ->get(route('timetables.lanes.index', $this->timetable));

        $response->assertStatus(200);
        $response->assertSee('担当班（レーン）管理');
        $response->assertSee('運行日別 担当班編成一覧に戻る');
        $response->assertDontSee('他日程からコピー');
        $response->assertDontSee('運行マトリクスを見る');
        $response->assertDontSee('copy-lane-modal');
        $response->assertDontSee('日程選択へ戻る');
        $response->assertSee('カラーコーン');
        $response->assertSee('ステカン');
        $response->assertSee('lanes-container');
        $response->assertSee('⋮⋮');
        $response->assertSee('主要班');
        $response->assertSee('delete-migration-modal');
    }

    /**
     * 3. 主担当が未設定のままでも新規レーンを追加できること
     */
    public function test_can_create_new_lane_without_lead_staff(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('timetables.lanes.store', $this->timetable), [
                'name' => '音響・PA班',
                'color' => 'indigo',
                'icon' => '🎤',
                'lead_staff_id' => null,
                'description' => 'ステージ音響設備の設置・マイク管理',
                'display_order' => 110,
            ]);

        $response->assertRedirect(route('timetables.lanes.index', $this->timetable));
        $this->assertDatabaseHas('lanes', [
            'timetable_id' => $this->timetable->id,
            'name' => '音響・PA班',
            'lead_staff_id' => null,
            'color' => 'indigo',
        ]);
    }

    /**
     * 4. 既存レーンに主担当スタッフを後付け設定・更新できること
     */
    public function test_can_update_lane_and_assign_lead_staff(): void
    {
        $this->timetable->createDefaultLanes();
        $lane = $this->timetable->lanes()->where('name', 'カラーコーン')->firstOrFail();

        $staff = Staff::factory()->create([
            'name' => '佐藤 友秋',
            'affiliation' => '青年部',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->operatorUser)
            ->put(route('timetables.lanes.update', [$this->timetable, $lane]), [
                'name' => 'カラーコーン',
                'color' => 'amber',
                'icon' => '🔺',
                'lead_staff_id' => $staff->id,
                'description' => '規制機材の設置と安全確保',
                'display_order' => 10,
            ]);

        $response->assertRedirect(route('timetables.lanes.index', $this->timetable));

        $lane->refresh();
        $this->assertSame($staff->id, $lane->lead_staff_id);
        $this->assertTrue($lane->hasLead());
        $this->assertSame('佐藤 友秋', $lane->leadStaff->name);

        // Staff 側からのリレーションも確認
        $this->assertTrue($staff->ledLanes->contains($lane));
    }

    /**
     * 5. 主担当をクイック更新エンドポイントから更新・解除できること
     */
    public function test_can_update_lead_staff_via_quick_endpoint(): void
    {
        $this->timetable->createDefaultLanes();
        $lane = $this->timetable->lanes()->where('name', '衛生')->firstOrFail();

        $staff = Staff::factory()->create([
            'name' => '山田 太郎',
            'is_active' => true,
        ]);

        // 1. 割り当て
        $response = $this->actingAs($this->operatorUser)
            ->patch(route('timetables.lanes.update-lead', [$this->timetable, $lane]), [
                'lead_staff_id' => $staff->id,
            ]);

        $response->assertSessionHas('success');
        $lane->refresh();
        $this->assertSame($staff->id, $lane->lead_staff_id);

        // 2. 解除 (null)
        $response = $this->actingAs($this->operatorUser)
            ->patch(route('timetables.lanes.update-lead', [$this->timetable, $lane]), [
                'lead_staff_id' => null,
            ]);

        $response->assertSessionHas('success');
        $lane->refresh();
        $this->assertNull($lane->lead_staff_id);
    }

    /**
     * 6. タスクが紐づいているレーンは削除できずブロックされること
     */
    public function test_cannot_delete_lane_with_tasks(): void
    {
        $this->timetable->createDefaultLanes();
        $lane = $this->timetable->lanes()->where('name', 'カラーコーン')->firstOrFail();

        // タスクを作成
        TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $lane->id,
            'title' => '規制コーン本設置',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('timetables.lanes.destroy', [$this->timetable, $lane]));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('lanes', ['id' => $lane->id]);
    }

    /**
     * 7. タスクが0件のレーンは安全に削除できること
     */
    public function test_can_delete_lane_without_tasks(): void
    {
        $this->timetable->createDefaultLanes();
        $lane = $this->timetable->lanes()->where('name', 'ステカン')->firstOrFail();

        $response = $this->actingAs($this->adminUser)
            ->delete(route('timetables.lanes.destroy', [$this->timetable, $lane]));

        $response->assertRedirect(route('timetables.lanes.index', $this->timetable));
        $this->assertDatabaseMissing('lanes', ['id' => $lane->id]);
    }

    /**
     * 8. レーン単位でタスクを作成した際、主担当が自動アサインされること
     */
    public function test_task_creation_with_lane_and_auto_assign_lead(): void
    {
        $this->timetable->createDefaultLanes();
        $lane = $this->timetable->lanes()->where('name', '給水設備')->firstOrFail();

        $leadStaff = Staff::factory()->create([
            'name' => '宮永 健一',
            'is_active' => true,
        ]);
        $lane->update(['lead_staff_id' => $leadStaff->id]);

        $taskData = [
            'timetable_id' => $this->timetable->id,
            'lane_id' => $lane->id,
            'title' => '足踏み式給水設備 設置点検',
            'location' => '番所前',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'required_people_count' => 2,
            'is_at_least' => false,
            'is_critical' => false,
            'requires_review' => false,
            'status' => 'not_started',
            'auto_assign_lead' => '1',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('timetable-tasks.store'), $taskData);

        $response->assertRedirect(route('timetables.show', $this->timetable));

        $task = TimetableTask::where('title', '足踏み式給水設備 設置点検')->firstOrFail();
        $this->assertSame($lane->id, $task->lane_id);
        $this->assertSame('給水設備', $task->lane);

        // 主担当スタッフが班リーダーとして自動アサインされていること
        $assignment = TaskStaffAssignment::where('timetable_task_id', $task->id)
            ->where('staff_id', $leadStaff->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertSame('班リーダー', $assignment->role_description);
        $this->assertSame('present', $assignment->attendance_status);
    }

    /**
     * 9. デフォルトレーン生成時に主要班（コアレーン）フラグが正しく設定されること
     */
    public function test_default_ten_lanes_have_core_flags(): void
    {
        $this->timetable->createDefaultLanes();

        $coreLaneNames = ['カラーコーン', '給水設備', '衛生', '巡回', 'ステカン'];
        foreach ($coreLaneNames as $name) {
            $lane = $this->timetable->lanes()->where('name', $name)->firstOrFail();
            $this->assertTrue($lane->is_core, "Lane {$name} should be core");
        }

        $nonCoreLaneNames = ['キッズ村', 'ちょうちん', 'ござ市', '本陣', '道路清掃'];
        foreach ($nonCoreLaneNames as $name) {
            $lane = $this->timetable->lanes()->where('name', $name)->firstOrFail();
            $this->assertFalse($lane->is_core, "Lane {$name} should not be core");
        }
    }

    /**
     * 10. ドラッグ＆ドロップでレーン表示順を一括更新できること
     */
    public function test_can_reorder_lanes(): void
    {
        $this->timetable->createDefaultLanes();
        $lanes = $this->timetable->lanes()->ordered()->get();

        // 逆順の ID 配列を用意
        $reversedIds = $lanes->pluck('id')->reverse()->values()->all();

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('timetables.lanes.reorder', $this->timetable), [
                'lane_ids' => $reversedIds,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // DB 上で display_order が更新されていること
        $reorderedLanes = $this->timetable->lanes()->ordered()->get();
        $this->assertSame($reversedIds, $reorderedLanes->pluck('id')->all());
        $this->assertSame(10, $reorderedLanes->first()->display_order);
        $this->assertSame(100, $reorderedLanes->last()->display_order);
    }

    /**
     * 12. タスクが存在するレーンを別レーンにタスク移管して安全に削除できること
     */
    public function test_can_delete_lane_with_task_migration(): void
    {
        $this->timetable->createDefaultLanes();
        $sourceLane = $this->timetable->lanes()->where('name', 'ステカン')->firstOrFail();
        $targetLane = $this->timetable->lanes()->where('name', '道路清掃')->firstOrFail();

        // ステカンにタスクを作成
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $sourceLane->id,
            'title' => 'ステカン撤去作業',
        ]);

        $this->assertSame($sourceLane->id, $task->lane_id);

        // 移管先を指定して削除
        $response = $this->actingAs($this->adminUser)
            ->delete(route('timetables.lanes.destroy', [$this->timetable, $sourceLane]), [
                'migrate_to_lane_id' => $targetLane->id,
            ]);

        $response->assertRedirect(route('timetables.lanes.index', $this->timetable));
        $response->assertSessionHas('success');

        // ソースレーンは削除されていること
        $this->assertDatabaseMissing('lanes', ['id' => $sourceLane->id]);

        // タスクはターゲットレーンに引き継がれていること
        $task->refresh();
        $this->assertSame($targetLane->id, $task->lane_id);
    }

    /**
     * 13. タスクが存在するレーンをタスクごと一括強制削除できること
     */
    public function test_can_delete_lane_with_cascade_tasks(): void
    {
        $this->timetable->createDefaultLanes();
        $lane = $this->timetable->lanes()->where('name', 'キッズ村')->firstOrFail();

        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'lane_id' => $lane->id,
            'title' => 'キッズコーナー風船配布',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('timetables.lanes.destroy', [$this->timetable, $lane]), [
                'cascade_delete_tasks' => '1',
            ]);

        $response->assertRedirect(route('timetables.lanes.index', $this->timetable));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('lanes', ['id' => $lane->id]);
        $this->assertDatabaseMissing('timetable_tasks', ['id' => $task->id]);
    }

    /**
     * 14. 運行計画が複数ある場合、/lanes で選択ハブ画面が表示されること
     */
    public function test_lanes_index_shows_timetable_selection_hub_when_multiple_timetables_exist(): void
    {
        $secondTimetable = Timetable::factory()->create([
            'name' => '本祭2日目',
            'target_date' => '2027-10-18',
        ]);
        $this->timetable->createDefaultLanes();
        $secondTimetable->createDefaultLanes();

        $response = $this->actingAs($this->adminUser)
            ->get(route('lanes.index'));

        $response->assertStatus(200);
        $response->assertSee('担当班（レーン）管理');
        $response->assertSee('班編成や現場リーダー設定を行うタイムテーブル（日程）を選択してください。');
        $response->assertDontSee('新規タイムテーブルを登録');
        $response->assertSee('運行日別 担当班編成一覧');
        $response->assertDontSee('全 2 日程');
        $response->assertSee('タイムテーブル');
        $response->assertSee('担当班一覧');
        $response->assertDontSee('マトリクス');
        $response->assertDontSee('担当班を管理');
        $response->assertDontSee('編成レーン例');
        $response->assertDontSee('現場リーダー設定状況');
        $response->assertDontSee('編成レーン数');
        $response->assertSee($this->timetable->name);
        $response->assertSee($secondTimetable->name);
        $response->assertSee(route('timetables.lanes.index', $this->timetable));
        $response->assertSee(route('timetables.lanes.index', $secondTimetable));
        $response->assertDontSee('タイムテーブル・タスク台帳');
    }

    /**
     * 15. 運行計画が1件のみの場合、/lanes からその日程へ自動リダイレクトされること
     */
    public function test_lanes_index_redirects_directly_when_only_one_timetable_exists(): void
    {
        // setUpで $this->timetable の1件のみ作成されている
        $response = $this->actingAs($this->adminUser)
            ->get(route('lanes.index'));

        $response->assertRedirect(route('timetables.lanes.index', $this->timetable));
    }

    /**
     * 16. 運行計画が0件の場合、/lanes から運行日管理へ案内付きでリダイレクトされること
     */
    public function test_lanes_index_redirects_to_timetables_index_when_no_timetables_exist(): void
    {
        $this->timetable->delete();

        $response = $this->actingAs($this->adminUser)
            ->get(route('lanes.index'));

        $response->assertRedirect(route('timetables.index'));
        $response->assertSessionHas('info', '担当班を編成する前に、運行日を登録してください。');
    }

    /**
     * 17. 個別日程のレーン一覧画面で運行計画クイック切り替えタブが表示されること
     */
    public function test_lane_index_does_not_display_timetable_quick_switch_tabs(): void
    {
        $secondTimetable = Timetable::factory()->create([
            'name' => '本祭2日目',
            'target_date' => '2027-10-18',
        ]);
        $this->timetable->createDefaultLanes();
        $secondTimetable->createDefaultLanes();

        $response = $this->actingAs($this->adminUser)
            ->get(route('timetables.lanes.index', $this->timetable));

        $response->assertStatus(200);
        $response->assertDontSee('タイムテーブル:');
        $response->assertDontSee('日程選択へ戻る');
    }
}
