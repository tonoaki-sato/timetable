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
            'name' => '商店街平面図 260813版 A3 1/400',
            'cad_version' => '260813',
            'paper_size' => 'A3',
            'scale_ratio' => 26.000,
            'width_meters' => 32.4,
            'height_meters' => 45.8,
            'svg_path' => 'maps/shopping_street_base_map_260813.svg',
            'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
            'viewbox' => '0 0 842 1191',
            'is_active' => true,
        ]);

        // アイコン定義マスターをシーディング
        $this->seed(SiteMapIconDefinitionSeeder::class);
    }

    /**
     * エディタ画面にパレットアイテムが正しくレンダリングされる
     */
    public function test_editor_renders_icon_palette_items(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $this->timetable));

        $response->assertStatus(200);

        // パレットコンテナ
        $response->assertSee('配置パレット');
        $response->assertSee('一般テント');
        $response->assertSee('露店 (縦長)');
        $response->assertSee('フリーエリア');
        $response->assertSee('給水設備');
        $response->assertSee('トイレ');
        $response->assertSee('data-shape-type="general_tent"', false);
        $response->assertSee('data-shape-type="stall_open"', false);
        $response->assertSee('data-shape-type="free_area"', false);
        $response->assertSee('draggable="true"', false);
    }

    /**
     * 新規配置をドラッグ＆ドロップAPIで保存できる
     */
    public function test_can_store_new_placement_via_api(): void
    {
        $payload = [
            'shape_type' => 'general_tent',
            'pos_x' => 250.50,
            'pos_y' => 350.25,
            'width' => 78.00,
            'height' => 78.00,
            'display_text' => 'A01',
            'color_theme' => 'red',
            'rotation' => 0,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('timetables.site-maps.placements.store', $this->timetable), $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'placement' => [
                    'site_base_map_id' => $this->siteBaseMap->id,
                    'shape_type' => 'general_tent',
                    'display_text' => 'A01',
                    'color_theme' => 'red',
                ],
            ]);

        $this->assertDatabaseHas('site_map_placements', [
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'general_tent',
            'display_text' => 'A01',
            'color_theme' => 'red',
        ]);
    }

    /**
     * 既存の配置データが一覧APIおよび画面初期データに含まれる
     */
    public function test_placements_are_listed_and_embedded(): void
    {
        $placement = SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'stall_open',
            'display_text' => 'B08',
            'color_theme' => 'green',
            'pos_x' => 120.00,
            'pos_y' => 200.00,
            'width' => 39.00,
            'height' => 78.00,
            'rotation' => 0,
        ]);

        // API確認
        $apiResponse = $this->actingAs($this->user)
            ->getJson(route('timetables.site-maps.placements.index', $this->timetable));

        $apiResponse->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonFragment([
                'id' => $placement->id,
                'display_text' => 'B08',
            ]);

        // 画面埋め込み確認
        $viewResponse = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $this->timetable));

        $viewResponse->assertStatus(200);
        $viewResponse->assertSee('B08');
        $viewResponse->assertSee('配置数: 1件');
    }

    /**
     * バリデーションエラー時は422を返す
     */
    public function test_store_placement_validation_error(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('timetables.site-maps.placements.store', $this->timetable), [
                // 必須の shape_type, pos_x, pos_y が未指定
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shape_type', 'pos_x', 'pos_y']);
    }

    /**
     * 配置の位置・寸法・回転をAPIで更新できる
     */
    public function test_can_update_placement_position_via_api(): void
    {
        $placement = SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'general_tent',
            'display_text' => 'A01',
            'color_theme' => 'red',
            'pos_x' => 100.00,
            'pos_y' => 150.00,
            'width' => 21.26,
            'height' => 21.26,
            'rotation' => 0,
        ]);

        $updatePayload = [
            'pos_x' => 135.50,
            'pos_y' => 180.25,
            'rotation' => 45,
        ];

        $response = $this->actingAs($this->user)
            ->putJson(route('timetables.site-maps.placements.update', [$this->timetable, $placement]), $updatePayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'placement' => [
                    'id' => $placement->id,
                    'pos_x' => '135.50',
                    'pos_y' => '180.25',
                    'rotation' => 45,
                ],
            ]);

        $this->assertDatabaseHas('site_map_placements', [
            'id' => $placement->id,
            'pos_x' => 135.50,
            'pos_y' => 180.25,
            'rotation' => 45,
        ]);
    }

    /**
     * モーダル経由でテキスト・カラー・ブース台帳紐づけ・特記事項を更新できる (Phase 4)
     */
    public function test_can_update_placement_modal_settings_via_api(): void
    {
        $placement = SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'stall_open',
            'display_text' => '初期値',
            'color_theme' => 'red',
            'pos_x' => 100.00,
            'pos_y' => 150.00,
            'width' => 6.24,
            'height' => 12.48,
            'rotation' => 0,
        ]);

        $updatePayload = [
            'display_text' => 'B22',
            'color_theme' => 'green',
            'rotation' => 90,
            'notes' => '電源ドラム・消火器配置済み',
        ];

        $response = $this->actingAs($this->user)
            ->putJson(route('timetables.site-maps.placements.update', [$this->timetable, $placement]), $updatePayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'placement' => [
                    'id' => $placement->id,
                    'display_text' => 'B22',
                    'color_theme' => 'green',
                    'rotation' => 90,
                    'notes' => '電源ドラム・消火器配置済み',
                ],
            ]);

        $this->assertDatabaseHas('site_map_placements', [
            'id' => $placement->id,
            'display_text' => 'B22',
            'color_theme' => 'green',
            'rotation' => 90,
            'notes' => '電源ドラム・消火器配置済み',
        ]);
    }

    /**
     * 白地図エディタ画面にオブジェクト設定モーダルとブース台帳データが埋め込まれている (Phase 4)
     */
    public function test_editor_renders_modal_and_layout_items(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $this->timetable));

        $response->assertStatus(200);
        $response->assertSee('site-map-placement-modal');
        $response->assertSee('layout-items-data');
        $response->assertSee('modal-display-text');
        $response->assertSee('modal-color-palette');
        $response->assertSee('modal-rotation');
        $response->assertSee('btn-rotate-selected');
    }

    /**
     * フリーエリアの寸法伸縮（リサイズ）をAPIで更新できる (Phase 5)
     */
    public function test_can_update_free_area_dimensions_via_api(): void
    {
        $placement = SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'free_area',
            'display_text' => 'ミニバス体験',
            'color_theme' => 'blue',
            'pos_x' => 450.00,
            'pos_y' => 300.00,
            'width' => 78.00,
            'height' => 78.00,
            'rotation' => 0,
        ]);

        $updatePayload = [
            'pos_x' => 440.00,
            'pos_y' => 290.00,
            'width' => 120.50,
            'height' => 95.00,
        ];

        $response = $this->actingAs($this->user)
            ->putJson(route('timetables.site-maps.placements.update', [$this->timetable, $placement]), $updatePayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'placement' => [
                    'id' => $placement->id,
                    'pos_x' => '440.00',
                    'pos_y' => '290.00',
                    'width' => '120.50',
                    'height' => '95.00',
                ],
            ]);

        $this->assertDatabaseHas('site_map_placements', [
            'id' => $placement->id,
            'pos_x' => 440.00,
            'pos_y' => 290.00,
            'width' => 120.50,
            'height' => 95.00,
        ]);
    }

    /**
     * 通り沿い配置時に回転ロック（is_rotation_locked=true）と角度リセットを更新できる (Phase 5)
     */
    public function test_can_lock_rotation_when_along_street_via_api(): void
    {
        $placement = SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'general_tent',
            'display_text' => '本部前テント',
            'color_theme' => 'red',
            'pos_x' => 265.00,
            'pos_y' => 400.00,
            'width' => 21.26,
            'height' => 21.26,
            'rotation' => 45,
            'is_rotation_locked' => false,
        ]);

        $updatePayload = [
            'rotation' => 0,
            'is_rotation_locked' => true,
        ];

        $response = $this->actingAs($this->user)
            ->putJson(route('timetables.site-maps.placements.update', [$this->timetable, $placement]), $updatePayload);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'placement' => [
                    'id' => $placement->id,
                    'rotation' => 0,
                    'is_rotation_locked' => true,
                ],
            ]);

        $this->assertDatabaseHas('site_map_placements', [
            'id' => $placement->id,
            'rotation' => 0,
            'is_rotation_locked' => true,
        ]);
    }

    /**
     * 配置をAPIで削除できる
     */
    public function test_can_delete_placement_via_api(): void
    {
        $placement = SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'general_tent',
            'display_text' => 'A02',
            'color_theme' => 'blue',
            'pos_x' => 200.00,
            'pos_y' => 250.00,
            'width' => 21.26,
            'height' => 21.26,
            'rotation' => 0,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson(route('timetables.site-maps.placements.destroy', [$this->timetable, $placement]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('site_map_placements', [
            'id' => $placement->id,
        ]);
    }
}
