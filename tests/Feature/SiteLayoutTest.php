<?php

namespace Tests\Feature;

use App\Enums\BoothCategory;
use App\Enums\BoothType;
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
            'booth_type' => BoothType::Tent->value,
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
            'booth_type' => BoothType::OpenStall->value,
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
            'category' => FacilityType::WaterSupply->value,
            'booth_type' => BoothType::OpenStall->value,
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
            'category' => FacilityType::StaffRoom->value,
            'booth_type' => BoothType::PartnerBuilding->value,
            'status' => LayoutItemStatus::Tentative,
            'location_description' => '本部テント裏',
            'display_order' => 2,
        ]);
    }

    /**
     * 配置計画画面が表示されること
     */
    public function test_layout_dashboard_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.layouts.index', $this->timetable));

        $response->assertStatus(200);
        $response->assertSee($this->timetable->name.' 配置計画');
        $response->assertSee('ござ市、インフラ設備などの配置情報を閲覧できます');
        $response->assertSee('配置一覧');
        $response->assertSee('hover:bg-indigo-600 hover:text-white hover:border-indigo-600', false);
        $response->assertDontSee('<span>出展・出店者マスター</span>', false);
        $response->assertDontSee('v1.0');
        $response->assertDontSee('ござ市・出店コマ割');
        $response->assertDontSee('メイン通り配置マップビュー');
        $response->assertDontSee('ZONE_A');
        $response->assertDontSee('全エリアの出展ブースおよび現場設備の配置状況一覧です。');
        $response->assertDontSee('出展一覧 →');
        $response->assertDontSee('現場設備一覧 →');

        // 1段目: 出展区分・設備区分
        $response->assertSee('区分別内訳');
        $response->assertSee('出展区分 (全2件)');
        $response->assertSee('設備区分 (全2箇所)');
        $response->assertSee(route('timetables.layouts.booths', [$this->timetable, 'item_type' => 'booth']));
        $response->assertSee(route('timetables.layouts.booths', [$this->timetable, 'item_type' => 'facility']));
        $response->assertSee(route('timetables.layouts.booths', [$this->timetable, 'item_type' => 'booth', 'category' => BoothCategory::GozaOutdoorCooking->value]));
        $response->assertSee(route('timetables.layouts.booths', [$this->timetable, 'item_type' => 'facility', 'category' => FacilityType::WaterSupply->value]));

        // 2段目: エリア別 ブース形態内訳
        $response->assertSee('エリア別 ブース形態内訳');
        $response->assertSee(route('timetables.layouts.booths', [$this->timetable, 'area_id' => $this->areaA->id]));
        $response->assertSee(route('timetables.layouts.booths', [$this->timetable, 'area_id' => $this->areaPark->id]));

        // エリア別配置一覧
        $response->assertSee('エリア別配置一覧 (全2エリア)');
        $response->assertSee('メイン通りA列（東側歩道）');
        $response->assertSee('北川駐車場内');
        $response->assertSee('給水設備①');
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
        $response->assertSee('hover:bg-indigo-600 hover:text-white hover:border-indigo-600', false);
        $response->assertSee('配置計画に戻る');
        $response->assertSee('配置一覧（全4件）');
        $response->assertSee('A99');
        $response->assertSee('B99');
        $response->assertSee('W-99');
        $response->assertSee('STAFF-99');
        $response->assertSee('テント');
        $response->assertSee('露店・露出');
        $response->assertSee('提携建屋内');
        $response->assertSee('🟢');
        $response->assertSee('🟡');

        // コード列の直後に種別絵文字アイコンが付いていないこと（コードのみのシンプルなバッジ表示）
        $response->assertDontSee('>A99</span><span class="text-[10px]', false);

        // 一覧画面最下部に重複した「変更を保存する」ボタンが露出していないこと（モーダルフォーム内のみに1つ存在）
        $this->assertSame(
            1,
            substr_count($response->getContent(), '💾 変更を保存する'),
            'There should be exactly one save button on the page (inside itemEditModal).'
        );

        // 詳細モーダルの基本情報カードが削除され、ヘッダーにインライン統合されていること
        $response->assertDontSee('grid grid-cols-2 gap-2 bg-slate-50 p-3 rounded-xl', false);

        // モーダル要素とスクリプトが正しくレンダリングされていること
        $response->assertSee('itemEditModal');
        $response->assertSee('modal-item-detail');
        $response->assertSee('detail-vendor-multiple-booths-container');
        $content = $response->getContent();
        $this->assertTrue(
            str_contains($content, 'booths.js') || str_contains($content, 'booths-'),
            'booths.js script should be rendered on the page.'
        );
    }

    /**
     * 配置一覧画面でエリアによる絞り込みが機能すること
     */
    public function test_items_list_screen_can_filter_by_area(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.layouts.booths', [
                $this->timetable,
                'area_id' => $this->areaA->id,
            ]));

        $response->assertStatus(200);
        $response->assertSee('A99');
        $response->assertSee('B99');
        $response->assertDontSee('W-99');
        $response->assertDontSee('STAFF-99');
        $response->assertSee('クリア');
    }

    /**
     * 配置一覧画面で区分による絞り込みが機能すること
     */
    public function test_items_list_screen_can_filter_by_category(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.layouts.booths', [
                $this->timetable,
                'item_type' => 'booth',
                'category' => BoothCategory::GozaCookedFood->value,
            ]));

        $response->assertStatus(200);
        $response->assertSee('A99');
        $response->assertDontSee('B99'); // B99 は GozaGeneralGoods
        $response->assertDontSee('W-99');
        $response->assertDontSee('STAFF-99');
        $response->assertSee('クリア');

        // 設備区分での絞り込み
        $facilityResponse = $this->actingAs($this->user)
            ->get(route('timetables.layouts.booths', [
                $this->timetable,
                'item_type' => 'facility',
                'category' => FacilityType::WaterSupply->value,
            ]));

        $facilityResponse->assertStatus(200);
        $facilityResponse->assertSee('W-99');
        $facilityResponse->assertDontSee('STAFF-99');
        $facilityResponse->assertDontSee('A99');
        $facilityResponse->assertDontSee('B99');
        $facilityResponse->assertSee('クリア');
    }

    /**
     * 新規ブース配置が登録できること
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
                'booth_type' => BoothType::KitchenCar->value,
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
            'booth_type' => BoothType::KitchenCar->value,
            'status' => 'confirmed',
            'notes' => '牛串、フランクフルト。発電機持参',
        ]);
    }

    /**
     * 新規設備が同一エンドポイントから登録でき、ブース形態（提携建屋内）も保存されること
     */
    public function test_facility_item_can_be_created(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('timetables.layouts.booths.store', $this->timetable), [
                'item_type' => 'facility',
                'code' => 'STAFF-NEW',
                'layout_area_id' => $this->areaPark->id,
                'name' => '第2スタッフ控室',
                'category' => FacilityType::StaffRoom->value,
                'booth_type' => BoothType::PartnerBuilding->value,
                'status' => 'tentative',
                'location_description' => '商店街会館2階',
                'notes' => '新規配備テスト',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('layout_items', [
            'layout_map_id' => $this->map->id,
            'item_type' => 'facility',
            'code' => 'STAFF-NEW',
            'name' => '第2スタッフ控室',
            'category' => FacilityType::StaffRoom->value,
            'booth_type' => BoothType::PartnerBuilding->value,
            'status' => 'tentative',
            'location_description' => '商店街会館2階',
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
                'booth_type' => BoothType::PartnerBuilding->value,
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
            'booth_type' => BoothType::PartnerBuilding->value,
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
