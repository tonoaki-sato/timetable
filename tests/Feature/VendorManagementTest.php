<?php

namespace Tests\Feature;

use App\Enums\BoothCategory;
use App\Enums\BoothStatus;
use App\Enums\BoothType;
use App\Models\LayoutArea;
use App\Models\LayoutBooth;
use App\Models\LayoutMap;
use App\Models\Timetable;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * 出展・出店者一覧画面が表示され、タイトルと新規登録ボタンが存在すること
     */
    public function test_vendors_list_screen_can_be_rendered(): void
    {
        $vendor = Vendor::create([
            'name' => '水浅青果',
            'contact_person' => '水浅 太郎',
            'contact' => '090-1111-2222',
            'notes' => '軽トラ朝搬入。複数コマ展開。',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('vendors.index'));

        $response->assertStatus(200);
        $response->assertSee('出展・出店者一覧');
        $response->assertSee('新規登録');
        $response->assertSee('水浅青果');
        $response->assertSee('水浅 太郎');
        $response->assertSee('090-1111-2222');

        // モーダル要素とスクリプトが正しくレンダリングされていること
        $response->assertSee('modal-vendor-detail');
        $response->assertSee('modal-vendor-form');
        $response->assertSee('btn-open-create-vendor');

        // 出展者名リンクが黒色ベース（text-slate-800）および操作ボタン（✏️ 編集 / 🗑️ 削除）が表示されていること
        $response->assertSee('text-slate-800');
        $response->assertSee('✏️');
        $response->assertSee('🗑️');

        $content = $response->getContent();
        $this->assertTrue(
            str_contains($content, 'vendors.js') || str_contains($content, 'vendors-'),
            'vendors.js script should be rendered on the page.'
        );
    }

    /**
     * 出展・出店者の新規登録・更新・削除（CRUD）ができること
     */
    public function test_vendor_crud(): void
    {
        // 1. 新規登録
        $response = $this->actingAs($this->user)
            ->post(route('vendors.store'), [
                'name' => 'ハングリータイガー',
                'contact_person' => '営業部 鈴木',
                'contact' => '090-0000-0001',
                'notes' => '車両電源使用。大型キッチンカー。',
            ]);

        $response->assertRedirect(route('vendors.index'));
        $this->assertDatabaseHas('vendors', [
            'name' => 'ハングリータイガー',
            'contact_person' => '営業部 鈴木',
            'contact' => '090-0000-0001',
        ]);

        $vendor = Vendor::where('name', 'ハングリータイガー')->firstOrFail();

        // 2. 更新
        $response = $this->actingAs($this->user)
            ->put(route('vendors.update', $vendor), [
                'name' => 'ハングリータイガー 本店',
                'contact_person' => '店長 田中',
                'contact' => '090-9999-8888',
                'notes' => '更新済み特記事項',
            ]);

        $response->assertRedirect(route('vendors.index'));
        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => 'ハングリータイガー 本店',
            'contact_person' => '店長 田中',
            'contact' => '090-9999-8888',
        ]);

        // 3. 削除
        $response = $this->actingAs($this->user)
            ->delete(route('vendors.destroy', $vendor));

        $response->assertRedirect(route('vendors.index'));
        $this->assertDatabaseMissing('vendors', [
            'id' => $vendor->id,
        ]);
    }

    /**
     * 1つの出展・出店者が複数コマ（ブース）に異なる展示・品目・形態で配置できること
     */
    public function test_vendor_multiple_booths(): void
    {
        $timetable = Timetable::factory()->create([
            'created_by' => $this->user->id,
        ]);

        $map = LayoutMap::create([
            'timetable_id' => $timetable->id,
            'name' => 'メイン配置図',
            'version_tag' => 'v1.0',
        ]);

        $areaA = LayoutArea::create([
            'layout_map_id' => $map->id,
            'zone_code' => 'ZONE_A',
            'name' => 'メイン通りA列',
            'display_order' => 1,
        ]);

        $areaPark = LayoutArea::create([
            'layout_map_id' => $map->id,
            'zone_code' => 'AREA_KITAGAWA',
            'name' => '北川駐車場内',
            'display_order' => 2,
        ]);

        $vendor = Vendor::create([
            'name' => '水浅青果',
            'contact_person' => '水浅 太郎',
            'contact' => '090-1111-2222',
        ]);

        // コマ1: A01（テント・調理済品・新鮮野菜）
        $booth1 = LayoutBooth::create([
            'layout_map_id' => $map->id,
            'layout_area_id' => $areaA->id,
            'vendor_id' => $vendor->id,
            'booth_code' => 'A01',
            'name' => '水浅青果 本店',
            'category' => BoothCategory::GozaCookedFood,
            'booth_type' => BoothType::Tent,
            'status' => BoothStatus::Confirmed,
            'product_items' => '新鮮野菜、果物詰め合わせ',
        ]);

        // コマ2: A03（テント・調理済品・漬物加工品）
        $booth2 = LayoutBooth::create([
            'layout_map_id' => $map->id,
            'layout_area_id' => $areaA->id,
            'vendor_id' => $vendor->id,
            'booth_code' => 'A03',
            'name' => '水浅青果 加工品',
            'category' => BoothCategory::GozaCookedFood,
            'booth_type' => BoothType::Tent,
            'status' => BoothStatus::Confirmed,
            'product_items' => '漬物、乾燥野菜加工品',
        ]);

        // コマ3: KC-03（キッチンカー・企業ブース・生搾りジュース）
        $booth3 = LayoutBooth::create([
            'layout_map_id' => $map->id,
            'layout_area_id' => $areaPark->id,
            'vendor_id' => $vendor->id,
            'booth_code' => 'KC-03',
            'name' => '水浅青果 ジュースカー',
            'category' => BoothCategory::CorporateBooth,
            'booth_type' => BoothType::KitchenCar,
            'status' => BoothStatus::Confirmed,
            'product_items' => '生搾り果実ジュース、カット果物',
        ]);

        // リレーションの検証 (1:N)
        $this->assertCount(3, $vendor->fresh()->booths);

        // 出展・出店者一覧画面では配置ブース（展開ブース）列が表示されないこと
        $response = $this->actingAs($this->user)
            ->get(route('vendors.index'));

        $response->assertStatus(200);
        $response->assertDontSee('展開ブース');
        $response->assertDontSee('3箇所');
        $response->assertDontSee('A01');
        $response->assertDontSee('A03');
        $response->assertDontSee('KC-03');
        $response->assertDontSee('配置ブース一覧');
    }
}
