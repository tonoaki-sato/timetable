<?php

namespace Tests\Feature;

use App\Enums\BoothCategory;
use App\Enums\FacilityType;
use App\Models\GozaStore;
use App\Models\LayoutBooth;
use App\Models\LayoutFacility;
use App\Models\LayoutMap;
use App\Models\Timetable;
use App\Models\User;
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

        // ダッシュボードからのリンク元表記も確認
        $indexResponse = $this->actingAs($this->user)
            ->get(route('timetables.layouts.index', $this->timetable));
        $indexResponse->assertStatus(200);
        $indexResponse->assertSee('インフラ設備一覧');

        $updateResponse = $this->actingAs($this->user)
            ->put(route('timetables.layouts.facilities.update', [$this->timetable, $facility]), [
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
            'name' => '給水設備①（北川P特設）',
            'status' => '確定',
        ]);
    }

    /**
     * ござ市出店者のCRUDができること
     */
    public function test_goza_store_crud(): void
    {
        // 一覧
        $listResponse = $this->actingAs($this->user)
            ->get(route('timetables.goza-stores.index', $this->timetable));

        $listResponse->assertStatus(200);
        $listResponse->assertSee('ござ市出店者マスター管理');

        // 新規登録
        $storeResponse = $this->actingAs($this->user)
            ->post(route('timetables.goza-stores.store', $this->timetable), [
                'store_name' => '新規宿場カフェ',
                'representative_name' => '鈴木 花子',
                'emergency_contact' => '090-9999-8888',
                'product_items' => 'ドリップコーヒー・焼き菓子',
                'status' => 'confirmed',
                'notes' => '初出店',
            ]);

        $storeResponse->assertRedirect();
        $storeResponse->assertSessionHas('success');

        $store = GozaStore::where('store_name', '新規宿場カフェ')->firstOrFail();
        $this->assertEquals('鈴木 花子', $store->representative_name);

        // 更新
        $updateResponse = $this->actingAs($this->user)
            ->put(route('timetables.goza-stores.update', [$this->timetable, $store]), [
                'store_name' => '新規宿場カフェ 保土ヶ谷店',
                'representative_name' => '鈴木 花子',
                'emergency_contact' => '090-9999-8888',
                'product_items' => 'ドリップコーヒー・焼き菓子・アイス',
                'status' => 'checked_in',
                'notes' => '当日受付済',
            ]);

        $updateResponse->assertRedirect();
        $this->assertDatabaseHas('goza_stores', [
            'id' => $store->id,
            'store_name' => '新規宿場カフェ 保土ヶ谷店',
            'status' => 'checked_in',
        ]);

        // 削除
        $deleteResponse = $this->actingAs($this->user)
            ->delete(route('timetables.goza-stores.destroy', [$this->timetable, $store]));

        $deleteResponse->assertRedirect();
        $this->assertDatabaseMissing('goza_stores', [
            'id' => $store->id,
        ]);
    }

    /**
     * 設備種別のカテゴリ分類マッピングが正しいこと
     */
    public function test_facility_type_category_mapping(): void
    {
        $this->assertSame(\App\Enums\FacilityCategory::WaterSupply, FacilityType::WaterSupply->category());
        $this->assertSame(\App\Enums\FacilityCategory::ToiletAndNursing, FacilityType::ToiletFree->category());
        $this->assertSame(\App\Enums\FacilityCategory::ToiletAndNursing, FacilityType::ToiletPaid->category());
        $this->assertSame(\App\Enums\FacilityCategory::ToiletAndNursing, FacilityType::NursingRoom->category());
        $this->assertSame(\App\Enums\FacilityCategory::Other, FacilityType::FirstAid->category());
        $this->assertSame(\App\Enums\FacilityCategory::Other, FacilityType::Headquarters->category());
        $this->assertSame(\App\Enums\FacilityCategory::Other, FacilityType::StaffWaitingArea->category());
    }
}
