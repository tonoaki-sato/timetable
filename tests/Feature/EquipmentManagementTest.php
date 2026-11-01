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
     * 資材管理一覧画面が表示され、区分でフィルタできること
     */
    public function test_index_displays_and_filters_equipments(): void
    {
        $this->actingAs($this->operator);

        $committeeItem = Equipment::factory()->committee()->create(['name' => 'カラーコーン']);
        $rentalItem = Equipment::factory()->rental()->create(['name' => 'パイプテント']);

        $response = $this->get(route('equipments.index'));
        $response->assertStatus(200);
        $response->assertSee('資材管理');
        $response->assertSee('カラーコーン');
        $response->assertSee('パイプテント');

        // 委員会管理のみフィルタ
        $committeeResponse = $this->get(route('equipments.index', ['type' => 'committee']));
        $committeeResponse->assertSee('カラーコーン');
        $committeeResponse->assertDontSee('パイプテント');

        // レンタルのみフィルタ
        $rentalResponse = $this->get(route('equipments.index', ['type' => 'rental']));
        $rentalResponse->assertSee('パイプテント');
        $rentalResponse->assertDontSee('カラーコーン');
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
     * 委員会管理機材の新規登録ができること
     */
    public function test_store_creates_committee_equipment(): void
    {
        $this->actingAs($this->operator);
        $lane = Lane::factory()->create(['name' => 'カラーコーン班']);

        $response = $this->post(route('equipments.store'), [
            'name' => 'カラーコーン（バー付き）',
            'type' => 'committee',
            'quantity' => 50,
            'unit' => '個',
            'storage_location' => '番所地下倉庫',
            'lane_id' => $lane->id,
            'description' => '路上規制用コーン',
        ]);

        $equipment = Equipment::where('name', 'カラーコーン（バー付き）')->first();
        $this->assertNotNull($equipment);
        $response->assertRedirect(route('equipments.show', $equipment));

        $this->assertDatabaseHas('equipments', [
            'name' => 'カラーコーン（バー付き）',
            'type' => 'committee',
            'quantity' => 50,
            'storage_location' => '番所地下倉庫',
        ]);
    }

    /**
     * イベント会社レンタル機材の新規登録ができること（提携1社のデフォルト補完含む）
     */
    public function test_store_creates_rental_equipment_with_default_company(): void
    {
        $this->actingAs($this->operator);

        $response = $this->post(route('equipments.store'), [
            'name' => 'パイプテント 2K×3K',
            'type' => 'rental',
            'quantity' => 10,
            'unit' => '張',
            'event_company_contact' => '03-3456-7890',
        ]);

        $equipment = Equipment::where('name', 'パイプテント 2K×3K')->first();
        $this->assertNotNull($equipment);
        $response->assertRedirect(route('equipments.show', $equipment));

        $this->assertDatabaseHas('equipments', [
            'name' => 'パイプテント 2K×3K',
            'type' => 'rental',
            'quantity' => 10,
            'event_company_name' => '東京イベントサプライ株式会社',
        ]);
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
            'unit' => '台',
            'storage_location' => '番所地下倉庫',
        ]);

        $updateRes->assertRedirect(route('equipments.show', $equipment));
        $this->assertDatabaseHas('equipments', [
            'id' => $equipment->id,
            'name' => '新・機材名',
            'quantity' => 15,
        ]);

        // 削除
        $destroyRes = $this->delete(route('equipments.destroy', $equipment));
        $destroyRes->assertRedirect(route('equipments.index'));
        $this->assertDatabaseMissing('equipments', ['id' => $equipment->id]);
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
}
