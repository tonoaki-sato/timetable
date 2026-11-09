<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Lane;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        Timetable::factory()->create(['target_date' => '2026-10-11', 'name' => '1日目']);
        Timetable::factory()->create(['target_date' => '2026-10-12', 'name' => '2日目']);
    }

    /**
     * 資材管理一覧画面が表示され、3区分（committee / rental / personal）でフィルタできること
     */
    public function test_index_displays_and_filters_equipments(): void
    {
        $this->actingAs($this->operator);

        Equipment::factory()->committee()->create(['name' => '立て看板']);
        Equipment::factory()->rental()->create(['name' => 'パイプテント']);
        Equipment::factory()->personal()->create(['name' => '私物折りたたみ台車']);

        $response = $this->get(route('equipments.index'));
        $response->assertStatus(200);
        $response->assertSee('資材管理');
        $response->assertSee('立て看板');
        $response->assertSee('パイプテント');
        $response->assertSee('私物折りたたみ台車');

        // 委員会管理のみフィルタ
        $committeeResponse = $this->get(route('equipments.index', ['type' => 'committee']));
        $committeeResponse->assertSee('立て看板');
        $committeeResponse->assertDontSee('パイプテント');
        $committeeResponse->assertDontSee('私物折りたたみ台車');

        // レンタルのみフィルタ
        $rentalResponse = $this->get(route('equipments.index', ['type' => 'rental']));
        $rentalResponse->assertSee('パイプテント');
        $rentalResponse->assertDontSee('立て看板');
        $rentalResponse->assertDontSee('私物折りたたみ台車');

        // 私物のみフィルタ
        $personalResponse = $this->get(route('equipments.index', ['type' => 'personal']));
        $personalResponse->assertSee('私物折りたたみ台車');
        $personalResponse->assertDontSee('立て看板');
        $personalResponse->assertDontSee('パイプテント');
    }

    /**
     * キーワード検索で資材が絞り込めること
     */
    public function test_index_keyword_search(): void
    {
        $this->actingAs($this->operator);

        Equipment::factory()->committee()->create(['name' => '特製カラーコーン', 'storage_location' => '番所地下倉庫']);
        Equipment::factory()->rental()->create(['name' => '大型パイプテント', 'storage_location' => null]);

        $searchRes = $this->get(route('equipments.index', ['keyword' => '特製']));
        $searchRes->assertSee('特製カラーコーン');
        $searchRes->assertDontSee('大型パイプテント');
    }

    /**
     * 委員会管理機材および概数管理フラグ付き資材の新規登録ができること
     */
    public function test_store_creates_committee_equipment_with_approximate_quantity(): void
    {
        $this->actingAs($this->operator);
        $lane = Lane::factory()->create(['name' => 'カラーコーン班']);

        $response = $this->post(route('equipments.store'), [
            'name' => 'カラーコーン（バー付き）',
            'type' => 'committee',
            'quantity' => 60,
            'is_approximate_quantity' => '1',
            'unit' => '本',
            'storage_location' => '番所倉庫',
            'lane_id' => $lane->id,
            'description' => '路上規制用コーン',
        ]);

        $equipment = Equipment::where('name', 'カラーコーン（バー付き）')->first();
        $this->assertNotNull($equipment);
        $response->assertRedirect(route('equipments.show', $equipment));

        $this->assertTrue($equipment->is_approximate_quantity);
        $this->assertSame('約 60 本', $equipment->formatted_quantity);

        $this->assertDatabaseHas('equipments', [
            'name' => 'カラーコーン（バー付き）',
            'type' => 'committee',
            'quantity' => 60,
            'is_approximate_quantity' => 1,
            'storage_location' => '番所倉庫',
        ]);
    }

    /**
     * イベント会社レンタル機材および私物機材の新規登録ができること
     */
    public function test_store_creates_rental_and_personal_equipment(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('equipments.store'), [
            'name' => 'パイプテント 2K×3K',
            'type' => 'rental',
            'quantity' => 10,
            'unit' => '張',
        ]);

        $equipment = Equipment::where('name', 'パイプテント 2K×3K')->first();
        $this->assertNotNull($equipment);
        $response->assertRedirect(route('equipments.show', $equipment));
        $this->assertNull($equipment->storage_location);
        $this->assertSame('10 張', $equipment->formatted_quantity);

        // 私物（個人持ち込み）
        $personalRes = $this->post(route('equipments.store'), [
            'name' => 'アルミ脚立（私物）',
            'type' => 'personal',
            'quantity' => 2,
            'unit' => '台',
            'storage_location' => '自宅',
        ]);

        $personalEq = Equipment::where('name', 'アルミ脚立（私物）')->first();
        $this->assertNotNull($personalEq);
        $personalRes->assertRedirect(route('equipments.show', $personalEq));
        $this->assertTrue($personalEq->isPersonal());
        $this->assertSame('自宅', $personalEq->storage_location);
    }

    /**
     * 資材の更新と削除ができること
     */
    public function test_update_and_destroy_equipment(): void
    {
        $this->actingAs($this->operator);

        $equipment = Equipment::factory()->committee()->create([
            'name' => '旧・機材名',
            'quantity' => 10,
        ]);

        // 更新
        $updateRes = $this->put(route('equipments.update', $equipment), [
            'name' => '新・機材名',
            'type' => 'committee',
            'quantity' => 15,
            'is_approximate_quantity' => '1',
            'unit' => '台',
            'storage_location' => '番所地下倉庫',
        ]);

        $updateRes->assertRedirect(route('equipments.show', $equipment));
        $this->assertDatabaseHas('equipments', [
            'id' => $equipment->id,
            'name' => '新・機材名',
            'quantity' => 15,
            'is_approximate_quantity' => 1,
        ]);

        // 削除
        $destroyRes = $this->delete(route('equipments.destroy', $equipment));
        $destroyRes->assertRedirect(route('equipments.index'));
        $this->assertDatabaseMissing('equipments', ['id' => $equipment->id]);
    }

    /**
     * 資材を削除した際、紐づく搬出入計画ステップがカスケード削除されること
     */
    public function test_equipment_deletion_cascades_to_plans(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::first();

        $equipment = Equipment::factory()->committee()->create(['name' => 'カスケード削除検証用資材']);
        $plan = $equipment->plans()->create([
            'timetable_id' => $timetable->id,
            'title' => 'テスト搬出ステップ',
            'day_phase' => 'pre_day',
            'action_type' => 'storage_out',
        ]);

        $this->assertDatabaseHas('equipments', ['id' => $equipment->id]);
        $this->assertDatabaseHas('equipment_logistics_plans', ['id' => $plan->id]);

        $response = $this->delete(route('equipments.destroy', $equipment));
        $response->assertRedirect(route('equipments.index'));

        $this->assertDatabaseMissing('equipments', ['id' => $equipment->id]);
        $this->assertDatabaseMissing('equipment_logistics_plans', ['id' => $plan->id]);
    }

    /**
     * 持出ルール付きで新規資材を登録し、持出ルールで台帳を絞り込みできること
     */
    public function test_store_and_filter_by_checkout_rule(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('equipments.store'), [
            'name' => '折りたたみ大型台車',
            'checkout_rule' => 'permission_required',
            'type' => 'committee',
            'quantity' => 5,
            'unit' => '台',
            'storage_location' => '番所地下倉庫',
            'storage_detail' => '地下倉庫入口右手・台車ラック',
        ]);

        $equipment = Equipment::where('name', '折りたたみ大型台車')->first();
        $this->assertNotNull($equipment);
        $response->assertRedirect(route('equipments.show', $equipment));
        $this->assertSame('permission_required', $equipment->checkout_rule);
        $this->assertSame('地下倉庫入口右手・台車ラック', $equipment->storage_detail);

        Equipment::factory()->create([
            'name' => '自由持出バケツ',
            'checkout_rule' => 'free',
        ]);

        $permResponse = $this->get(route('equipments.index', ['checkout_rule' => 'permission_required']));
        $permResponse->assertStatus(200);
        $permResponse->assertSee('折りたたみ大型台車');
        $permResponse->assertDontSee('自由持出バケツ');

        // 一覧画面に「持ち出しルール」カラムおよびバッジが正しく表示されること
        $indexResponse = $this->get(route('equipments.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('持ち出しルール');
        $indexResponse->assertSee('本陣許可制');
        $indexResponse->assertSee('自由持出可');
    }

    /**
     * 一般スタッフ（権限なし）は資材管理の登録・編集・削除ができないこと
     */
    public function test_unauthorized_user_cannot_manage_equipment(): void
    {
        $regularUser = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);
        $this->actingAs($regularUser);

        $response = $this->post(route('equipments.store'), [
            'name' => '不正登録',
            'type' => 'committee',
            'quantity' => 1,
            'unit' => '個',
        ]);

        $response->assertForbidden();
    }

    /**
     * レンタル機材（type=rental）登録・更新時に、フォーム初期値の保管場所が送信されてもnullに正規化されること
     */
    public function test_rental_equipment_clears_default_storage_location_and_detail(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('equipments.store'), [
            'name' => '足踏み式給水設備',
            'type' => 'rental',
            'checkout_rule' => 'leader_only',
            'quantity' => 5,
            'unit' => '台',
            'storage_location' => '番所地下倉庫',
            'storage_detail' => '不要な詳細',
        ]);

        $equipment = Equipment::where('name', '足踏み式給水設備')->first();
        $this->assertNotNull($equipment);
        $response->assertRedirect(route('equipments.show', $equipment));
        $this->assertNull($equipment->storage_location);
        $this->assertNull($equipment->storage_detail);

        // 委員会管理からレンタルへ更新した場合もクリアされること
        $committeeEq = Equipment::factory()->committee()->create([
            'storage_location' => '番所地下倉庫',
            'storage_detail' => '地下1階',
        ]);

        $this->put(route('equipments.update', $committeeEq), [
            'name' => $committeeEq->name,
            'type' => 'rental',
            'quantity' => 3,
            'unit' => '台',
            'storage_location' => '番所地下倉庫',
            'storage_detail' => '地下1階',
        ])->assertRedirect(route('equipments.show', $committeeEq));

        $committeeEq->refresh();
        $this->assertNull($committeeEq->storage_location);
        $this->assertNull($committeeEq->storage_detail);
    }

    /**
     * 初期データが一切ない状態でも資材一覧・新規作成・詳細画面がエラーなく表示されること
     */
    public function test_empty_initial_data_renders_equipment_views_cleanly(): void
    {
        $this->actingAs($this->operator);
        Timetable::query()->delete();

        $this->get(route('equipments.index'))
            ->assertStatus(200)
            ->assertSee('備品がまだ登録されていません');

        $this->get(route('equipments.create'))
            ->assertStatus(200)
            ->assertSee('新規資材の登録')
            ->assertSee('資材管理に戻る')
            ->assertSee('<select id="type" name="type"', false);

        $this->post(route('equipments.store'), [
            'name' => '初回登録資材',
            'type' => 'committee',
            'quantity' => 1,
            'unit' => '個',
        ])->assertRedirect();

        $firstEquipment = Equipment::firstOrFail();

        $this->get(route('equipments.edit', $firstEquipment))
            ->assertStatus(200)
            ->assertSee('<select id="type" name="type"', false);

        $this->get(route('equipments.show', $firstEquipment))
            ->assertStatus(200)
            ->assertSee('初回登録資材')
            ->assertSee('資材基本台帳情報')
            ->assertSee('搬出入計画・現場配備状況')
            ->assertSee(route('logistics.equipments.show', $firstEquipment));

        // 計画画面への導線および計画画面の初期表示検証
        $this->get(route('logistics.equipments.show', $firstEquipment))
            ->assertStatus(200)
            ->assertSee('初回登録資材')
            ->assertSee('搬出入パターンがまだ登録されていません')
            ->assertSee(route('equipments.show', $firstEquipment));
    }

    /**
     * タイムテーブルスコープでの資機材管理一覧・詳細画面（班担当管理風導線）が表示されること
     */
    public function test_timetable_scoped_equipment_routes_and_navigation(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::first();
        $lane = Lane::factory()->create(['timetable_id' => $timetable->id, 'name' => '放送設営班']);
        $equipment = Equipment::factory()->committee()->create([
            'name' => 'ワイヤレスアンプ',
            'lane_id' => $lane->id,
            'quantity' => 2,
            'unit' => '台',
        ]);

        // タイムテーブルスコープの一覧表示
        $indexRes = $this->get(route('timetables.equipments.index', $timetable));
        $indexRes->assertStatus(200);
        $indexRes->assertSee($timetable->name);
        $indexRes->assertSee('ワイヤレスアンプ');
        $indexRes->assertSee('放送設営班');
        $indexRes->assertSee('対象運行日:');

        // タイムテーブルスコープの詳細表示 (2:1 カラム)
        $showRes = $this->get(route('timetables.equipments.show', [$timetable, $equipment]));
        $showRes->assertStatus(200);
        $showRes->assertSee('ワイヤレスアンプ');
        $showRes->assertSee('資材基本台帳情報');
        $showRes->assertSee('主担当班の体制');
        $showRes->assertSee('放送設営班');
        $showRes->assertSee('クイック操作');

        // タイムテーブルスコープの作成画面表示
        $createRes = $this->get(route('timetables.equipments.create', $timetable));
        $createRes->assertStatus(200);
        $createRes->assertSee('新規資材の登録');
        $createRes->assertSee('放送設営班');

        // タイムテーブルスコープの編集画面表示
        $editRes = $this->get(route('timetables.equipments.edit', [$timetable, $equipment]));
        $editRes->assertStatus(200);
        $editRes->assertSee('ワイヤレスアンプ の編集');
    }

    /**
     * ドラッグ＆ドロップAPIで備品の表示順（display_order）が一括更新できること
     */
    public function test_reorder_updates_equipment_display_orders(): void
    {
        $this->actingAs($this->operator);

        $eq1 = Equipment::factory()->create(['name' => '備品A', 'display_order' => 10]);
        $eq2 = Equipment::factory()->create(['name' => '備品B', 'display_order' => 20]);
        $eq3 = Equipment::factory()->create(['name' => '備品C', 'display_order' => 30]);

        $response = $this->postJson(route('equipments.reorder'), [
            'equipment_ids' => [$eq3->id, $eq1->id, $eq2->id],
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'message' => '備品の表示順を更新しました。',
        ]);

        $this->assertSame(10, $eq3->fresh()->display_order);
        $this->assertSame(20, $eq1->fresh()->display_order);
        $this->assertSame(30, $eq2->fresh()->display_order);
    }

    /**
     * 一般スタッフは備品の表示順を変更できないこと
     */
    public function test_general_staff_cannot_reorder_equipments(): void
    {
        $regularUser = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);
        $this->actingAs($regularUser);

        $eq1 = Equipment::factory()->create(['name' => '備品A', 'display_order' => 10]);
        $eq2 = Equipment::factory()->create(['name' => '備品B', 'display_order' => 20]);

        $response = $this->postJson(route('equipments.reorder'), [
            'equipment_ids' => [$eq2->id, $eq1->id],
        ]);

        $response->assertForbidden();
    }
}
