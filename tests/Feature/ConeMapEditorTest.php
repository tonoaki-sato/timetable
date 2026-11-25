<?php

namespace Tests\Feature;

use App\Actions\Timetable\CloneTimetableAction;
use App\DTOs\Timetable\CloneTimetableOptionsDTO;
use App\Models\ConeMapBar;
use App\Models\ConeMapBarricade;
use App\Models\ConeMapCallout;
use App\Models\ConeMapCone;
use App\Models\Equipment;
use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConeMapEditorTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $operator;

    protected User $viewer;

    protected Timetable $timetable;

    protected SiteBaseMap $siteBaseMap;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);
        $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);

        $this->siteBaseMap = SiteBaseMap::create([
            'name' => '保土ヶ谷宿場まつり 平面図 1/400 A3',
            'cad_version' => '260813',
            'paper_size' => 'A3',
            'scale_ratio' => 7.0866,
            'width_meters' => 118.80,
            'height_meters' => 168.00,
            'svg_path' => 'maps/shopping_street_base_map_260813.svg',
            'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
            'viewbox' => '0 0 842 1191',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->timetable = Timetable::factory()->create([
            'name' => '2026年本祭 1日目',
            'target_date' => '2026-10-18',
            'site_base_map_id' => $this->siteBaseMap->id,
            'created_by' => $this->admin->id,
        ]);
    }

    public function test_editor_renders_three_pane_ui_with_strict_csp_and_units(): void
    {
        $response = $this->actingAs($this->operator)
            ->get(route('timetables.cone-maps.editor', $this->timetable));

        $response->assertStatus(200)
            ->assertSee('カラーコーン・バリケード配置エディタ')
            ->assertSee('カラーコーン (単独)')
            ->assertSee('H70cm / 38.8×38.8cm')
            ->assertSee('コーン＋バー連結配置')
            ->assertSee('バー L200cm / φ3.4cm')
            ->assertSee('バー本数＝コーン個数−1')
            ->assertSee('A型バリケード')
            ->assertSee('120×46×H80cm')
            ->assertSee('樹脂製単管バリケード')
            ->assertSee('120×64×H82cm')
            ->assertSee('吹き出し（注意事項）')
            ->assertSee('0個')
            ->assertSee('0本')
            ->assertSee('0台')
            ->assertSee('0件')
            ->assertSee('id="cone-map-editor-app"', false)
            ->assertDontSee('data-palette-kind="bar"', false);

        $content = $response->getContent();
        $this->assertTrue(
            str_contains($content, 'cone-map-editor.js') || str_contains($content, 'cone-map-editor-'),
            'cone-map-editor Vite asset must be loaded'
        );
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*(?:\bsrc=|\bnonce=))[^>]*>/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $content);
    }

    public function test_can_store_update_bulk_update_and_delete_cone_with_weight_sync(): void
    {
        $createRes = $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.cones.store', $this->timetable), [
                'pos_x' => 320.5,
                'pos_y' => 450.25,
                'color' => 'yellow',
                'label' => 'C01',
                'notes' => '角地注意',
            ]);

        $createRes->assertStatus(201)
            ->assertJson([
                'success' => true,
                'cone' => [
                    'color' => 'yellow',
                    'label' => 'C01',
                ],
                'summary' => [
                    'cones_count' => 1,
                    'standalone_cones_count' => 1,
                    'linked_cones_count' => 0,
                    'weights_count' => 1,
                    'bars_count' => 0,
                ],
            ]);

        $coneId = (int) $createRes->json('cone.id');

        $cone2Res = $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.cones.store', $this->timetable), [
                'pos_x' => 340.0,
                'pos_y' => 450.25,
                'color' => 'red',
            ]);
        $cone2Id = (int) $cone2Res->json('cone.id');

        $this->actingAs($this->operator)
            ->putJson(route('timetables.cone-maps.cones.update', [$this->timetable, $coneId]), [
                'pos_x' => 325.0,
                'color' => 'blue',
                'label' => 'C01改',
            ])
            ->assertOk()
            ->assertJson([
                'success' => true,
                'cone' => ['color' => 'blue', 'label' => 'C01改'],
                'summary' => ['cones_count' => 2, 'weights_count' => 2],
            ]);

        $this->actingAs($this->operator)
            ->putJson(route('timetables.cone-maps.cones.bulk-update', $this->timetable), [
                'items' => [
                    ['id' => $coneId, 'pos_x' => 330.0, 'color' => 'green'],
                    ['id' => $cone2Id, 'pos_x' => 344.17, 'color' => 'green'],
                ],
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('cone_map_cones', ['id' => $coneId, 'color' => 'green', 'pos_x' => 330.00]);

        $this->actingAs($this->operator)
            ->deleteJson(route('timetables.cone-maps.cones.destroy', [$this->timetable, $coneId]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'summary' => ['cones_count' => 1, 'weights_count' => 1],
            ]);
    }

    public function test_cone_line_creates_k_cones_and_k_minus_one_bars_within_2m(): void
    {
        $startX = 300.0;
        $startY = 500.0;
        $endX = 300.0 + (5.0 * 7.0866); // 実寸 5.0m -> ceil(5/2)=3スパン -> コーン4個・バー3本
        $endY = 500.0;

        $response = $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.cone-lines.store', $this->timetable), [
                'start_x' => $startX,
                'start_y' => $startY,
                'end_x' => $endX,
                'end_y' => $endY,
                'color' => 'red',
                'bar_style' => 'yellow_black',
                'pitch_mode' => 'even_max_2m',
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'cones_count' => 4,
                    'standalone_cones_count' => 0,
                    'linked_cones_count' => 4,
                    'linked_groups_count' => 1,
                    'weights_count' => 4,
                    'bars_count' => 3,
                ],
            ]);

        $cones = $response->json('cones');
        $bars = $response->json('bars');
        $this->assertCount(4, $cones);
        $this->assertCount(3, $bars);

        // 各スパンが 2.00m 以内であることを検証
        for ($i = 0; $i < count($cones) - 1; $i++) {
            $distM = hypot($cones[$i + 1]['pos_x'] - $cones[$i]['pos_x'], $cones[$i + 1]['pos_y'] - $cones[$i]['pos_y']) / 7.0866;
            $this->assertLessThanOrEqual(2.01, $distM);
        }

        // 末端コーンから 2.00m 固定でさらに1本延長
        $lastConeId = (int) $cones[3]['id'];
        $extendRes = $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.cone-lines.store', $this->timetable), [
                'start_x' => $cones[3]['pos_x'],
                'start_y' => $cones[3]['pos_y'],
                'end_x' => $cones[3]['pos_x'] + 14.17,
                'end_y' => $cones[3]['pos_y'],
                'start_cone_id' => $lastConeId,
                'color' => 'red',
                'bar_style' => 'yellow_black',
                'pitch_mode' => 'fixed_2m',
            ]);

        $extendRes->assertStatus(201)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'cones_count' => 5,
                    'weights_count' => 5,
                    'bars_count' => 4, // 5 - 1 = 4本
                ],
            ]);
    }

    public function test_bar_creation_prevents_standalone_duplicate_and_loop_connections(): void
    {
        // 両端コーン未指定は 422（単独配置禁止）
        $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.bars.store', $this->timetable), [
                'bar_style' => 'yellow_black',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['start_cone_id', 'end_cone_id']);

        $c1 = ConeMapCone::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'pos_x' => 300.0,
            'pos_y' => 300.0,
        ]);
        $c2 = ConeMapCone::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'pos_x' => 314.17,
            'pos_y' => 300.0,
        ]);
        $c3 = ConeMapCone::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'pos_x' => 328.34,
            'pos_y' => 300.0,
        ]);

        // 同一コーン同士は 422
        $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.bars.store', $this->timetable), [
                'start_cone_id' => $c1->id,
                'end_cone_id' => $c1->id,
            ])
            ->assertStatus(422);

        // C1-C2 連結成功
        $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.bars.store', $this->timetable), [
                'start_cone_id' => $c1->id,
                'end_cone_id' => $c2->id,
            ])
            ->assertStatus(201);

        // C2-C1 重複連結は 422
        $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.bars.store', $this->timetable), [
                'start_cone_id' => $c2->id,
                'end_cone_id' => $c1->id,
            ])
            ->assertStatus(422);

        // C2-C3 連結成功
        $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.bars.store', $this->timetable), [
                'start_cone_id' => $c2->id,
                'end_cone_id' => $c3->id,
            ])
            ->assertStatus(201);

        // C1-C3 連結は閉路（ループ）になるため 422 拒否（バー本数は3-1=2本を維持）
        $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.bars.store', $this->timetable), [
                'start_cone_id' => $c1->id,
                'end_cone_id' => $c3->id,
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertSame(2, ConeMapBar::where('timetable_id', $this->timetable->id)->count());
    }

    public function test_deleting_cone_cascades_bars_and_moving_target_syncs_callout_anchor(): void
    {
        $c1 = ConeMapCone::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'pos_x' => 300.0,
            'pos_y' => 400.0,
        ]);
        $c2 = ConeMapCone::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'pos_x' => 314.0,
            'pos_y' => 400.0,
        ]);
        $bar = ConeMapBar::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'start_cone_id' => $c1->id,
            'end_cone_id' => $c2->id,
        ]);
        $barricade = ConeMapBarricade::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'barricade_type' => 'type_a',
            'pos_x' => 500.0,
            'pos_y' => 500.0,
        ]);

        $calloutCone = ConeMapCallout::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'target_cone_id' => $c1->id,
            'anchor_x' => 300.0,
            'anchor_y' => 400.0,
            'box_x' => 320.0,
            'box_y' => 370.0,
            'body' => 'コーン追従確認',
        ]);
        $calloutBarricade = ConeMapCallout::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'target_barricade_id' => $barricade->id,
            'anchor_x' => 500.0,
            'anchor_y' => 500.0,
            'box_x' => 520.0,
            'box_y' => 470.0,
            'body' => 'バリケード追従確認',
        ]);

        // コーン移動で吹き出しアンカーが自動追従
        $this->actingAs($this->operator)
            ->putJson(route('timetables.cone-maps.cones.update', [$this->timetable, $c1]), [
                'pos_x' => 305.5,
                'pos_y' => 410.25,
            ])
            ->assertOk();
        $this->assertDatabaseHas('cone_map_callouts', [
            'id' => $calloutCone->id,
            'anchor_x' => 305.50,
            'anchor_y' => 410.25,
        ]);

        // バリケード移動で吹き出しアンカーが自動追従
        $this->actingAs($this->operator)
            ->putJson(route('timetables.cone-maps.barricades.update', [$this->timetable, $barricade]), [
                'pos_x' => 515.0,
                'pos_y' => 525.0,
            ])
            ->assertOk();
        $this->assertDatabaseHas('cone_map_callouts', [
            'id' => $calloutBarricade->id,
            'anchor_x' => 515.00,
            'anchor_y' => 525.00,
        ]);

        // コーン削除で接続バーが連動削除
        $this->actingAs($this->operator)
            ->deleteJson(route('timetables.cone-maps.cones.destroy', [$this->timetable, $c1]))
            ->assertOk()
            ->assertJson([
                'success' => true,
                'deleted_bar_ids' => [$bar->id],
                'summary' => [
                    'cones_count' => 1,
                    'bars_count' => 0,
                ],
            ]);
        $this->assertDatabaseMissing('cone_map_bars', ['id' => $bar->id]);
    }

    public function test_barricade_crud_bulk_update_and_type_counts(): void
    {
        $resA = $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.barricades.store', $this->timetable), [
                'barricade_type' => 'type_a',
                'pos_x' => 400.0,
                'pos_y' => 600.0,
                'rotation' => 45,
                'label' => 'A-1',
            ]);
        $resA->assertStatus(201)
            ->assertJson([
                'success' => true,
                'barricade' => ['barricade_type' => 'type_a', 'rotation' => 45, 'label' => 'A-1'],
                'summary' => ['barricades_type_a_count' => 1, 'barricades_resin_pipe_count' => 0],
            ]);

        $resR = $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.barricades.store', $this->timetable), [
                'barricade_type' => 'resin_pipe',
                'pos_x' => 420.0,
                'pos_y' => 600.0,
                'rotation' => 90,
                'label' => 'R-1',
            ]);
        $resR->assertStatus(201)
            ->assertJson([
                'success' => true,
                'summary' => ['barricades_type_a_count' => 1, 'barricades_resin_pipe_count' => 1],
            ]);

        $idA = (int) $resA->json('barricade.id');
        $idR = (int) $resR->json('barricade.id');

        $this->actingAs($this->operator)
            ->putJson(route('timetables.cone-maps.barricades.bulk-update', $this->timetable), [
                'items' => [
                    ['id' => $idA, 'pos_x' => 405.0, 'rotation' => 135],
                    ['id' => $idR, 'pos_x' => 425.0],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseHas('cone_map_barricades', ['id' => $idA, 'pos_x' => 405.00, 'rotation' => 135]);

        $this->actingAs($this->operator)
            ->deleteJson(route('timetables.cone-maps.barricades.destroy', [$this->timetable, $idR]))
            ->assertOk()
            ->assertJson(['summary' => ['barricades_resin_pipe_count' => 0]]);
    }

    public function test_callout_crud_and_inventory_over_stock_warning(): void
    {
        Equipment::factory()->committee()->create([
            'name' => 'カラーコーン',
            'quantity' => 2,
            'is_approximate_quantity' => false,
            'unit' => '個',
        ]);
        Equipment::factory()->committee()->create([
            'name' => 'A型バリケード',
            'quantity' => 1,
            'is_approximate_quantity' => false,
            'unit' => '台',
        ]);

        // コーン3個・A型バリケード2台を配置して在庫超過状態を作る
        for ($i = 0; $i < 3; $i++) {
            ConeMapCone::create([
                'timetable_id' => $this->timetable->id,
                'site_base_map_id' => $this->siteBaseMap->id,
                'pos_x' => 300.0 + $i * 10,
                'pos_y' => 300.0,
            ]);
        }
        for ($i = 0; $i < 2; $i++) {
            ConeMapBarricade::create([
                'timetable_id' => $this->timetable->id,
                'site_base_map_id' => $this->siteBaseMap->id,
                'barricade_type' => 'type_a',
                'pos_x' => 400.0 + $i * 15,
                'pos_y' => 300.0,
            ]);
        }

        $calloutRes = $this->actingAs($this->operator)
            ->postJson(route('timetables.cone-maps.callouts.store', $this->timetable), [
                'anchor_x' => 310.0,
                'anchor_y' => 300.0,
                'box_x' => 330.0,
                'box_y' => 260.0,
                'title' => '通行止め厳守',
                'body' => '緊急車両のみ進入可',
                'color_theme' => 'red',
            ]);

        $calloutRes->assertStatus(201)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'cones_count' => 3,
                    'weights_count' => 3,
                    'barricades_type_a_count' => 2,
                    'callouts_count' => 1,
                    'inventory' => [
                        'cone' => [
                            'registered' => true,
                            'stock_quantity' => 2,
                            'used_quantity' => 3,
                            'remaining_quantity' => -1,
                            'is_over_stock' => true,
                            'unit' => '個',
                        ],
                        'barricade_type_a' => [
                            'registered' => true,
                            'stock_quantity' => 1,
                            'used_quantity' => 2,
                            'remaining_quantity' => -1,
                            'is_over_stock' => true,
                            'unit' => '台',
                        ],
                    ],
                ],
            ]);

        $calloutId = (int) $calloutRes->json('callout.id');

        $this->actingAs($this->operator)
            ->putJson(route('timetables.cone-maps.callouts.update', [$this->timetable, $calloutId]), [
                'body' => '17:00撤収開始',
                'color_theme' => 'blue',
            ])
            ->assertOk()
            ->assertJson(['callout' => ['body' => '17:00撤収開始', 'color_theme' => 'blue']]);

        $this->actingAs($this->operator)
            ->deleteJson(route('timetables.cone-maps.callouts.destroy', [$this->timetable, $calloutId]))
            ->assertOk()
            ->assertJson(['summary' => ['callouts_count' => 0]]);
    }

    public function test_multiple_timetables_isolated_and_viewer_forbidden(): void
    {
        $secondTimetable = Timetable::factory()->create([
            'name' => '2026年本祭 2日目',
            'target_date' => '2026-10-19',
            'site_base_map_id' => $this->siteBaseMap->id,
            'created_by' => $this->admin->id,
        ]);

        $otherCone = ConeMapCone::create([
            'timetable_id' => $secondTimetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'pos_x' => 350.0,
            'pos_y' => 350.0,
        ]);

        // scopeBindings による越境操作 404
        $this->actingAs($this->operator)
            ->putJson(route('timetables.cone-maps.cones.update', [$this->timetable, $otherCone]), ['pos_x' => 360.0])
            ->assertNotFound();
        $this->actingAs($this->operator)
            ->deleteJson(route('timetables.cone-maps.cones.destroy', [$this->timetable, $otherCone]))
            ->assertNotFound();

        // viewer は閲覧可・更新不可 (403)
        $this->actingAs($this->viewer)
            ->get(route('timetables.cone-maps.editor', $this->timetable))
            ->assertOk();
        $this->actingAs($this->viewer)
            ->postJson(route('timetables.cone-maps.cones.store', $this->timetable), [
                'pos_x' => 300.0,
                'pos_y' => 300.0,
            ])
            ->assertForbidden();
    }

    public function test_can_export_a3_vector_pdf_with_emphasis_and_booths_options(): void
    {
        $c1 = ConeMapCone::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'pos_x' => 320.0,
            'pos_y' => 420.0,
            'color' => 'red',
            'label' => 'C01',
        ]);
        $c2 = ConeMapCone::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'pos_x' => 334.17,
            'pos_y' => 420.0,
            'color' => 'yellow',
        ]);
        ConeMapBar::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'start_cone_id' => $c1->id,
            'end_cone_id' => $c2->id,
            'bar_style' => 'yellow_black',
        ]);
        ConeMapBarricade::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'barricade_type' => 'type_a',
            'pos_x' => 350.0,
            'pos_y' => 500.0,
            'rotation' => 45,
            'label' => 'AB1',
        ]);
        ConeMapBarricade::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'barricade_type' => 'resin_pipe',
            'pos_x' => 380.0,
            'pos_y' => 500.0,
            'rotation' => 90,
            'label' => 'RP1',
        ]);
        ConeMapCallout::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'target_cone_id' => $c1->id,
            'anchor_x' => 320.0,
            'anchor_y' => 420.0,
            'box_x' => 340.0,
            'box_y' => 380.0,
            'title' => 'NOTE',
            'body' => '通行止め解除時撤去',
            'color_theme' => 'amber',
        ]);
        SiteMapPlacement::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'A01',
            'pos_x' => 725.58,
            'pos_y' => 1017.60,
            'width' => 13.86,
            'height' => 13.86,
        ]);

        $response = $this->actingAs($this->viewer)
            ->get(route('timetables.cone-maps.pdf', [
                'timetable' => $this->timetable,
                'emphasis' => 1,
                'show_booths' => 1,
            ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertGreaterThan(20000, strlen($content));

        preg_match_all('/\/Length\s+(\d+)\s*>>\s*stream\r?\n/s', $content, $lengthMatches, PREG_OFFSET_CAPTURE);
        $decompressed = '';
        foreach ($lengthMatches[1] as $idx => $lenInfo) {
            $len = (int) $lenInfo[0];
            $start = $lengthMatches[0][$idx][1] + strlen($lengthMatches[0][$idx][0]);
            $streamBytes = substr($content, $start, $len);
            $decomp = @gzuncompress($streamBytes);
            if ($decomp !== false) {
                $decompressed .= $decomp;
            }
        }

        $this->assertStringContainsString('(NOTE)', $decompressed);
        $this->assertStringContainsString('(AB1)', $decompressed);
        $this->assertStringContainsString('(RP1)', $decompressed);
        $this->assertStringContainsString('(C01)', $decompressed);
        $this->assertDoesNotMatchRegularExpression('/\[\(\?{3,}\)\]/', $decompressed);

        // 実寸・ブース非表示モードでも正常生成
        $this->actingAs($this->viewer)
            ->get(route('timetables.cone-maps.pdf', [
                'timetable' => $this->timetable,
                'emphasis' => 0,
                'show_booths' => 0,
            ]))
            ->assertStatus(200)
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_base_map_deletion_is_blocked_when_used_by_cone_map(): void
    {
        $customMap = SiteBaseMap::factory()->create([
            'name' => '削除保護検証用マップ',
            'is_default' => false,
            'is_active' => true,
        ]);

        ConeMapBarricade::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $customMap->id,
            'barricade_type' => 'type_a',
            'pos_x' => 100.0,
            'pos_y' => 100.0,
        ]);

        $response = $this->actingAs($this->admin)
            ->deleteJson(route('site-base-maps.destroy', $customMap));

        $response->assertStatus(422);
        $this->assertDatabaseHas('site_base_maps', ['id' => $customMap->id]);
    }

    public function test_cloning_timetable_deep_copies_cone_map_with_remapped_ids(): void
    {
        $c1 = ConeMapCone::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'pos_x' => 300.0,
            'pos_y' => 300.0,
            'color' => 'red',
            'label' => 'SRC1',
        ]);
        $c2 = ConeMapCone::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'pos_x' => 314.17,
            'pos_y' => 300.0,
            'color' => 'yellow',
            'label' => 'SRC2',
        ]);
        $bar = ConeMapBar::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'start_cone_id' => $c1->id,
            'end_cone_id' => $c2->id,
            'bar_style' => 'red_white',
        ]);
        $barricade = ConeMapBarricade::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'barricade_type' => 'resin_pipe',
            'pos_x' => 450.0,
            'pos_y' => 450.0,
            'rotation' => 30,
        ]);
        ConeMapCallout::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'target_cone_id' => $c1->id,
            'anchor_x' => 300.0,
            'anchor_y' => 300.0,
            'box_x' => 320.0,
            'box_y' => 270.0,
            'body' => '複製テスト',
        ]);
        ConeMapCallout::create([
            'timetable_id' => $this->timetable->id,
            'site_base_map_id' => $this->siteBaseMap->id,
            'target_barricade_id' => $barricade->id,
            'anchor_x' => 450.0,
            'anchor_y' => 450.0,
            'box_x' => 470.0,
            'box_y' => 420.0,
            'body' => 'バリケード複製テスト',
        ]);

        $cloneAction = app(CloneTimetableAction::class);
        $result = $cloneAction->execute(
            [
                'target_date' => '2027-10-18',
                'name' => '2027年本祭 1日目',
                'description' => null,
                'start_time' => '09:00',
                'end_time' => '17:00',
                'status' => 'draft',
            ],
            new CloneTimetableOptionsDTO(
                sourceTimetableId: $this->timetable->id,
                copyLanes: false,
                copyLaneStaff: false,
                copyTasks: false,
                copyTaskAssignments: false,
                copyLogisticsPlans: false,
                copySiteLayouts: true,
            ),
            $this->admin,
        );

        /** @var Timetable $cloned */
        $cloned = $result['timetable'];
        $clonedCones = ConeMapCone::where('timetable_id', $cloned->id)->get()->keyBy('label');
        $clonedBars = ConeMapBar::where('timetable_id', $cloned->id)->get();
        $clonedBarricades = ConeMapBarricade::where('timetable_id', $cloned->id)->get();
        $clonedCallouts = ConeMapCallout::where('timetable_id', $cloned->id)->get();

        $this->assertCount(2, $clonedCones);
        $this->assertCount(1, $clonedBars);
        $this->assertCount(1, $clonedBarricades);
        $this->assertCount(2, $clonedCallouts);

        $clonedBar = $clonedBars->first();
        $this->assertNotEquals($bar->id, $clonedBar->id);
        $this->assertSame($clonedCones->get('SRC1')->id, $clonedBar->start_cone_id);
        $this->assertSame($clonedCones->get('SRC2')->id, $clonedBar->end_cone_id);

        $clonedConeCallout = $clonedCallouts->firstWhere('body', '複製テスト');
        $this->assertSame($clonedCones->get('SRC1')->id, $clonedConeCallout->target_cone_id);

        $clonedBarricadeCallout = $clonedCallouts->firstWhere('body', 'バリケード複製テスト');
        $this->assertSame($clonedBarricades->first()->id, $clonedBarricadeCallout->target_barricade_id);
    }
}
