<?php

namespace Tests\Feature;

use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Models\User;
use Database\Seeders\SiteMapIconDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteMapPlacementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Timetable $timetable;

    protected SiteBaseMap $siteBaseMap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'name' => '2026年本祭 1日目',
            'target_date' => '2026-10-18',
            'created_by' => $this->user->id,
        ]);

        $this->siteBaseMap = SiteBaseMap::create([
            'timetable_id' => $this->timetable->id,
            'name' => '保土ヶ谷宿場まつり 平面図 1/400 A3',
            'cad_version' => '260927',
            'paper_size' => 'A3',
            'scale_ratio' => 7.0866,
            'width_meters' => 118.8,
            'height_meters' => 168.0,
            'svg_path' => 'maps/shopping_street_base_map_260813.svg',
            'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
            'viewbox' => '0 0 842 1191',
            'is_active' => true,
        ]);

        $this->seed(SiteMapIconDefinitionSeeder::class);
    }

    /**
     * エディタ画面に3ペインUI・CAD図形パレット・インスペクタ・PDFダウンロードボタンがレンダリングされる
     */
    public function test_editor_renders_three_pane_cad_ui(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $this->timetable));

        $response->assertStatus(200);
        $response->assertSee('図形パレット');
        $response->assertSee('通り標準ブース');
        $response->assertSee('通りテントブース');
        $response->assertSee('洗浄・手洗い設備');
        $response->assertSee('フリーエリア');
        $response->assertSee('プロパティ・インスペクタ');
        $response->assertSee('PDFダウンロード (A3 1/400)');
        $response->assertSee('data-shape-type="booth_standard"', false);
        $response->assertSee('data-shape-type="booth_with_tent"', false);
        $response->assertSee('data-shape-type="water_supply"', false);
        $response->assertSee('data-shape-type="free_area"', false);
        $response->assertSee('btn-export-pdf', false);
        $response->assertSee('btn-pack-vert', false);
        $response->assertSee('insp-display-text', false);
        $response->assertSee('btn-open-create-icon-modal', false);
        $response->assertSee('modal-create-icon', false);
        $response->assertSee('modal-edit-icon', false);
        $response->assertSee('modal-delete-icon', false);
    }

    /**
     * 新規配置をAPIで保存でき、シンボル定義の初期値が適用される
     */
    public function test_can_store_new_placement_via_api(): void
    {
        $payload = [
            'shape_type' => 'booth_standard',
            'pos_x' => 353.94,
            'pos_y' => 123.72,
            'width' => 13.86,
            'height' => 13.86,
            'display_text' => 'A51',
            'color_theme' => 'red',
            'rotation' => 0,
            'is_rotation_locked' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('timetables.site-maps.placements.store', $this->timetable), $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'placement' => [
                    'site_base_map_id' => $this->siteBaseMap->id,
                    'shape_type' => 'booth_standard',
                    'display_text' => 'A51',
                    'color_theme' => 'red',
                    'is_rotation_locked' => true,
                ],
            ]);

        $this->assertDatabaseHas('site_map_placements', [
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'A51',
        ]);
    }

    /**
     * 給水設備をデフォルトカラー未指定で追加した場合、青(blue)が自動選択される
     */
    public function test_water_supply_defaults_to_blue_color(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('timetables.site-maps.placements.store', $this->timetable), [
                'shape_type' => 'water_supply',
                'pos_x' => 360.36,
                'pos_y' => 274.14,
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'placement' => [
                    'shape_type' => 'water_supply',
                    'color_theme' => 'blue',
                    'width' => '6.24',
                    'height' => '6.24',
                ],
            ]);
    }

    /**
     * 既存の配置データが一覧APIおよび画面初期データに含まれる
     */
    public function test_placements_are_listed_and_embedded(): void
    {
        $placement = SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'B08',
            'color_theme' => 'green',
            'pos_x' => 681.36,
            'pos_y' => 590.58,
            'width' => 13.86,
            'height' => 13.86,
            'rotation' => 0,
        ]);

        $apiResponse = $this->actingAs($this->user)
            ->getJson(route('timetables.site-maps.placements.index', $this->timetable));

        $apiResponse->assertStatus(200)
            ->assertJson(['success' => true])
            ->assertJsonFragment(['id' => $placement->id, 'display_text' => 'B08']);

        $viewResponse = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $this->timetable));

        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('B08');
    }

    /**
     * 配置の位置・寸法・回転・属性をAPIで更新できる
     */
    public function test_can_update_placement_via_api(): void
    {
        $placement = SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'free_area',
            'display_text' => 'ミニバス体験',
            'color_theme' => 'black',
            'pos_x' => 98.70,
            'pos_y' => 645.42,
            'width' => 89.88,
            'height' => 55.32,
            'rotation' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson(route('timetables.site-maps.placements.update', [$this->timetable, $placement]), [
                'pos_x' => 105.00,
                'width' => 100.50,
                'display_text' => 'ミニバス体験スペース',
                'color_theme' => 'cyan',
                'rotation' => 45,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'placement' => [
                    'id' => $placement->id,
                    'pos_x' => '105.00',
                    'width' => '100.50',
                    'display_text' => 'ミニバス体験スペース',
                    'color_theme' => 'cyan',
                    'rotation' => 45,
                ],
            ]);
    }

    /**
     * 複数の配置を一括整列更新できる (bulkUpdatePlacements)
     */
    public function test_can_bulk_update_placements_via_api(): void
    {
        $p1 = SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'A01',
            'pos_x' => 725.00,
            'pos_y' => 1000.00,
            'width' => 13.86,
            'height' => 13.86,
        ]);
        $p2 = SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'A02',
            'pos_x' => 728.00,
            'pos_y' => 1020.00,
            'width' => 13.86,
            'height' => 13.86,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson(route('timetables.site-maps.placements.bulk-update', $this->timetable), [
                'items' => [
                    ['id' => $p1->id, 'pos_x' => 725.58, 'pos_y' => 1000.00, 'color_theme' => 'green'],
                    ['id' => $p2->id, 'pos_x' => 725.58, 'pos_y' => 1013.86, 'color_theme' => 'green'],
                ],
            ]);

        $response->assertStatus(200)->assertJson(['success' => true]);

        $this->assertDatabaseHas('site_map_placements', ['id' => $p1->id, 'pos_x' => 725.58, 'color_theme' => 'green']);
        $this->assertDatabaseHas('site_map_placements', ['id' => $p2->id, 'pos_x' => 725.58, 'pos_y' => 1013.86]);
    }

    /**
     * 配置をAPIで削除できる
     */
    public function test_can_delete_placement_via_api(): void
    {
        $placement = SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'A02',
            'pos_x' => 725.58,
            'pos_y' => 980.00,
            'width' => 13.86,
            'height' => 13.86,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('timetables.site-maps.placements.destroy', [$this->timetable, $placement]));

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertDatabaseMissing('site_map_placements', ['id' => $placement->id]);
    }

    /**
     * 標準見本配置プリセット（111件）へ復元できる
     */
    public function test_can_reset_to_standard_preset_via_api(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('timetables.site-maps.reset-preset', $this->timetable));

        $response->assertStatus(200)->assertJson(['success' => true]);
        $this->assertGreaterThan(100, $this->siteBaseMap->placements()->count());
        $this->assertDatabaseHas('site_map_placements', [
            'site_base_map_id' => $this->siteBaseMap->id,
            'display_text' => 'A51',
        ]);
    }

    /**
     * 編集済みの白地図配置を A3 1/400 ベクターPDF としてダウンロードできる
     */
    public function test_can_export_a3_vector_pdf(): void
    {
        SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'A01',
            'color_theme' => 'red',
            'pos_x' => 725.58,
            'pos_y' => 1017.60,
            'width' => 13.86,
            'height' => 13.86,
        ]);
        SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'water_supply',
            'display_text' => '水1',
            'color_theme' => 'blue',
            'pos_x' => 360.36,
            'pos_y' => 274.14,
            'width' => 6.24,
            'height' => 6.24,
            'show_hygiene_circle' => true,
            'arc_start_angle' => 90,
            'arc_end_angle' => 270,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.pdf', $this->timetable));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertGreaterThan(20000, strlen($content));
    }

    /**
     * バリデーションエラー時は422を返す
     */
    public function test_store_placement_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('timetables.site-maps.placements.store', $this->timetable), []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shape_type', 'pos_x', 'pos_y']);
    }

    /**
     * booth_standard と booth_with_tent は初期テキスト省略時に x00 が設定される
     */
    public function test_default_display_text_is_x00_for_booths(): void
    {
        // 1. booth_standard (通り標準ブース): 省略時に x00, default_color は black
        $resStd = $this->actingAs($this->user)
            ->postJson(route('timetables.site-maps.placements.store', $this->timetable), [
                'shape_type' => 'booth_standard',
                'pos_x' => 100.0,
                'pos_y' => 200.0,
            ]);
        $resStd->assertStatus(201);
        $this->assertSame('x00', $resStd->json('placement.display_text'));
        $this->assertSame('black', $resStd->json('placement.color_theme'));

        // 2. booth_with_tent (通りテントブース): 省略時に x00, default_color は red
        $resTent = $this->actingAs($this->user)
            ->postJson(route('timetables.site-maps.placements.store', $this->timetable), [
                'shape_type' => 'booth_with_tent',
                'pos_x' => 120.0,
                'pos_y' => 220.0,
            ]);
        $resTent->assertStatus(201);
        $this->assertSame('x00', $resTent->json('placement.display_text'));
        $this->assertSame('red', $resTent->json('placement.color_theme'));
    }

    /**
     * デフォルト表示では配置オブジェクトが存在せず、0件の白地図のみが表示される
     */
    public function test_editor_displays_empty_placements_by_default(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $this->timetable));

        $response->assertStatus(200);

        // 配置オブジェクトのカウントが 0件であることを検証
        $this->assertSame(0, $this->siteBaseMap->placements()->count());

        // 「標準見本配置に復元」および「全配置をクリア」ボタンが存在することを検証
        $response->assertSee('id="btn-reset-preset"', false);
        $response->assertSee('id="btn-clear-placements"', false);
        $response->assertSee('全配置をクリア');
    }

    /**
     * 全配置オブジェクトを一括削除（クリア）できる
     */
    public function test_can_clear_all_placements_via_api(): void
    {
        // 事前に2件の配置を作成
        SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'B01',
            'color_theme' => 'black',
            'pos_x' => 100.0,
            'pos_y' => 150.0,
            'width' => 6.24,
            'height' => 12.48,
        ]);
        SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'booth_with_tent',
            'display_text' => 'A01',
            'color_theme' => 'red',
            'pos_x' => 200.0,
            'pos_y' => 250.0,
            'width' => 13.86,
            'height' => 13.86,
        ]);

        $this->assertSame(2, $this->siteBaseMap->placements()->count());

        // クリアAPIを実行
        $response = $this->actingAs($this->user)
            ->postJson(route('timetables.site-maps.clear-placements', $this->timetable));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'placements' => [],
            ]);

        $this->assertSame(0, $this->siteBaseMap->placements()->count());
    }
}
