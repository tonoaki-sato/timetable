<?php

namespace Tests\Feature;

use App\Enums\BoothCategory;
use App\Enums\FacilityCategory;
use App\Enums\FacilityType;
use App\Models\LayoutBooth;
use App\Models\LayoutFacility;
use App\Models\LayoutMap;
use App\Models\Timetable;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\SiteLayoutSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteLayoutTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Timetable $timetable;

    protected LayoutMap $map;

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

        $this->seed(SiteLayoutSeeder::class);
        $this->map = LayoutMap::where('timetable_id', $this->timetable->id)->firstOrFail();
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
    }

    /**
     * コマ割り一覧画面が表示され、絞り込みができること
     */
    public function test_booths_list_screen_can_be_rendered(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.layouts.booths', $this->timetable));

        $response->assertStatus(200);
        $response->assertSee('コマ割り・出店ブース一覧');
        $response->assertSee('A01');
        $response->assertSee('B01');
    }

    /**
     * コマ割り情報の更新ができること
     */
    public function test_booth_information_can_be_updated(): void
    {
        $booth = LayoutBooth::where('layout_map_id', $this->map->id)
            ->where('booth_code', 'A01')
            ->firstOrFail();

        $response = $this->actingAs($this->user)
            ->put(route('timetables.layouts.booths.update', [$this->timetable, $booth]), [
                'name' => '水浅青果 本店',
                'category' => BoothCategory::FoodCooking->value,
                'adjacent_landmark' => 'セブンイレブン向かい',
                'notes' => '三方幕テント持参確認',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('layout_booths', [
            'id' => $booth->id,
            'name' => '水浅青果 本店',
            'category' => BoothCategory::FoodCooking->value,
            'adjacent_landmark' => 'セブンイレブン向かい',
        ]);
    }

    /**
     * インフラ設備一覧画面が表示され、設備の更新ができること
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
        $response->assertSee('トイレ・授乳室');
        $response->assertSee('その他');
        $response->assertSee('W-01');
        $response->assertHeader('Content-Security-Policy');
        $response->assertSee('facilities');
        $response->assertDontSee('<script type="module">', false);
        $response->assertSee('（指定なし）');
        $response->assertSee('S&S前駐車場内');
        $response->assertSee('税務署前駐車場内');
        $response->assertDontSee('タカオ前ステージ');
        $response->assertDontSee('日本パーキング');
        $response->assertDontSee('タイムス');
        $response->assertDontSee('(parking)');
        $response->assertDontSee('(street)');

        // ダッシュボードからのリンク元表記も確認
        $indexResponse = $this->actingAs($this->user)
            ->get(route('timetables.layouts.index', $this->timetable));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('インフラ設備一覧');

        $updateResponse = $this->actingAs($this->user)
            ->put(route('timetables.layouts.facilities.update', [$this->timetable, $facility]), [
                'facility_code' => 'W-01-REV',
                'name' => '給水設備①（北川P特設）',
                'facility_type' => FacilityType::WaterSupply->value,
                'status' => '確定',
                'location_description' => '北川駐車場 最奥部水栓付近',
                'notes' => 'ポリタンク2個配備済',
            ]);

        $updateResponse->assertRedirect();
        $updateResponse->assertSessionHas('success');

        $this->assertDatabaseHas('layout_facilities', [
            'id' => $facility->id,
            'facility_code' => 'W-01-REV',
            'name' => '給水設備①（北川P特設）',
            'status' => '確定',
        ]);
    }

    /**
     * 新規設備が登録できること、およびコード重複時にバリデーションエラーとなること
     */
    public function test_facility_can_be_created_and_code_uniqueness_is_validated(): void
    {
        // 1. 新規登録の成功
        $response = $this->actingAs($this->user)
            ->post(route('timetables.layouts.facilities.store', $this->timetable), [
                'facility_code' => 'W-NEW-01',
                'name' => '新設給水所A',
                'facility_type' => FacilityType::WaterSupply->value,
                'status' => '予定',
                'location_description' => '市役所前特設エリア入口',
                'notes' => '新規配備テスト',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('layout_facilities', [
            'layout_map_id' => $this->map->id,
            'facility_code' => 'W-NEW-01',
            'name' => '新設給水所A',
            'status' => '予定',
        ]);

        // 2. 同一図面内での重複コード登録の失敗
        $duplicateResponse = $this->actingAs($this->user)
            ->post(route('timetables.layouts.facilities.store', $this->timetable), [
                'facility_code' => 'W-NEW-01',
                'name' => '新設給水所B（重複）',
                'facility_type' => FacilityType::WaterSupply->value,
                'status' => '予定',
            ]);

        $duplicateResponse->assertSessionHasErrors(['facility_code']);
    }

    /**
     * 設備が削除できること
     */
    public function test_facility_can_be_deleted(): void
    {
        $facility = LayoutFacility::where('layout_map_id', $this->map->id)
            ->where('facility_code', 'W-01')
            ->firstOrFail();

        // 一覧画面に削除ボタンが表示されていること
        $listResponse = $this->actingAs($this->user)
            ->get(route('timetables.layouts.facilities', $this->timetable));
        $listResponse->assertStatus(200);
        $listResponse->assertSee('削除');
        $listResponse->assertSee('🗑️');

        // 削除リクエスト
        $response = $this->actingAs($this->user)
            ->delete(route('timetables.layouts.facilities.destroy', [$this->timetable, $facility]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('layout_facilities', [
            'id' => $facility->id,
        ]);
    }

    /**
     * 商業出店者のCRUDおよび複数ブース割当ができること
     */
    public function test_vendor_crud_and_multi_booth_assignment(): void
    {
        // 1. 一覧表示（ヘッダー構成・説明文・3列構成・アクションボタン検証）
        $listResponse = $this->actingAs($this->user)
            ->get(route('timetables.vendors.index', $this->timetable));

        $listResponse->assertStatus(200);
        $listResponse->assertSee('出店者マスター管理');
        $listResponse->assertSee('イベントにおける商業出店者の基本情報と配置コマを一元管理します。');
        $listResponse->assertSee('新規出店者を登録');
        $listResponse->assertSee('配置ダッシュボードへ');
        $listResponse->assertSee('インフラ設備一覧');
        $listResponse->assertSee('名前');
        $listResponse->assertSee('配置');
        $listResponse->assertSee('操作');
        $listResponse->assertDontSee('>状態<', false);

        // 2. 新規出店者を登録（statusなし）
        $storeResponse = $this->actingAs($this->user)
            ->post(route('timetables.vendors.store', $this->timetable), [
                'name' => '新規宿場カフェ',
                'representative_name' => '鈴木 花子',
                'emergency_contact' => '090-9999-8888',
                'notes' => '初出店',
            ]);

        $storeResponse->assertRedirect();
        $storeResponse->assertSessionHas('success');

        $vendor = Vendor::where('name', '新規宿場カフェ')->firstOrFail();
        $this->assertEquals('鈴木 花子', $vendor->representative_name);
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => '新規宿場カフェ',
        ]);

        // 3. 一覧表示での表示確認（検索、名前クリック詳細モーダル属性、コマ数表示、CSP検証）
        $afterStoreList = $this->actingAs($this->user)
            ->get(route('timetables.vendors.index', [$this->timetable, 'search' => '新規宿場カフェ']));
        $afterStoreList->assertStatus(200);
        $afterStoreList->assertSee('新規宿場カフェ');
        $afterStoreList->assertSee('(0コマ)');
        $afterStoreList->assertSee('data-action="open-vendor-detail"', false);
        $afterStoreList->assertSee('data-action="open-edit-vendor-modal"', false);
        $afterStoreList->assertSee('✏️');
        $afterStoreList->assertSee('編集');
        $afterStoreList->assertSee('🗑️');
        $afterStoreList->assertSee('削除');
        $afterStoreList->assertDontSee('<script type="module">', false);
        $afterStoreList->assertDontSee('<script>', false);

        // 4. 同一出店者に対する複数ブース割当（それぞれ異なる品目を設定）
        $booth1 = LayoutBooth::where('layout_map_id', $this->map->id)->where('booth_code', 'A01')->firstOrFail();
        $booth2 = LayoutBooth::where('layout_map_id', $this->map->id)->where('booth_code', 'A02')->firstOrFail();

        $booth1->update([
            'vendor_id' => $vendor->id,
            'category' => BoothCategory::FoodNonCooking,
            'product_items' => '焼きたてワッフル',
        ]);
        $booth2->update([
            'vendor_id' => $vendor->id,
            'category' => BoothCategory::FoodCooking,
            'product_items' => '自家焙煎アイスコーヒー',
        ]);

        $vendorWithBooths = $vendor->fresh(['booths']);
        $this->assertCount(2, $vendorWithBooths->booths);
        $this->assertEquals('焼きたてワッフル', $vendorWithBooths->booths[0]->product_items);
        $this->assertEquals('自家焙煎アイスコーヒー', $vendorWithBooths->booths[1]->product_items);

        $multiBoothsList = $this->actingAs($this->user)
            ->get(route('timetables.vendors.index', [$this->timetable, 'search' => '新規宿場カフェ']));
        $multiBoothsList->assertSee('(2コマ)');
        $multiBoothsList->assertSee('A01');
        $multiBoothsList->assertSee('焼きたてワッフル');
        $multiBoothsList->assertSee('A02');
        $multiBoothsList->assertSee('自家焙煎アイスコーヒー');

        // 5. 更新
        $updateResponse = $this->actingAs($this->user)
            ->put(route('timetables.vendors.update', [$this->timetable, $vendor]), [
                'name' => '新規宿場カフェ 保土ヶ谷店',
                'representative_name' => '鈴木 花子',
                'emergency_contact' => '090-9999-8888',
                'notes' => '店名変更完了',
            ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => '新規宿場カフェ 保土ヶ谷店',
        ]);

        // 6. 削除
        $deleteResponse = $this->actingAs($this->user)
            ->delete(route('timetables.vendors.destroy', [$this->timetable, $vendor]));

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('vendors', [
            'id' => $vendor->id,
        ]);
        // コマのvendor_idが解除されていること
        $this->assertNull($booth1->fresh()->vendor_id);
    }

    /**
     * 設備種別のカテゴリ分類マッピングが正しいこと
     */
    public function test_facility_type_category_mapping(): void
    {
        $this->assertSame(FacilityCategory::WaterSupply, FacilityType::WaterSupply->category());
        $this->assertSame(FacilityCategory::ToiletAndNursing, FacilityType::ToiletFree->category());
        $this->assertSame(FacilityCategory::ToiletAndNursing, FacilityType::ToiletPaid->category());
        $this->assertSame(FacilityCategory::ToiletAndNursing, FacilityType::NursingRoom->category());
        $this->assertSame(FacilityCategory::Other, FacilityType::FirstAid->category());
        $this->assertSame(FacilityCategory::Other, FacilityType::Headquarters->category());
        $this->assertSame(FacilityCategory::Other, FacilityType::StaffWaitingArea->category());
    }
}
