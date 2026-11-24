<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
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
     * 1. タイムテーブル作成時、初期状態では担当班編成は自動生成されず0件であること
     */
    public function test_new_timetable_has_zero_lanes_initially(): void
    {
        $this->assertCount(0, $this->timetable->timetableLanes()->get());
    }

    /**
     * テスト用のサンプル担当班マスター＋日別編成を一括生成するヘルパー
     */
    private function createSampleLanes(Timetable $timetable): void
    {
        $sampleLanes = [
            ['name' => 'カラーコーン', 'icon' => '🔺', 'color' => 'amber', 'description' => '路上規制バー・コーン搬入・本設置・撤去', 'display_order' => 10],
            ['name' => '給水設備', 'icon' => '🚰', 'color' => 'sky', 'description' => '給水タンク・仮設手洗い場・配管ホース設置', 'display_order' => 20],
            ['name' => '衛生', 'icon' => '🚻', 'color' => 'emerald', 'description' => '仮設トイレ・ごみステーション設置・回収袋交換', 'display_order' => 30],
            ['name' => '巡回', 'icon' => '🚨', 'color' => 'purple', 'description' => '会場警備・迷子対応・防犯パトロール・動線監視', 'display_order' => 40],
            ['name' => 'キッズ村', 'icon' => '🎈', 'color' => 'pink', 'description' => 'こども体験ブース設営・運営・安全管理', 'display_order' => 50],
            ['name' => 'ちょうちん', 'icon' => '🏮', 'color' => 'red', 'description' => '提灯取付・配線点検・点灯確認・翌日回収', 'display_order' => 60],
            ['name' => 'ござ市', 'icon' => '🧺', 'color' => 'orange', 'description' => 'ござ敷設・出店区画案内・境界管理・漆原さん連携', 'display_order' => 70],
            ['name' => '本陣', 'icon' => '🏛️', 'color' => 'indigo', 'description' => '本部運営・来賓応接・放送アナウンス・総合案内', 'display_order' => 80],
            ['name' => '道路清掃', 'icon' => '🧹', 'color' => 'teal', 'description' => '開催前清掃・巡回清掃・規制解除前の最終路面清掃', 'display_order' => 90],
            ['name' => 'ステカン', 'icon' => '🪧', 'color' => 'yellow', 'description' => '捨て看板（案内標識）の電柱・フェンス設置および完全回収', 'display_order' => 100],
        ];

        foreach ($sampleLanes as $laneData) {
            $master = Lane::firstOrCreate(
                ['name' => $laneData['name']],
                [
                    'icon' => $laneData['icon'],
                    'color' => $laneData['color'],
                    'description' => $laneData['description'],
                    'display_order' => $laneData['display_order'],
                    'is_active' => true,
                ]
            );

            TimetableLane::factory()->create([
                'timetable_id' => $timetable->id,
                'lane_id' => $master->id,
                'display_order' => $laneData['display_order'],
                'lead_staff_id' => null,
            ]);
        }
    }

    /**
     * 2. 日別担当班編成一覧画面の表示とモーダルDOM・Viteスクリプト検証
     */
    public function test_admin_and_operator_can_view_lanes_index(): void
    {
        $this->createSampleLanes($this->timetable);

        $response = $this->actingAs($this->adminUser)
            ->get(route('timetables.lanes.index', $this->timetable));

        $response->assertStatus(200);
        $response->assertSee($this->timetable->name.' の編成済み担当班');
        $response->assertSee('運行日程管理');
        $response->assertSee(route('timetables.index'));
        $response->assertSee(route('timetables.show', $this->timetable));
        $response->assertSee('担当班編成');
        $response->assertSee(route('lanes.index'));
        $response->assertSee('担当班マスター管理');
        $response->assertSee('担当班一覧PDF');
        $response->assertDontSee('他日程からコピー');
        $response->assertDontSee('copy-lane-modal');
        $response->assertDontSee('日程選択へ戻る');
        $response->assertSee('カラーコーン');
        $response->assertSee('ステカン');
        $response->assertSee('timetable-lanes-sortable-body');
        $response->assertSee('⋮⋮');
        $response->assertSee('班名');
        $response->assertSee('バッジ');
        $response->assertSee('班長名');
        $response->assertSee('固定人数・メモ');
        $response->assertSee('操作');
        $response->assertSee('id="modal-tlane-detail"', false);
        $response->assertSee('id="modal-tlane-add"', false);
        $response->assertSee('id="modal-tlane-edit"', false);
        $response->assertSee('id="delete-migration-modal"', false);
        $response->assertSee('id="btn-open-tlane-add"', false);
        $response->assertSee(route('timetables.tasks.index', $this->timetable));
        $response->assertSee('タスク計画へ進む');

        $html = $response->getContent();
        $this->assertTrue(
            str_contains($html, 'timetable-lanes.js') || str_contains($html, 'timetable-lanes-'),
            'timetable-lanes script asset should be loaded'
        );
    }

    /**
     * 3. 主担当が未設定のままでもマスターから日別編成を追加できること
     */
    public function test_can_create_new_lane_without_lead_staff(): void
    {
        $master = Lane::factory()->create([
            'name' => '音響・PA班',
            'color' => 'indigo',
            'icon' => '🎤',
            'description' => 'ステージ音響設備の設置・マイク管理',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('timetables.lanes.store', $this->timetable), [
                'lane_id' => $master->id,
                'lead_staff_id' => null,
                'notes' => '朝7時にステージ裏集合',
            ]);

        $response->assertRedirect(route('timetables.lanes.index', $this->timetable));
        $this->assertDatabaseHas('timetable_lanes', [
            'timetable_id' => $this->timetable->id,
            'lane_id' => $master->id,
            'lead_staff_id' => null,
            'notes' => '朝7時にステージ裏集合',
        ]);
    }

    /**
     * 3b. 新規担当班編成時に班長および固定スタッフを指定して同時に登録できること
     */
    public function test_can_create_lane_with_lead_and_fixed_staff(): void
    {
        $master = Lane::factory()->create([
            'name' => '警備・巡回特務班',
            'color' => 'purple',
            'icon' => '🚨',
            'description' => '会場外周の警備と迷子対応',
        ]);
        $lead = Staff::factory()->create(['name' => '班長リーダー', 'is_active' => true]);
        $fixedStaff1 = Staff::factory()->create(['name' => '固定スタッフA', 'is_active' => true]);
        $fixedStaff2 = Staff::factory()->create(['name' => '固定スタッフB', 'is_active' => true]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('timetables.lanes.store', $this->timetable), [
                'lane_id' => $master->id,
                'lead_staff_id' => $lead->id,
                'fixed_staff_ids' => [$fixedStaff1->id, $fixedStaff2->id],
            ]);

        $response->assertRedirect(route('timetables.lanes.index', $this->timetable));

        $timetableLane = TimetableLane::where('timetable_id', $this->timetable->id)
            ->where('lane_id', $master->id)
            ->firstOrFail();
        $this->assertSame($lead->id, (int) $timetableLane->lead_staff_id);

        $this->assertDatabaseHas('timetable_lane_fixed_staff', [
            'timetable_lane_id' => $timetableLane->id,
            'staff_id' => $fixedStaff1->id,
        ]);
        $this->assertDatabaseHas('timetable_lane_fixed_staff', [
            'timetable_lane_id' => $timetableLane->id,
            'staff_id' => $fixedStaff2->id,
        ]);
    }

    /**
     * 3c. マスターから複数担当班の一括追加ができ、同一運行日への重複登録は拒否されること
     */
    public function test_can_bulk_store_from_masters_and_rejects_duplicate_assignment_in_same_timetable(): void
    {
        $master1 = Lane::factory()->create(['name' => '救護待機班', 'display_order' => 10]);
        $master2 = Lane::factory()->create(['name' => '広報記録班', 'display_order' => 20]);

        $bulkRes = $this->actingAs($this->adminUser)
            ->post(route('timetables.lanes.bulk-store', $this->timetable), [
                'lane_ids' => [$master1->id, $master2->id],
            ]);

        $bulkRes->assertRedirect(route('timetables.lanes.index', $this->timetable));
        $this->assertDatabaseHas('timetable_lanes', [
            'timetable_id' => $this->timetable->id,
            'lane_id' => $master1->id,
        ]);
        $this->assertDatabaseHas('timetable_lanes', [
            'timetable_id' => $this->timetable->id,
            'lane_id' => $master2->id,
        ]);

        // 同じ運行日へ同じマスターを単一追加しようとするとバリデーションエラー
        $dupRes = $this->actingAs($this->adminUser)
            ->postJson(route('timetables.lanes.store', $this->timetable), [
                'lane_id' => $master1->id,
            ]);
        $dupRes->assertStatus(422)->assertJsonValidationErrors(['lane_id']);
    }

    /**
     * 4. 既存の日別編成に主担当スタッフを後付け設定・更新できること
     */
    public function test_can_update_lane_and_assign_lead_staff(): void
    {
        $this->createSampleLanes($this->timetable);
        $lane = $this->timetable->timetableLanes()
            ->whereHas('lane', fn ($q) => $q->where('name', 'カラーコーン'))
            ->firstOrFail();

        $staff = Staff::factory()->create([
            'name' => '佐藤 友秋',
            'affiliation' => '青年部',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->operatorUser)
            ->put(route('timetables.lanes.update', [$this->timetable, $lane]), [
                'lead_staff_id' => $staff->id,
                'notes' => '8時前に本陣前でコーン積込',
            ]);

        $response->assertRedirect(route('timetables.lanes.index', $this->timetable));

        $lane->refresh();
        $this->assertSame($staff->id, $lane->lead_staff_id);
        $this->assertSame('8時前に本陣前でコーン積込', $lane->notes);
        $this->assertTrue($lane->hasLead());
        $this->assertSame('佐藤 友秋', $lane->leadStaff->name);

        // Staff 側からのリレーションも確認
        $this->assertTrue($staff->ledTimetableLanes->contains($lane));
    }

    /**
     * 5. 班長を更新から設定・解除でき、一覧画面に班長名が表示されること
     */
    public function test_can_update_lead_staff(): void
    {
        $this->createSampleLanes($this->timetable);
        $lane = $this->timetable->timetableLanes()
            ->whereHas('lane', fn ($q) => $q->where('name', '衛生'))
            ->firstOrFail();

        $staff = Staff::factory()->create([
            'name' => '山田 太郎',
            'is_active' => true,
        ]);

        // 1. 割り当て
        $response = $this->actingAs($this->operatorUser)
            ->put(route('timetables.lanes.update', [$this->timetable, $lane]), [
                'lead_staff_id' => $staff->id,
            ]);

        $response->assertSessionHas('success');
        $lane->refresh();
        $this->assertSame($staff->id, $lane->lead_staff_id);
        $this->assertTrue($lane->fixedStaff->contains($staff));

        // 編成一覧のHTMLでも班長名「👑」「山田 太郎」が表示されていること
        $viewResponse = $this->actingAs($this->operatorUser)
            ->get(route('timetables.lanes.index', $this->timetable));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('👑');
        $viewResponse->assertSee('山田 太郎');

        // 2. 解除 (null)
        $response = $this->actingAs($this->operatorUser)
            ->put(route('timetables.lanes.update', [$this->timetable, $lane]), [
                'lead_staff_id' => null,
            ]);

        $response->assertSessionHas('success');
        $lane->refresh();
        $this->assertNull($lane->lead_staff_id);

        // 編成一覧で未設定と表示されること
        $viewResponse = $this->actingAs($this->operatorUser)
            ->get(route('timetables.lanes.index', $this->timetable));
        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('未設定');
    }

    /**
     * 6. タスクが紐づいている編成は戦略未指定だと削除できずブロックされること
     */
    public function test_cannot_delete_lane_with_tasks(): void
    {
        $this->createSampleLanes($this->timetable);
        $lane = $this->timetable->timetableLanes()
            ->whereHas('lane', fn ($q) => $q->where('name', 'カラーコーン'))
            ->firstOrFail();

        // タスクを作成
        TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $lane->id,
            'title' => '規制コーン本設置',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('timetables.lanes.destroy', [$this->timetable, $lane]));

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('timetable_lanes', ['id' => $lane->id]);
    }

    /**
     * 7. タスクが0件の編成は安全に解除でき、親マスター自体は削除されないこと
     */
    public function test_can_delete_lane_without_tasks(): void
    {
        $this->createSampleLanes($this->timetable);
        $lane = $this->timetable->timetableLanes()
            ->whereHas('lane', fn ($q) => $q->where('name', 'ステカン'))
            ->firstOrFail();
        $masterLaneId = $lane->lane_id;

        $response = $this->actingAs($this->adminUser)
            ->delete(route('timetables.lanes.destroy', [$this->timetable, $lane]));

        $response->assertRedirect(route('timetables.lanes.index', $this->timetable));
        $this->assertDatabaseMissing('timetable_lanes', ['id' => $lane->id]);
        $this->assertDatabaseHas('lanes', ['id' => $masterLaneId]);
    }

    /**
     * 8. 日別編成単位でタスクを作成した際、主担当が自動アサインされること
     */
    public function test_task_creation_with_lane_and_auto_assign_lead(): void
    {
        $this->createSampleLanes($this->timetable);
        $lane = $this->timetable->timetableLanes()
            ->whereHas('lane', fn ($q) => $q->where('name', '給水設備'))
            ->firstOrFail();

        $leadStaff = Staff::factory()->create([
            'name' => '宮永 健一',
            'is_active' => true,
        ]);
        $lane->update(['lead_staff_id' => $leadStaff->id]);

        $taskData = [
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $lane->id,
            'title' => '足踏み式給水設備 設置点検',
            'location' => '番所前',
            'start_time' => '10:00',
            'end_time' => '11:00',
            'required_people_count' => 2,
            'is_at_least' => false,
            'is_critical' => false,
            'status' => 'not_started',
            'auto_assign_lead' => '1',
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('timetable-tasks.store'), $taskData);

        $response->assertRedirect(route('timetables.tasks.index', $this->timetable));

        $task = TimetableTask::where('title', '足踏み式給水設備 設置点検')->firstOrFail();
        $this->assertSame($lane->id, $task->timetable_lane_id);
        $this->assertSame('給水設備', $task->presenter()->laneNameLabel());

        // 主担当スタッフが班リーダーとして自動アサインされていること
        $assignment = TaskStaffAssignment::where('timetable_task_id', $task->id)
            ->where('staff_id', $leadStaff->id)
            ->first();

        $this->assertNotNull($assignment);
        $this->assertSame('班長', $assignment->role_description);
        $this->assertSame('assigned', $assignment->attendance_status);
    }

    /**
     * 8b. 編成に主担当が決まっている場合、auto_assign_lead パラメータを省略してもデフォルトでタスクにアサインされること
     */
    public function test_task_creation_auto_assigns_lead_staff_by_default_when_param_omitted(): void
    {
        $this->createSampleLanes($this->timetable);
        $lane = $this->timetable->timetableLanes()
            ->whereHas('lane', fn ($q) => $q->where('name', 'カラーコーン'))
            ->firstOrFail();

        $leadStaff = Staff::factory()->create([
            'name' => 'デフォルト主担当 太郎',
            'is_active' => true,
        ]);
        $lane->update(['lead_staff_id' => $leadStaff->id]);

        // auto_assign_lead パラメータを省略して送信
        $response = $this->actingAs($this->adminUser)
            ->post(route('timetable-tasks.store'), [
                'timetable_id' => $this->timetable->id,
                'timetable_lane_id' => $lane->id,
                'title' => 'コーン設置タスク（デフォルトアサイン確認）',
                'start_time' => '08:00',
                'end_time' => '09:00',
                'required_people_count' => 3,
                'status' => 'not_started',
            ]);

        $response->assertRedirect(route('timetables.tasks.index', $this->timetable));

        $task = TimetableTask::where('title', 'コーン設置タスク（デフォルトアサイン確認）')->firstOrFail();

        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task->id,
            'staff_id' => $leadStaff->id,
            'role_description' => '班長',
            'attendance_status' => 'assigned',
        ]);
    }

    /**
     * 8c. 既存タスクがある編成に後から主担当を設定・変更した際、既存タスクにも主担当が自動アサイン（同期）されること
     */
    public function test_updating_lane_lead_staff_syncs_to_existing_tasks(): void
    {
        $this->createSampleLanes($this->timetable);
        $lane = $this->timetable->timetableLanes()
            ->whereHas('lane', fn ($q) => $q->where('name', '衛生'))
            ->firstOrFail();

        // 主担当未設定の状態でタスクを2件作成
        $task1 = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $lane->id,
            'title' => '衛生タスク1',
            'status' => 'not_started',
        ]);
        $task2 = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $lane->id,
            'title' => '衛生タスク2',
            'status' => 'not_started',
        ]);

        $firstLead = Staff::factory()->create(['name' => '初代リーダー', 'is_active' => true]);
        $secondLead = Staff::factory()->create(['name' => '二代目リーダー', 'is_active' => true]);

        // 1. 後付けで初代リーダーを設定 -> 既存2タスクに自動アサインされること
        $this->actingAs($this->operatorUser)
            ->put(route('timetables.lanes.update', [$this->timetable, $lane]), [
                'lead_staff_id' => $firstLead->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task1->id,
            'staff_id' => $firstLead->id,
            'role_description' => '班長',
            'attendance_status' => 'assigned',
        ]);
        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task2->id,
            'staff_id' => $firstLead->id,
            'role_description' => '班長',
            'attendance_status' => 'assigned',
        ]);

        // 2. 二代目リーダーに変更 -> 班長が二代目へ引き継がれること
        $this->actingAs($this->operatorUser)
            ->put(route('timetables.lanes.update', [$this->timetable, $lane]), [
                'lead_staff_id' => $secondLead->id,
            ])
            ->assertSessionHas('success');

        $this->assertDatabaseHas('task_staff_assignments', [
            'timetable_task_id' => $task1->id,
            'staff_id' => $secondLead->id,
            'role_description' => '班長',
        ]);
        $this->assertDatabaseMissing('task_staff_assignments', [
            'timetable_task_id' => $task1->id,
            'staff_id' => $firstLead->id,
        ]);
    }

    /**
     * 9. 10班編成が正しい名前・順で生成されること
     */
    public function test_default_ten_lanes_are_created_properly(): void
    {
        $this->createSampleLanes($this->timetable);

        $this->assertCount(10, $this->timetable->timetableLanes);

        $expectedNames = [
            'カラーコーン', '給水設備', '衛生', '巡回', 'キッズ村',
            'ちょうちん', 'ござ市', '本陣', '道路清掃', 'ステカン',
        ];

        foreach ($expectedNames as $name) {
            $lane = $this->timetable->timetableLanes()
                ->whereHas('lane', fn ($q) => $q->where('name', $name))
                ->first();
            $this->assertNotNull($lane, "TimetableLane {$name} should exist");
        }
    }

    /**
     * 10. ドラッグ＆ドロップで日別編成の表示順を一括更新できること
     */
    public function test_can_reorder_lanes(): void
    {
        $this->createSampleLanes($this->timetable);
        $lanes = $this->timetable->timetableLanes()->ordered()->get();

        // 逆順の ID 配列を用意
        $reversedIds = $lanes->pluck('id')->reverse()->values()->all();

        $response = $this->actingAs($this->adminUser)
            ->postJson(route('timetables.lanes.reorder', $this->timetable), [
                'timetable_lane_ids' => $reversedIds,
            ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // DB 上で display_order が更新されていること
        $reorderedLanes = $this->timetable->timetableLanes()->ordered()->get();
        $this->assertSame($reversedIds, $reorderedLanes->pluck('id')->all());
        $this->assertSame(10, $reorderedLanes->first()->display_order);
        $this->assertSame(100, $reorderedLanes->last()->display_order);
    }

    /**
     * 12. タスクが存在する編成を別の編成にタスク移管して安全に解除できること
     */
    public function test_can_delete_lane_with_task_migration(): void
    {
        $this->createSampleLanes($this->timetable);
        $sourceLane = $this->timetable->timetableLanes()
            ->whereHas('lane', fn ($q) => $q->where('name', 'ステカン'))
            ->firstOrFail();
        $targetLane = $this->timetable->timetableLanes()
            ->whereHas('lane', fn ($q) => $q->where('name', '道路清掃'))
            ->firstOrFail();

        // ステカンにタスクを作成
        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $sourceLane->id,
            'title' => 'ステカン撤去作業',
        ]);

        $this->assertSame($sourceLane->id, $task->timetable_lane_id);

        // 移管先を指定して解除
        $response = $this->actingAs($this->adminUser)
            ->delete(route('timetables.lanes.destroy', [$this->timetable, $sourceLane]), [
                'migrate_to_timetable_lane_id' => $targetLane->id,
            ]);

        $response->assertRedirect(route('timetables.lanes.index', $this->timetable));
        $response->assertSessionHas('success');

        // ソース編成は解除されていること
        $this->assertDatabaseMissing('timetable_lanes', ['id' => $sourceLane->id]);

        // タスクはターゲット編成に引き継がれていること
        $task->refresh();
        $this->assertSame($targetLane->id, $task->timetable_lane_id);
    }

    /**
     * 13. タスクが存在する編成をタスクごと一括強制削除できること
     */
    public function test_can_delete_lane_with_cascade_tasks(): void
    {
        $this->createSampleLanes($this->timetable);
        $lane = $this->timetable->timetableLanes()
            ->whereHas('lane', fn ($q) => $q->where('name', 'キッズ村'))
            ->firstOrFail();

        $task = TimetableTask::factory()->create([
            'timetable_id' => $this->timetable->id,
            'timetable_lane_id' => $lane->id,
            'title' => 'キッズコーナー風船配布',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('timetables.lanes.destroy', [$this->timetable, $lane]), [
                'cascade_delete_tasks' => '1',
            ]);

        $response->assertRedirect(route('timetables.lanes.index', $this->timetable));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('timetable_lanes', ['id' => $lane->id]);
        $this->assertDatabaseMissing('timetable_tasks', ['id' => $task->id]);
    }

    /**
     * 14. /timetable-lanes（timetable-lanes.index）がアクティブな運行日へリダイレクトされ、/lanes はマスター一覧を返すこと
     */
    public function test_timetable_lanes_route_redirects_and_lanes_renders_master_list(): void
    {
        $this->actingAs($this->adminUser)
            ->get('/timetable-lanes')
            ->assertRedirect(route('timetables.lanes.index', $this->timetable));

        $this->actingAs($this->adminUser)
            ->get('/lanes')
            ->assertOk()
            ->assertSee('担当班マスター');
    }

    /**
     * 17. 個別日程の編成一覧画面で運行計画クイック切り替えタブが表示されないこと
     */
    public function test_lane_index_does_not_display_timetable_quick_switch_tabs(): void
    {
        $secondTimetable = Timetable::factory()->create([
            'name' => '本祭2日目',
            'target_date' => '2027-10-18',
        ]);
        $this->createSampleLanes($this->timetable);
        $this->createSampleLanes($secondTimetable);

        $response = $this->actingAs($this->adminUser)
            ->get(route('timetables.lanes.index', $this->timetable));

        $response->assertStatus(200);
        $response->assertDontSee('タイムテーブル:');
        $response->assertDontSee('日程選択へ戻る');
    }

    /**
     * 18. 親タイムテーブルに属さない日別編成へのアクセスは404となること（スコープバインディング）
     */
    public function test_lane_scoped_binding_rejects_mismatched_timetable(): void
    {
        $secondTimetable = Timetable::factory()->create([
            'name' => '本祭2日目',
            'target_date' => '2027-10-18',
        ]);
        $master = Lane::factory()->create(['name' => '別日レーン']);
        $laneOnSecond = TimetableLane::factory()->create([
            'timetable_id' => $secondTimetable->id,
            'lane_id' => $master->id,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('timetables.lanes.update', [$this->timetable, $laneOnSecond]), [
                'notes' => '改ざんメモ',
            ]);

        $response->assertStatus(404);
    }

    /**
     * 19. 旧作成・詳細・編集の独立画面ルートが削除されており、一覧＋モーダル完結と非アクティブ拒否が機能すること
     */
    public function test_legacy_subpage_routes_are_removed_and_modal_controls_respect_permissions(): void
    {
        $this->assertFalse(Route::has('timetables.lanes.create'));
        $this->assertFalse(Route::has('timetables.lanes.show'));
        $this->assertFalse(Route::has('timetables.lanes.edit'));
        $this->assertFalse(Route::has('lanes.create'));
        $this->assertFalse(Route::has('lanes.show'));
        $this->assertFalse(Route::has('lanes.edit'));

        $this->createSampleLanes($this->timetable);

        // viewer には一覧表示と詳細閲覧ボタンが表示され、編集・解除・追加ボタンは非表示
        $viewerUser = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
        $viewerRes = $this->actingAs($viewerUser)
            ->get(route('timetables.lanes.index', $this->timetable));
        $viewerRes->assertStatus(200);
        $viewerRes->assertSee('btn-view-tlane', false);
        $viewerRes->assertDontSee('id="btn-open-tlane-add"', false);
        $viewerRes->assertDontSee('btn-edit-tlane', false);
        $viewerRes->assertDontSee('btn-delete-tlane', false);

        // 非アクティブユーザーは403
        $inactiveUser = User::factory()->create(['role' => 'admin', 'is_active' => false]);
        $this->actingAs($inactiveUser)
            ->get(route('timetables.lanes.index', $this->timetable))
            ->assertStatus(403);
    }

    /**
     * 23. /timetable-lanes（timetable-lanes.index）がアクティブまたは指定運行日の編成一覧へリダイレクトされること
     */
    public function test_lanes_resource_index_redirects_appropriately(): void
    {
        // 1. timetable_id パラメータ指定時
        $this->actingAs($this->operatorUser)
            ->get(route('timetable-lanes.index', ['timetable_id' => $this->timetable->id]))
            ->assertRedirect(route('timetables.lanes.index', $this->timetable));

        // 2. timetable_id パラメータなし（アクティブ運行日へリダイレクト）
        $this->actingAs($this->operatorUser)
            ->get(route('timetable-lanes.index'))
            ->assertRedirect(route('timetables.lanes.index', $this->timetable));

        // 3. 運行日程が0件の場合は timetables.index へリダイレクト
        Timetable::query()->delete();
        $this->actingAs($this->operatorUser)
            ->get(route('timetable-lanes.index'))
            ->assertRedirect(route('timetables.index'));
    }
}
