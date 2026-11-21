<?php

namespace Tests\Feature;

use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Models\User;
use Database\Seeders\SiteMapIconDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SiteBaseMapMasterTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected User $operator;

    protected User $viewer;

    /**
     * @var array<int, string>
     */
    protected array $createdCustomFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $this->operator = User::factory()->create(['role' => 'operator', 'is_active' => true]);
        $this->viewer = User::factory()->create(['role' => 'viewer', 'is_active' => true]);
    }

    protected function tearDown(): void
    {
        $customDir = public_path('maps/custom');
        if (File::isDirectory($customDir)) {
            foreach (File::files($customDir) as $file) {
                if (str_starts_with($file->getFilename(), 'base_map_')) {
                    File::delete($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    /**
     * 白地図マスター一覧画面が専用Viteアセット・モーダルDOM・厳格CSP準拠で表示される
     */
    public function test_index_renders_cleanly_with_vite_script_modals_and_strict_csp(): void
    {
        $defaultMap = SiteBaseMap::factory()->asDefault()->create([
            'name' => '標準商店街平面図',
            'cad_version' => '260813',
        ]);
        $inactiveMap = SiteBaseMap::factory()->inactive()->create([
            'name' => '旧版平面図',
            'cad_version' => '250101',
        ]);
        $timetable = Timetable::factory()->create(['site_base_map_id' => $defaultMap->id]);
        SiteMapPlacement::create([
            'timetable_id' => $timetable->id,
            'site_base_map_id' => $defaultMap->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'A01',
            'pos_x' => 100.0,
            'pos_y' => 100.0,
        ]);

        $response = $this->actingAs($this->operator)->get(route('site-base-maps.index'));

        $response->assertOk()
            ->assertSee('白地図マスター')
            ->assertSee('標準商店街平面図')
            ->assertSee('旧版平面図')
            ->assertSee('デフォルト')
            ->assertSee('1日程')
            ->assertSee('1配置')
            ->assertSee('id="modal-site-base-map-detail"', false)
            ->assertSee('id="modal-site-base-map-form"', false)
            ->assertSee('id="btn-open-create-site-base-map"', false);

        $content = $response->getContent();
        $this->assertTrue(
            str_contains($content, 'site-base-maps.js') || str_contains($content, 'site-base-maps-'),
            'site-base-maps Vite script asset must be loaded'
        );
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*(?:\bsrc=|\bnonce=))[^>]*>/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $content);
    }

    /**
     * SVGファイルのアップロードでサニタイズ・viewBox自動抽出・maps/custom保存が行われる
     */
    public function test_can_create_base_map_by_uploading_and_sanitizing_svg(): void
    {
        $unsafeSvg = <<<'SVG'
<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 900">
    <script>alert("xss")</script>
    <foreignObject><body xmlns="http://www.w3.org/1999/xhtml"><p>bad</p></body></foreignObject>
    <rect x="10" y="10" width="580" height="880" fill="#ffffff" stroke="#1e293b" onclick="alert(1)" />
    <a href="javascript:alert(2)"><line x1="20" y1="20" x2="200" y2="200" stroke="#000000" /></a>
</svg>
SVG;
        $svgFile = UploadedFile::fake()->createWithContent('festival_2027.svg', $unsafeSvg);

        $response = $this->actingAs($this->operator)->post(route('site-base-maps.store'), [
            'name' => '商店街平面図 270801版',
            'cad_version' => '270801',
            'paper_size' => 'A3',
            'scale_ratio' => 7.0866,
            'width_meters' => 118.80,
            'height_meters' => 168.00,
            'svg_file' => $svgFile,
            'is_default' => '1',
            'is_active' => '1',
            'notes' => 'テストアップロード白地図',
        ]);

        $response->assertRedirect(route('site-base-maps.index'));

        $created = SiteBaseMap::where('name', '商店街平面図 270801版')->firstOrFail();
        $this->assertStringStartsWith('maps/custom/base_map_', $created->svg_path);
        $this->assertSame('0 0 600 900', $created->viewbox);
        $this->assertTrue($created->is_default);
        $this->assertTrue($created->is_active);
        $this->assertFileExists(public_path($created->svg_path));

        $savedContent = file_get_contents(public_path($created->svg_path));
        $this->assertStringNotContainsString('<script', strtolower($savedContent));
        $this->assertStringNotContainsString('foreignobject', strtolower($savedContent));
        $this->assertStringNotContainsString('onclick', strtolower($savedContent));
        $this->assertStringNotContainsString('javascript:', strtolower($savedContent));
        $this->assertStringContainsString('<rect', $savedContent);
    }

    /**
     * 既存SVGパス指定による登録およびパストラバーサル拒否（422）
     */
    public function test_can_create_with_existing_path_and_rejects_path_traversal(): void
    {
        $validRes = $this->actingAs($this->operator)->postJson(route('site-base-maps.store'), [
            'name' => '既存パス参照平面図',
            'cad_version' => '260813-copy',
            'paper_size' => 'A3',
            'scale_ratio' => 7.0866,
            'width_meters' => 118.80,
            'height_meters' => 168.00,
            'svg_path' => 'maps/shopping_street_base_map_260813.svg',
            'is_active' => '1',
        ]);

        $validRes->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('site_base_maps', ['name' => '既存パス参照平面図']);

        $badRes = $this->actingAs($this->operator)->postJson(route('site-base-maps.store'), [
            'name' => '不正パス平面図',
            'cad_version' => '999',
            'paper_size' => 'A3',
            'scale_ratio' => 7.0866,
            'width_meters' => 118.80,
            'height_meters' => 168.00,
            'svg_path' => 'maps/../index.php',
        ]);

        $badRes->assertStatus(422)->assertJsonValidationErrors(['svg_path']);
    }

    /**
     * 更新・is_default排他制御・SVG差し替え時の旧カスタム削除とプリセット保護
     */
    public function test_update_handles_default_exclusivity_and_asset_cleanup_safely(): void
    {
        $presetMap = SiteBaseMap::factory()->asDefault()->create([
            'name' => 'プリセット白地図',
            'svg_path' => 'maps/shopping_street_base_map_260813.svg',
        ]);

        // カスタムSVGを持つ白地図を作成
        $customSvg1 = UploadedFile::fake()->createWithContent('v1.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 500 500"><rect x="0" y="0" width="500" height="500"/></svg>');
        $this->actingAs($this->operator)->post(route('site-base-maps.store'), [
            'name' => 'カスタム白地図',
            'cad_version' => 'v1',
            'paper_size' => 'A3',
            'scale_ratio' => 7.0866,
            'width_meters' => 100.00,
            'height_meters' => 100.00,
            'svg_file' => $customSvg1,
            'is_default' => '0',
            'is_active' => '1',
        ]);
        $customMap = SiteBaseMap::where('name', 'カスタム白地図')->firstOrFail();
        $oldCustomPath = $customMap->svg_path;
        $this->assertFileExists(public_path($oldCustomPath));

        // カスタム白地図をデフォルトに変更＋SVG差し替え
        $customSvg2 = UploadedFile::fake()->createWithContent('v2.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 700 800"><line x1="0" y1="0" x2="700" y2="800"/></svg>');
        $updateRes = $this->actingAs($this->operator)->put(route('site-base-maps.update', $customMap), [
            'name' => 'カスタム白地図 v2',
            'cad_version' => 'v2',
            'paper_size' => 'A3',
            'scale_ratio' => 7.0866,
            'width_meters' => 110.00,
            'height_meters' => 120.00,
            'svg_file' => $customSvg2,
            'is_default' => '1',
            'is_active' => '1',
        ]);

        $updateRes->assertRedirect(route('site-base-maps.index'));
        $this->assertFalse($presetMap->fresh()->is_default);
        $this->assertTrue($customMap->fresh()->is_default);
        $this->assertFileDoesNotExist(public_path($oldCustomPath));
        $this->assertFileExists(public_path($customMap->fresh()->svg_path));

        // プリセットパスを持っていたレコードを別のパスに更新してもプリセットSVG本体は保護される
        $this->actingAs($this->operator)->putJson(route('site-base-maps.update', $presetMap), [
            'name' => 'プリセット改',
            'cad_version' => '260813',
            'paper_size' => 'A3',
            'scale_ratio' => 7.0866,
            'width_meters' => 118.80,
            'height_meters' => 168.00,
            'svg_path' => $customMap->fresh()->svg_path,
            'is_default' => '0',
            'is_active' => '1',
        ])->assertOk();
        $this->assertFileExists(public_path('maps/shopping_street_base_map_260813.svg'));
    }

    /**
     * 削除保護（デフォルト・運行日参照中・配置参照中は拒否、未使用非デフォルトは削除＆カスタムファイル削除）
     */
    public function test_delete_protection_and_cleanup_for_unused_custom_map(): void
    {
        $defaultMap = SiteBaseMap::factory()->asDefault()->create(['name' => '標準白地図']);
        $timetableUsedMap = SiteBaseMap::factory()->create(['name' => '日程参照中白地図']);
        Timetable::factory()->create(['site_base_map_id' => $timetableUsedMap->id]);

        $placementUsedMap = SiteBaseMap::factory()->create(['name' => '配置参照中白地図']);
        $otherTimetable = Timetable::factory()->create(['site_base_map_id' => $defaultMap->id]);
        SiteMapPlacement::create([
            'timetable_id' => $otherTimetable->id,
            'site_base_map_id' => $placementUsedMap->id,
            'shape_type' => 'booth_standard',
            'display_text' => 'A01',
        ]);

        $unusedSvg = UploadedFile::fake()->createWithContent('unused.svg', '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400"><rect x="0" y="0" width="400" height="400"/></svg>');
        $this->actingAs($this->operator)->post(route('site-base-maps.store'), [
            'name' => '未使用カスタム白地図',
            'cad_version' => 'tmp',
            'paper_size' => 'A4',
            'scale_ratio' => 7.0,
            'width_meters' => 50.0,
            'height_meters' => 50.0,
            'svg_file' => $unusedSvg,
        ]);
        $unusedMap = SiteBaseMap::where('name', '未使用カスタム白地図')->firstOrFail();
        $unusedSvgPath = $unusedMap->svg_path;
        $this->assertFileExists(public_path($unusedSvgPath));

        // 1. デフォルトは削除不可
        $this->actingAs($this->operator)->deleteJson(route('site-base-maps.destroy', $defaultMap))->assertStatus(422);
        // 2. 運行日程参照中は削除不可
        $this->actingAs($this->operator)->deleteJson(route('site-base-maps.destroy', $timetableUsedMap))->assertStatus(422);
        // 3. 配置参照中は削除不可
        $this->actingAs($this->operator)->deleteJson(route('site-base-maps.destroy', $placementUsedMap))->assertStatus(422);

        // 4. 未使用・非デフォルトは削除成功＆カスタムSVGファイルも削除
        $this->actingAs($this->operator)->deleteJson(route('site-base-maps.destroy', $unusedMap))
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('site_base_maps', ['id' => $unusedMap->id]);
        $this->assertFileDoesNotExist(public_path($unusedSvgPath));
    }

    /**
     * 権限検証: viewerは一覧閲覧のみ可で登録・更新・削除・白地図切替は403
     */
    public function test_authorization_prevents_viewer_from_mutating_base_maps(): void
    {
        $map = SiteBaseMap::factory()->asDefault()->create();
        $secondMap = SiteBaseMap::factory()->create();
        $timetable = Timetable::factory()->create(['site_base_map_id' => $map->id]);

        $this->actingAs($this->viewer)->get(route('site-base-maps.index'))->assertOk();
        $this->actingAs($this->viewer)->postJson(route('site-base-maps.store'), ['name' => 'NG'])->assertStatus(403);
        $this->actingAs($this->viewer)->putJson(route('site-base-maps.update', $map), ['name' => 'NG'])->assertStatus(403);
        $this->actingAs($this->viewer)->deleteJson(route('site-base-maps.destroy', $secondMap))->assertStatus(403);
        $this->actingAs($this->viewer)->patchJson(route('timetables.site-maps.base-map.update', $timetable), [
            'site_base_map_id' => $secondMap->id,
        ])->assertStatus(403);
    }

    /**
     * エディタでの使用白地図切替・配置分離・カスタムSVGでのPDF出力が正常動作する
     */
    public function test_editor_base_map_switch_isolates_placements_and_exports_custom_pdf(): void
    {
        $this->seed(SiteMapIconDefinitionSeeder::class);

        $defaultMap = SiteBaseMap::factory()->asDefault()->create(['name' => '標準白地図']);
        $inactiveMap = SiteBaseMap::factory()->inactive()->create(['name' => '無効白地図']);

        $customSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 600 800"><rect x="10" y="10" width="580" height="780" fill="#ffffff" stroke="#1e293b" stroke-width="1"/><line x1="20" y1="400" x2="580" y2="400" stroke="#334155" stroke-width="1"/></svg>';
        $this->actingAs($this->operator)->post(route('site-base-maps.store'), [
            'name' => '特設別会場白地図',
            'cad_version' => 'custom-1',
            'paper_size' => 'A4',
            'scale_ratio' => 7.0866,
            'width_meters' => 84.0,
            'height_meters' => 112.0,
            'svg_file' => UploadedFile::fake()->createWithContent('venue2.svg', $customSvg),
            'is_active' => '1',
        ]);
        $customMap = SiteBaseMap::where('name', '特設別会場白地図')->firstOrFail();

        $timetable = Timetable::factory()->create(['site_base_map_id' => $defaultMap->id]);

        // 標準白地図上に配置を作成
        $this->actingAs($this->operator)->postJson(route('timetables.site-maps.placements.store', $timetable), [
            'shape_type' => 'booth_standard',
            'display_text' => 'A01',
            'pos_x' => 100.0,
            'pos_y' => 100.0,
        ])->assertStatus(201);

        // 無効化された白地図への切替は422で拒否
        $this->actingAs($this->operator)->patchJson(route('timetables.site-maps.base-map.update', $timetable), [
            'site_base_map_id' => $inactiveMap->id,
        ])->assertStatus(422);

        // 特設別会場白地図へ切り替え
        $this->actingAs($this->operator)->patchJson(route('timetables.site-maps.base-map.update', $timetable), [
            'site_base_map_id' => $customMap->id,
        ])->assertOk()->assertJson([
            'success' => true,
            'site_base_map' => ['id' => $customMap->id],
        ]);

        $this->assertSame($customMap->id, $timetable->fresh()->site_base_map_id);

        // 切替後は旧白地図の配置 (A01) が混在せず 0 件となる
        $this->actingAs($this->operator)->getJson(route('timetables.site-maps.placements.index', $timetable))
            ->assertOk()
            ->assertJson(['success' => true, 'placements' => []]);

        // 新白地図上へ配置して PDF 出力できること
        $this->actingAs($this->operator)->postJson(route('timetables.site-maps.placements.store', $timetable), [
            'shape_type' => 'booth_standard',
            'display_text' => 'X01',
            'pos_x' => 150.0,
            'pos_y' => 200.0,
        ])->assertStatus(201);

        $pdfRes = $this->actingAs($this->operator)->get(route('timetables.site-maps.pdf', $timetable));
        $pdfRes->assertOk();
        $pdfRes->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF-', (string) $pdfRes->getContent());
    }
}
