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
        $response->assertSee('右クリック: 選択/手のひら切替');
        $response->assertSee('選択・移動モード (Vキー / 右クリック)');
        $response->assertSee('手のひら移動モード (Hキー / Space押下 / 右クリック)');
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
     * 配置オブジェクトがない状態の PDF エクスポートでも旧ブース矩形枠線が描画されないことを検証
     */
    public function test_export_pdf_has_no_residual_booth_rectangles(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.pdf', $this->timetable));

        $response->assertStatus(200);
        $content = $response->getContent();
        $this->assertStringStartsWith('%PDF-', $content);

        // PDFの描画ストリームを解凍して検証
        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $streamMatches);
        $decompressed = '';
        foreach ($streamMatches[1] as $s) {
            $decomp = @gzuncompress($s);
            if ($decomp !== false) {
                $decompressed .= $decomp;
            }
        }

        // 旧ブース矩形（更地前・国際会館前等の線分）がストリームに含まれていないことを検証
        $this->assertStringNotContainsString('313.440 961.380', $decompressed);
        $this->assertStringNotContainsString('313.440 975.240', $decompressed);
        $this->assertStringNotContainsString('358.800 1040.340', $decompressed);
        $this->assertStringNotContainsString('358.800 1054.140', $decompressed);
        $this->assertStringNotContainsString('730.440 939.780', $decompressed);
    }

    /**
     * PDFエクスポート時に日本語および英数字・記号（WANDA、ECLAT、A01、1/400等）が正しく描画されることを検証
     */
    public function test_export_pdf_renders_japanese_and_ascii_text_correctly(): void
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

        $response = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.pdf', $this->timetable));

        $response->assertStatus(200);
        $content = $response->getContent();

        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $streamMatches);
        $decompressed = '';
        foreach ($streamMatches[1] as $s) {
            $decomp = @gzuncompress($s);
            if ($decomp !== false) {
                $decompressed .= $decomp;
            }
        }

        // 半角英数字・記号が Helvetica で正しく出力され、未定義グリフ（豆腐化）していないことを検証
        $this->assertStringContainsString('(WANDA)', $decompressed);
        $this->assertStringContainsString('(ECLAT)', $decompressed);
        $this->assertStringContainsString('(POLA)', $decompressed);
        $this->assertStringContainsString('(1/400)', $decompressed);
        $this->assertStringContainsString('(A3)', $decompressed);
        $this->assertStringContainsString('(A01)', $decompressed);

        // 日本語（西村アパート等のUTF-16BEエンコード）も正常に含まれていることを検証
        $nishimuraHex = "\x89\x7F\x67\x51"; // 「西村」の UTF-16BE
        $this->assertStringContainsString($nishimuraHex, $decompressed);
    }

    /**
     * 日本語フォントと英数字フォント（Helvetica）のベースラインが同期され、上下の文字重なりやガタつきが生じないことを検証
     */
    public function test_export_pdf_aligns_font_baselines_without_overlap(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.pdf', $this->timetable));

        $response->assertStatus(200);
        $content = $response->getContent();

        preg_match_all('/stream[\r\n]+(.*?)[\r\n]+endstream/s', $content, $streamMatches);
        $decompressed = '';
        foreach ($streamMatches[1] as $s) {
            $decomp = @gzuncompress($s);
            if ($decomp !== false) {
                $decompressed .= $decomp;
            }
        }

        // まるお酒場 (UTF-16BE: 307e308b304a91525834), WANDA, ECLAT の y 座標を抽出
        // 例: BT 378.060 778.171 Td /F1 8.3 Tf  [(0~0...)] TJ ET
        // バイナリ列を含むため /u フラグは付けず /s のみでマッチさせる
        $maruoBinary = "\x30\x7E\x30\x8B\x30\x4A\x91\x52\x58\x34";
        preg_match('/BT 378\.060 ([\d\.]+) Td \/F1 8\.3 Tf\s+\[\('.preg_quote($maruoBinary, '/').'\)\]/s', $decompressed, $mMaruo);
        preg_match('/BT 378\.060 ([\d\.]+) Td \/F2 8\.3 Tf\s+\[\(\x57\x41\x4E\x44\x41\)\]/s', $decompressed, $mWanda);
        preg_match('/BT 378\.060 ([\d\.]+) Td \/F2 8\.3 Tf\s+\[\(\x45\x43\x4C\x41\x54\)\]/s', $decompressed, $mEclat);

        $this->assertNotEmpty($mMaruo, 'まるお酒場の描画コマンドが見つかりません');
        $this->assertNotEmpty($mWanda, 'WANDAの描画コマンドが見つかりません');
        $this->assertNotEmpty($mEclat, 'ECLATの描画コマンドが見つかりません');

        $yMaruo = (float) $mMaruo[1];
        $yWanda = (float) $mWanda[1];
        $yEclat = (float) $mEclat[1];

        // まるお酒場とWANDAの間隔、WANDAとECLATの間隔がどちらも 8.28pt（等間隔）で重なっていないことを検証
        $this->assertEqualsWithDelta(8.28, $yMaruo - $yWanda, 0.05, 'まるお酒場とWANDAの行間隔が8.28pt等間隔になっていません');
        $this->assertEqualsWithDelta(8.28, $yWanda - $yEclat, 0.05, 'WANDAとECLATの行間隔が8.28pt等間隔になっていません');

        // タイトル行「保土ヶ谷宿場まつり ... 1/400 A3」のベースラインy座標が日本語と英数字で完全に一致していることを検証
        preg_match('/BT ([\d\.]+) ([\d\.]+) Td \/F2 10\.5 Tf\s+\[\(1\/400\)\]/', $decompressed, $mScale);
        preg_match('/BT ([\d\.]+) ([\d\.]+) Td \/F2 10\.5 Tf\s+\[\(A3\)\]/', $decompressed, $mPaper);

        $this->assertNotEmpty($mScale, '1/400の描画コマンドが見つかりません');
        $this->assertNotEmpty($mPaper, 'A3の描画コマンドが見つかりません');
        $this->assertEqualsWithDelta(39.746, (float) $mScale[2], 0.05, '1/400のベースラインが日本語と揃っていません');
        $this->assertEqualsWithDelta(39.746, (float) $mPaper[2], 0.05, 'A3のベースラインが日本語と揃っていません');
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
