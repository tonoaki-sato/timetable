<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\Location;
use App\Models\LocationType;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentManagementTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    private LocationType $storageType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $this->storageType = LocationType::create([
            'name' => 'storage',
            'label' => '倉庫・保管庫',
            'color' => 'indigo',
            'display_order' => 1,
            'is_active' => true,
        ]);

        Timetable::factory()->create(['target_date' => '2026-10-11', 'name' => '1日目']);
        Timetable::factory()->create(['target_date' => '2026-10-12', 'name' => '2日目']);
    }

    /**
     * 資材管理一覧画面が表示され、3区分（committee / rental / personal）でフィルタでき、モーダルDOM・Vite JSが含まれること
     */
    public function test_index_displays_and_filters_equipments(): void
    {
        $this->actingAs($this->operator);

        $location = Location::factory()->create([
            'location_type_id' => $this->storageType->id,
            'name' => '番所地下倉庫',
        ]);

        Equipment::factory()->committee()->create([
            'name' => '立て看板',
            'storage_location_id' => $location->id,
        ]);
        Equipment::factory()->rental()->create(['name' => '貸出用発電機セット']);
        Equipment::factory()->personal()->create(['name' => '私物折りたたみ台車']);

        $response = $this->get(route('equipments.index'));
        $response->assertStatus(200);
        $response->assertSee('機材・備品一覧');
        $response->assertSee('立て看板');
        $response->assertSee('番所地下倉庫');
        $response->assertSee('貸出用発電機セット');
        $response->assertSee('イベント会社手配');
        $response->assertSee('私物折りたたみ台車');
        $response->assertSee('個人保管（持ち込み）');

        // 画面専用JS・モーダルDOM・拠点セレクトが存在すること
        $html = (string) $response->getContent();
        $this->assertMatchesRegularExpression('/equipments(?:-[a-zA-Z0-9_-]+)?\.js/', $html);
        $response->assertSee('id="modal-equipment-detail"', false);
        $response->assertSee('id="modal-equipment-form"', false);
        $response->assertSee('id="btn-open-create-equipment"', false);
        $response->assertSee('<select id="storage_location_id" name="storage_location_id"', false);

        // 委員会管理のみフィルタ
        $committeeResponse = $this->get(route('equipments.index', ['type' => 'committee']));
        $committeeResponse->assertSee('立て看板');
        $committeeResponse->assertDontSee('貸出用発電機セット');
        $committeeResponse->assertDontSee('私物折りたたみ台車');

        // レンタルのみフィルタ
        $rentalResponse = $this->get(route('equipments.index', ['type' => 'rental']));
        $rentalResponse->assertSee('貸出用発電機セット');
        $rentalResponse->assertDontSee('立て看板');
        $rentalResponse->assertDontSee('私物折りたたみ台車');

        // 私物のみフィルタ
        $personalResponse = $this->get(route('equipments.index', ['type' => 'personal']));
        $personalResponse->assertSee('私物折りたたみ台車');
        $personalResponse->assertDontSee('立て看板');
        $personalResponse->assertDontSee('貸出用発電機セット');
    }

    /**
     * キーワード検索で資材名および定常保管場所（拠点マスター名）で絞り込めること
     */
    public function test_index_keyword_search_includes_storage_location_name(): void
    {
        $this->actingAs($this->operator);

        $bansho = Location::factory()->create([
            'location_type_id' => $this->storageType->id,
            'name' => '番所地下倉庫',
        ]);
        $community = Location::factory()->create([
            'location_type_id' => $this->storageType->id,
            'name' => 'コミュニティルーム',
        ]);

        Equipment::factory()->committee()->create([
            'name' => '特製カラーコーン',
            'storage_location_id' => $bansho->id,
        ]);
        Equipment::factory()->committee()->create([
            'name' => '誘導看板セット',
            'storage_location_id' => $community->id,
        ]);
        Equipment::factory()->rental()->create(['name' => '大型パイプテント']);

        // 資材名検索
        $searchRes = $this->get(route('equipments.index', ['keyword' => '特製']));
        $searchRes->assertSee('特製カラーコーン');
        $searchRes->assertDontSee('大型パイプテント');

        // 拠点マスター名での検索
        $locationSearchRes = $this->get(route('equipments.index', ['keyword' => 'コミュニティ']));
        $locationSearchRes->assertSee('誘導看板セット');
        $locationSearchRes->assertDontSee('特製カラーコーン');
        $locationSearchRes->assertDontSee('大型パイプテント');
    }

    /**
     * 定常保管場所列（storage_location_id）による昇順・降順ソートでNULLが末尾配置されること
     */
    public function test_index_sorts_by_storage_location_id(): void
    {
        $this->actingAs($this->operator);

        $locA = Location::factory()->create([
            'location_type_id' => $this->storageType->id,
            'name' => 'AAA倉庫',
        ]);
        $locZ = Location::factory()->create([
            'location_type_id' => $this->storageType->id,
            'name' => 'ZZZ倉庫',
        ]);

        Equipment::factory()->committee()->create(['name' => '機材_ZZZ保管', 'storage_location_id' => $locZ->id, 'display_order' => 10]);
        Equipment::factory()->committee()->create(['name' => '機材_AAA保管', 'storage_location_id' => $locA->id, 'display_order' => 20]);
        Equipment::factory()->rental()->create(['name' => '機材_レンタル未指定', 'display_order' => 30]);

        $ascRes = $this->get(route('equipments.index', ['sort' => 'storage_location_id', 'direction' => 'asc']));
        $ascRes->assertStatus(200);
        $ascRes->assertSeeInOrder(['機材_AAA保管', '機材_ZZZ保管', '機材_レンタル未指定']);

        $descRes = $this->get(route('equipments.index', ['sort' => 'storage_location_id', 'direction' => 'desc']));
        $descRes->assertStatus(200);
        $descRes->assertSeeInOrder(['機材_ZZZ保管', '機材_AAA保管', '機材_レンタル未指定']);
    }

    /**
     * 委員会管理機材を拠点マスター指定・概数管理フラグ付きで新規登録できること
     */
    public function test_store_creates_committee_equipment_with_approximate_quantity(): void
    {
        $this->actingAs($this->operator);

        $location = Location::factory()->create([
            'location_type_id' => $this->storageType->id,
            'name' => '番所倉庫',
        ]);

        $response = $this->post(route('equipments.store'), [
            'name' => 'カラーコーン（バー付き）',
            'type' => 'committee',
            'quantity' => 60,
            'is_approximate_quantity' => '1',
            'unit' => '本',
            'storage_location_id' => $location->id,
            'description' => '路上規制用コーン',
        ]);

        $equipment = Equipment::where('name', 'カラーコーン（バー付き）')->first();
        $this->assertNotNull($equipment);
        $response->assertRedirect(route('equipments.index'));

        $this->assertTrue($equipment->is_approximate_quantity);
        $this->assertSame('約 60 本', $equipment->formatted_quantity);
        $this->assertSame($location->id, $equipment->storage_location_id);
        $this->assertSame('番所倉庫', $equipment->storage_location_name);
        $this->assertSame('番所倉庫', $equipment->resolved_storage_location_label);

        $this->assertDatabaseHas('equipments', [
            'name' => 'カラーコーン（バー付き）',
            'type' => 'committee',
            'quantity' => 60,
            'is_approximate_quantity' => 1,
            'storage_location_id' => $location->id,
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
        $response->assertRedirect(route('equipments.index'));
        $this->assertNull($equipment->storage_location_id);
        $this->assertSame('イベント会社手配', $equipment->resolved_storage_location_label);
        $this->assertSame('10 張', $equipment->formatted_quantity);

        // 私物（個人持ち込み・拠点未指定）
        $personalRes = $this->post(route('equipments.store'), [
            'name' => 'アルミ脚立（私物）',
            'type' => 'personal',
            'quantity' => 2,
            'unit' => '台',
            'storage_location_id' => '',
        ]);

        $personalEq = Equipment::where('name', 'アルミ脚立（私物）')->first();
        $this->assertNotNull($personalEq);
        $personalRes->assertRedirect(route('equipments.index'));
        $this->assertTrue($personalEq->isPersonal());
        $this->assertNull($personalEq->storage_location_id);
        $this->assertSame('個人保管（持ち込み）', $personalEq->resolved_storage_location_label);
    }

    /**
     * 存在しない拠点IDの指定がバリデーションエラーとなること
     */
    public function test_store_and_update_reject_nonexistent_storage_location_id(): void
    {
        $this->actingAs($this->operator);

        $this->post(route('equipments.store'), [
            'name' => '不正拠点機材',
            'type' => 'committee',
            'quantity' => 1,
            'unit' => '個',
            'storage_location_id' => 999999,
        ])->assertSessionHasErrors('storage_location_id');

        $equipment = Equipment::factory()->committee()->create();

        $this->put(route('equipments.update', $equipment), [
            'name' => $equipment->name,
            'type' => 'committee',
            'quantity' => 1,
            'unit' => '個',
            'storage_location_id' => 999999,
        ])->assertSessionHasErrors('storage_location_id');
    }

    /**
     * 資材の更新と削除ができること
     */
    public function test_update_and_destroy_equipment(): void
    {
        $this->actingAs($this->operator);

        $location = Location::factory()->create([
            'location_type_id' => $this->storageType->id,
            'name' => '番所地下倉庫',
        ]);

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
            'storage_location_id' => $location->id,
        ]);

        $updateRes->assertRedirect(route('equipments.index'));
        $this->assertDatabaseHas('equipments', [
            'id' => $equipment->id,
            'name' => '新・機材名',
            'quantity' => 15,
            'is_approximate_quantity' => 1,
            'storage_location_id' => $location->id,
        ]);

        // 削除
        $destroyRes = $this->delete(route('equipments.destroy', $equipment));
        $destroyRes->assertRedirect(route('equipments.index'));
        $this->assertDatabaseMissing('equipments', ['id' => $equipment->id]);
    }

    /**
     * 紐づく拠点を削除した際、機材の storage_location_id が null に設定されること（nullOnDelete）
     */
    public function test_deleting_location_sets_equipment_storage_location_id_to_null(): void
    {
        $this->actingAs($this->operator);

        $location = Location::factory()->create([
            'location_type_id' => $this->storageType->id,
            'name' => '削除対象倉庫',
        ]);

        $equipment = Equipment::factory()->committee()->create([
            'name' => '一時保管機材',
            'storage_location_id' => $location->id,
        ]);

        $this->assertSame($location->id, $equipment->storage_location_id);

        $location->delete();
        $equipment->refresh();

        $this->assertNull($equipment->storage_location_id);
        $this->assertSame('未指定', $equipment->resolved_storage_location_label);
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

        $location = Location::factory()->create([
            'location_type_id' => $this->storageType->id,
            'name' => '番所地下倉庫',
        ]);

        $response = $this->post(route('equipments.store'), [
            'name' => '折りたたみ大型台車',
            'checkout_rule' => 'permission_required',
            'type' => 'committee',
            'quantity' => 5,
            'unit' => '台',
            'storage_location_id' => $location->id,
        ]);

        $equipment = Equipment::where('name', '折りたたみ大型台車')->first();
        $this->assertNotNull($equipment);
        $response->assertRedirect(route('equipments.index'));
        $this->assertSame('permission_required', $equipment->checkout_rule);

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
     * レンタル機材（type=rental）登録・更新時に、保管場所IDが送信されてもnullに正規化されること
     */
    public function test_rental_equipment_clears_default_storage_location(): void
    {
        $this->actingAs($this->operator);

        $location = Location::factory()->create([
            'location_type_id' => $this->storageType->id,
            'name' => '番所地下倉庫',
        ]);

        $response = $this->post(route('equipments.store'), [
            'name' => '足踏み式給水設備',
            'type' => 'rental',
            'checkout_rule' => 'leader_only',
            'quantity' => 5,
            'unit' => '台',
            'storage_location_id' => $location->id,
        ]);

        $equipment = Equipment::where('name', '足踏み式給水設備')->first();
        $this->assertNotNull($equipment);
        $response->assertRedirect(route('equipments.index'));
        $this->assertNull($equipment->storage_location_id);

        // 委員会管理からレンタルへ更新した場合もクリアされること
        $committeeEq = Equipment::factory()->committee()->create([
            'storage_location_id' => $location->id,
        ]);

        $this->put(route('equipments.update', $committeeEq), [
            'name' => $committeeEq->name,
            'type' => 'rental',
            'quantity' => 3,
            'unit' => '台',
            'storage_location_id' => $location->id,
        ])->assertRedirect(route('equipments.index'));

        $committeeEq->refresh();
        $this->assertNull($committeeEq->storage_location_id);
    }

    /**
     * 初期データ（運行日程・資機材・拠点）が一切ない状態でも資材一覧がエラーなく表示・登録できること
     */
    public function test_empty_initial_data_renders_equipment_views_cleanly(): void
    {
        $this->actingAs($this->operator);
        Timetable::query()->delete();
        Location::query()->delete();

        $this->get(route('equipments.index'))
            ->assertStatus(200)
            ->assertSee('備品がまだ登録されていません')
            ->assertSee('拠点がまだ登録されていません')
            ->assertSee('<select id="equipment_type" name="type"', false)
            ->assertSee('<select id="storage_location_id" name="storage_location_id"', false);

        $this->post(route('equipments.store'), [
            'name' => '初回登録資材',
            'type' => 'committee',
            'quantity' => 1,
            'unit' => '個',
        ])->assertRedirect(route('equipments.index'));

        $this->get(route('equipments.index'))
            ->assertStatus(200)
            ->assertSee('初回登録資材')
            ->assertSee('未指定');
    }

    /**
     * 廃止された旧タイムテーブル配下ルートおよび別ページ遷移ルートが存在しないこと
     */
    public function test_obsolete_equipment_routes_do_not_exist(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::first();
        $equipment = Equipment::factory()->committee()->create();

        // 旧タイムテーブルスコープ配下ルートは完全削除済み
        $this->get("/timetables/{$timetable->id}/equipments")->assertNotFound();
        $this->get("/timetables/{$timetable->id}/equipments/create")->assertNotFound();

        // モーダル完結化により別ページルート（create/show/edit）は廃止（POST/PUT/DELETEルートが存在するため GET は405、/edit は404）
        $this->get('/equipments/create')->assertStatus(405);
        $this->get("/equipments/{$equipment->id}")->assertStatus(405);
        $this->get("/equipments/{$equipment->id}/edit")->assertNotFound();
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
