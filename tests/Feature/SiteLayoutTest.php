<?php

namespace Tests\Feature;

use App\Enums\BoothCategory;
use App\Enums\BoothStatus;
use App\Enums\BoothType;
use App\Enums\FacilityStatus;
use App\Enums\FacilityType;
use App\Models\LayoutArea;
use App\Models\LayoutBooth;
use App\Models\LayoutFacility;
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

        // コマ1 (A01)
        LayoutBooth::create([
            'layout_map_id' => $this->map->id,
            'layout_area_id' => $this->areaA->id,
            'vendor_id' => $vendor->id,
            'booth_code' => 'A01',
            'name' => '水浅青果 本店',
            'category' => BoothCategory::GozaCookedFood,
            'booth_type' => BoothType::Tent,
            'status' => BoothStatus::Confirmed,
            'product_items' => '新鮮野菜',
            'adjacent_landmark' => 'セブンイレブン向かい',
            'display_order' => 1,
        ]);

        // コマ2 (B01)
        LayoutBooth::create([
            'layout_map_id' => $this->map->id,
            'layout_area_id' => $this->areaA->id,
            'vendor_id' => null,
            'booth_code' => 'B01',
            'name' => null,
            'category' => BoothCategory::GozaGeneralGoods,
            'booth_type' => BoothType::StreetStall,
            'status' => BoothStatus::Tentative,
            'display_order' => 2,
        ]);

        // 設備1: 給水設備
        LayoutFacility::create([
            'layout_map_id' => $this->map->id,
            'layout_area_id' => $this->areaPark->id,
            'facility_code' => 'W-01',
            'name' => '給水設備①',
            'facility_type' => FacilityType::WaterSupply,
            'status' => FacilityStatus::Confirmed,
            'location_description' => '北川P入口',
            'display_order' => 1,
        ]);

        // 設備2: スタッフ控室
        LayoutFacility::create([
            'layout_map_id' => $this->map->id,
            'layout_area_id' => $this->areaPark->id,
            'facility_code' => 'STAFF-01',
            'name' => 'スタッフ控室',
            'facility_type' => FacilityType::StaffRoom,
            'status' => FacilityStatus::Tentative,
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
        $response->assertSee('A01');
        $response->assertSee('B01');
        $response->assertSee('給水設備①');
        $response->assertSee('出展・出店者マスター');
    }

    /**
     * コマ割り一覧画面が表示され、6区分・4形態・2状態が確認できること
     */
    public function test_booths_list_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.layouts.booths', $this->timetable));

        $response->assertStatus(200);
        $response->assertSee('コマ割り・出店ブース一覧');
        $response->assertSee('A01');
        $response->assertSee('B01');
        $response->assertSee('ござ市（調理済品販売）');
        $response->assertSee('テント');
        $response->assertSee('確定');
        $response->assertSee('出展・出店者マスター');
    }

    /**
     * コマ割り情報（区分・形態・状態）の更新ができること
     */
    public function test_booth_information_can_be_updated(): void
    {
        $booth = LayoutBooth::where('layout_map_id', $this->map->id)
            ->where('booth_code', 'A01')
            ->firstOrFail();

        $response = $this->actingAs($this->user)
            ->put(route('timetables.layouts.booths.update', [$this->timetable, $booth]), [
                'name' => '水浅青果 焼き芋店',
                'category' => BoothCategory::GozaOutdoorCooking->value,
                'booth_type' => BoothType::KitchenCar->value,
                'status' => BoothStatus::Confirmed->value,
                'adjacent_landmark' => 'セブンイレブン向かい',
                'product_items' => '石焼き芋、焼きとうもろこし',
                'notes' => '三方幕テント持参確認',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('layout_booths', [
            'id' => $booth->id,
            'name' => '水浅青果 焼き芋店',
            'category' => BoothCategory::GozaOutdoorCooking->value,
            'booth_type' => BoothType::KitchenCar->value,
            'status' => BoothStatus::Confirmed->value,
            'product_items' => '石焼き芋、焼きとうもろこし',
        ]);
    }

    /**
     * インフラ設備一覧画面が表示され、スタッフ控室や未定/確定状態が反映されること
     */
    public function test_facilities_list_screen_and_update(): void
    {
        $facility = LayoutFacility::where('layout_map_id', $this->map->id)
            ->where('facility_code', 'W-01')
            ->firstOrFail();

        $response = $this->actingAs($this->user)
            ->get(route('timetables.layouts.facilities', $this->timetable));

        $response->assertStatus(200);
        $response->assertSee('インフラ設備一覧');
        $response->assertSee('給水設備');
        $response->assertSee('スタッフ控室');
        $response->assertSee('W-01');

        // 更新テスト
        $updateResponse = $this->actingAs($this->user)
            ->put(route('timetables.layouts.facilities.update', [$this->timetable, $facility]), [
                'facility_code' => 'W-01-REV',
                'name' => '給水設備①（北川P特設）',
                'facility_type' => FacilityType::WaterSupply->value,
                'status' => 'confirmed',
                'location_description' => '北川駐車場 最奥部水栓付近',
                'notes' => 'ポリタンク2個配備済',
            ]);

        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('success');

        $this->assertDatabaseHas('layout_facilities', [
            'id' => $facility->id,
            'facility_code' => 'W-01-REV',
            'name' => '給水設備①（北川P特設）',
            'status' => 'confirmed',
        ]);
    }

    /**
     * 新規設備が登録できること
     */
    public function test_facility_can_be_created(): void
    {
        $response = $this->actingAs($this->user)
            ->post(route('timetables.layouts.facilities.store', $this->timetable), [
                'facility_code' => 'STAFF-NEW',
                'name' => '第2スタッフ控室',
                'facility_type' => FacilityType::StaffRoom->value,
                'status' => 'tentative',
                'location_description' => '市役所前特設エリア入口',
                'notes' => '新規配備テスト',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('layout_facilities', [
            'layout_map_id' => $this->map->id,
            'facility_code' => 'STAFF-NEW',
            'name' => '第2スタッフ控室',
            'facility_type' => FacilityType::StaffRoom->value,
            'status' => 'tentative',
        ]);
    }

    /**
     * 設備が削除できること
     */
    public function test_facility_can_be_deleted(): void
    {
        $facility = LayoutFacility::where('layout_map_id', $this->map->id)
            ->where('facility_code', 'W-01')
            ->firstOrFail();

        $response = $this->actingAs($this->user)
            ->delete(route('timetables.layouts.facilities.destroy', [$this->timetable, $facility]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('layout_facilities', [
            'id' => $facility->id,
        ]);
    }
}
