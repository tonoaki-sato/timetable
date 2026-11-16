<?php

namespace Tests\Feature;

use App\Enums\BoothCategory;
use App\Enums\BoothType;
use App\Enums\FacilityCategory;
use App\Enums\FacilityType;
use App\Enums\LayoutItemStatus;
use App\Enums\LayoutItemType;
use App\Models\LayoutArea;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Timetable;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Timetable $timetable;

    protected LayoutMap $map;

    protected LayoutArea $areaA;

    protected LayoutArea $areaPark;

    protected LayoutItem $itemBoothA;

    protected LayoutItem $itemBoothB;

    protected LayoutItem $itemFacilityWater;

    protected LayoutItem $itemFacilityStaff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'name' => '2026年本祭1日目',
            'target_date' => '2026-10-18',
            'created_by' => $this->user->id,
        ]);

        $this->setupLayoutData();
    }

    /**
     * テスト用現場配置データのセットアップ
     */
    protected function setupLayoutData(): void
    {
        $this->map = LayoutMap::create([
            'timetable_id' => $this->timetable->id,
            'name' => 'メイン通り商店街配置図',
            'version_tag' => 'v1.0',
            'notes' => '現場配置計画テスト用データ',
            'created_by' => $this->user->id,
        ]);

        $this->areaA = LayoutArea::create([
            'layout_map_id' => $this->map->id,
            'zone_code' => 'ZONE_A',
            'name' => 'メイン通りA列（東側歩道）',
            'display_order' => 10,
        ]);

        $this->areaPark = LayoutArea::create([
            'layout_map_id' => $this->map->id,
            'zone_code' => 'AREA_KITAGAWA',
            'name' => '北川駐車場内',
            'display_order' => 20,
        ]);

        // 出展者マスター
        $vendor = Vendor::create([
            'name' => '水浅青果',
            'contact_person' => '水浅 太郎',
            'contact' => '090-1111-2222',
        ]);

        // コマ1 (A99 - ブース)
        $this->itemBoothA = LayoutItem::create([
            'layout_map_id' => $this->map->id,
            'layout_area_id' => $this->areaA->id,
            'item_type' => LayoutItemType::Booth,
            'vendor_id' => $vendor->id,
            'code' => 'A99',
            'name' => '水浅青果 本店',
            'category' => BoothCategory::GozaCookedFood->value,
            'sub_type' => BoothType::Tent->value,
            'status' => LayoutItemStatus::Confirmed,
            'location_description' => 'セブンイレブン向かい',
            'notes' => '新鮮野菜販売',
            'display_order' => 1,
        ]);

        // コマ2 (B99 - ブース未定)
        $this->itemBoothB = LayoutItem::create([
            'layout_map_id' => $this->map->id,
            'layout_area_id' => $this->areaA->id,
            'item_type' => LayoutItemType::Booth,
            'vendor_id' => null,
            'code' => 'B99',
            'name' => null,
            'category' => BoothCategory::GozaGeneralGoods->value,
            'sub_type' => BoothType::StreetStall->value,
            'status' => LayoutItemStatus::Tentative,
            'display_order' => 2,
        ]);

        // 設備1: 給水設備 (W-99)
        $this->itemFacilityWater = LayoutItem::create([
            'layout_map_id' => $this->map->id,
            'layout_area_id' => $this->areaPark->id,
            'item_type' => LayoutItemType::Facility,
            'code' => 'W-99',
            'name' => '給水設備①',
            'category' => FacilityCategory::WaterSupply->value,
            'sub_type' => FacilityType::WaterSupply->value,
            'status' => LayoutItemStatus::Confirmed,
            'location_description' => '北川P入口',
            'notes' => 'ポリタンク常設',
            'display_order' => 1,
        ]);

        // 設備2: スタッフ控室 (STAFF-99)
        $this->itemFacilityStaff = LayoutItem::create([
            'layout_map_id' => $this->map->id,
            'layout_area_id' => $this->areaPark->id,
            'item_type' => LayoutItemType::Facility,
            'code' => 'STAFF-99',
            'name' => 'スタッフ控室',
            'category' => FacilityCategory::Other->value,
            'sub_type' => FacilityType::StaffRoom->value,
            'status' => LayoutItemStatus::Tentative,
            'location_description' => '本部テント裏',
            'display_order' => 2,
        ]);
    }

    /**
     * 現場配置計画ダッシュボード画面が表示されること
     */
    public function test_layout_dashboard_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.layouts.index', $this->timetable));

        $response->assertStatus(200);
        $response->assertSee('現場配置計画');
        $response->assertSee('メイン通り商店街配置図');
        $response->assertSee('給水設備①');
        $response->assertSee('出展・出店者マスター');
    }

    /**
     * 統合配置一覧画面が表示され、7列構成・詳細モーダル・丸アイコンが確認できること
     */
    public function test_items_list_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.layouts.booths', $this->timetable));

        $response->assertStatus(200);
        $response->assertSee('配置一覧');
        $response->assertSee('ござ市、インフラ設備などの配置を管理します。');
        $response->assertSee('新規配置追加');
        $response->assertSee('ダッシュボードに戻る');
        $response->assertSee('配置一覧（全4件）');
        $response->assertSee('A99');
        $response->assertSee('B99');
        $response->assertSee('W-99');
        $response->assertSee('STAFF-99');
        $response->assertSee('テント');
        $response->assertSee('現場インフラ');
        $response->assertSee('🟢');
        $response->assertSee('🟡');

        // モーダル要素とスクリプトが正しくレンダリングされていること
        $response->assertSee('itemEditModal');
        $response->assertSee('modal-item-detail');
        $content = $response->getContent();
        $this->assertTrue(
            str_contains($content, 'booths.js') || str_contains($content, 'booths-'),
            'booths.js script should be rendered on the page.'
        );
    }

    /**
     * 新規ブース配置が登録できること (product_items は notes に統合)
     */
    public function test_booth_item_can_be_created(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('timetables.layouts.booths.store', $this->timetable), [
                'item_type' => 'booth',
                'code' => 'KC-01',
                'layout_area_id' => $this->areaPark->id,
                'name' => 'テストキッチンカー',
                'category' => BoothCategory::GozaOutdoorCooking->value,
                'sub_type' => BoothType::KitchenCar->value,
                'status' => 'confirmed',
                'location_description' => '駐車場入口横',
                'notes' => '牛串、フランクフルト。発電機持参',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('layout_items', [
            'layout_map_id' => $this->map->id,
            'layout_area_id' => $this->areaPark->id,
            'item_type' => 'booth',
            'code' => 'KC-01',
            'name' => 'テストキッチンカー',
            'category' => BoothCategory::GozaOutdoorCooking->value,
            'sub_type' => BoothType::KitchenCar->value,
            'status' => 'confirmed',
            'notes' => '牛串、フランクフルト。発電機持参',
        ]);
    }

    /**
     * 新規設備が同一エンドポイントから登録できること
     */
    public function test_facility_item_can_be_created(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('timetables.layouts.booths.store', $this->timetable), [
                'item_type' => 'facility',
                'code' => 'STAFF-NEW',
                'layout_area_id' => $this->areaPark->id,
                'name' => '第2スタッフ控室',
                'category' => FacilityCategory::Other->value,
                'sub_type' => FacilityType::StaffRoom->value,
                'status' => 'tentative',
                'location_description' => '市役所前特設エリア入口',
                'notes' => '新規配備テスト',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('layout_items', [
            'layout_map_id' => $this->map->id,
            'item_type' => 'facility',
            'code' => 'STAFF-NEW',
            'name' => '第2スタッフ控室',
            'category' => FacilityCategory::Other->value,
            'sub_type' => FacilityType::StaffRoom->value,
            'status' => 'tentative',
        ]);
    }

    /**
     * 配置情報（区分・形態・状態・notes）の更新ができること
     */
    public function test_layout_item_can_be_updated(): void
    {
        $response = $this->actingAs($this->user)
            ->put(route('timetables.layouts.booths.update', [$this->timetable, $this->itemBoothA]), [
                'item_type' => 'booth',
                'code' => 'A99-REV',
                'layout_area_id' => $this->areaA->id,
                'name' => '水浅青果 焼き芋店',
                'category' => BoothCategory::GozaOutdoorCooking->value,
                'sub_type' => BoothType::KitchenCar->value,
                'status' => 'confirmed',
                'location_description' => 'セブンイレブン向かい',
                'notes' => '石焼き芋、焼きとうもろこし。三方幕テント持参確認',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('layout_items', [
            'id' => $this->itemBoothA->id,
            'code' => 'A99-REV',
            'name' => '水浅青果 焼き芋店',
            'category' => BoothCategory::GozaOutdoorCooking->value,
            'sub_type' => BoothType::KitchenCar->value,
            'status' => 'confirmed',
            'notes' => '石焼き芋、焼きとうもろこし。三方幕テント持参確認',
        ]);
    }

    /**
     * 配置が削除できること
     */
    public function test_layout_item_can_be_deleted(): void
    {
        $response = $this->actingAs($this->user)
            ->delete(route('timetables.layouts.booths.destroy', [$this->timetable, $this->itemBoothA]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('layout_items', [
            'id' => $this->itemBoothA->id,
        ]);
    }

    /**
     * 表示種別によるフィルタリングができること
     */
    public function test_filter_items_by_type(): void
    {
        // 現場設備のみ表示
        $response = $this->actingAs($this->user)
            ->get(route('timetables.layouts.booths', [$this->timetable, 'item_type' => 'facility']));

        $response->assertStatus(200);
        $response->assertSee('W-99');
        $response->assertSee('STAFF-99');
        $response->assertDontSee('A99');
        $response->assertDontSee('B99');

        // 出店ブースのみ表示
        $responseBooth = $this->actingAs($this->user)
            ->get(route('timetables.layouts.booths', [$this->timetable, 'item_type' => 'booth']));

        $responseBooth->assertStatus(200);
        $responseBooth->assertSee('A99');
        $responseBooth->assertSee('B99');
        $responseBooth->assertDontSee('W-99');
        $responseBooth->assertDontSee('STAFF-99');
    }
}
