<?php

namespace Tests\Feature;

use App\Models\SiteBaseMap;
use App\Models\SiteMapIconDefinition;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Models\User;
use App\Repositories\Contracts\SiteMapRepositoryInterface;
use Database\Seeders\SiteMapIconDefinitionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class SiteMapIconManagementTest extends TestCase
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

    protected function tearDown(): void
    {
        // テストで作成されたカスタム画像ファイルをクリーンアップ
        $customDir = public_path('maps/icons/custom');
        if (File::exists($customDir)) {
            $files = File::files($customDir);
            foreach ($files as $file) {
                if (str_starts_with($file->getFilename(), 'custom_')) {
                    File::delete($file->getPathname());
                }
            }
        }

        parent::tearDown();
    }

    /**
     * PNG画像アップロードからカスタムアイコン定義を新規作成できる
     */
    public function test_can_create_custom_icon_from_uploaded_png(): void
    {
        $fakePng = UploadedFile::fake()->image('aid_station.png', 128, 128);

        $payload = [
            'name' => '本部救護所',
            'category' => 'facility',
            'width_meters' => 3.0,
            'height_meters' => 2.5,
            'is_rotatable' => true,
            'is_resizable' => true,
            'has_hygiene_circle' => true,
            'default_color' => 'blue',
            'png_file' => $fakePng,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('timetables.site-maps.icons.store', $this->timetable), $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'icon' => [
                    'name' => '本部救護所',
                    'category' => 'facility',
                    'render_style' => 'image',
                    'is_rotatable' => true,
                    'is_resizable' => true,
                    'has_hygiene_circle' => true,
                    'is_preset' => false,
                ],
            ]);

        $json = $response->json();
        $this->assertNotEmpty($json['icon']['icon_asset_path']);
        $this->assertFileExists(public_path($json['icon']['icon_asset_path']));

        // スケール換算の検証 (3.0m * 7.0866 = 21.26pt, 2.5m * 7.0866 = 17.72pt)
        $this->assertEquals(21.26, (float) $json['icon']['default_width']);
        $this->assertEquals(17.72, (float) $json['icon']['default_height']);

        $this->assertDatabaseHas('site_map_icon_definitions', [
            'name' => '本部救護所',
            'render_style' => 'image',
            'is_preset' => false,
        ]);
    }

    /**
     * 非PNGファイル（JPEGやPDF等）のアップロードはバリデーションで弾かれる
     */
    public function test_rejects_non_png_upload(): void
    {
        $fakePdf = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');

        $payload = [
            'name' => '無効ファイル',
            'category' => 'booth',
            'width_meters' => 2.0,
            'height_meters' => 2.0,
            'png_file' => $fakePdf,
        ];

        $response = $this->actingAs($this->user)
            ->postJson(route('timetables.site-maps.icons.store', $this->timetable), $payload);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['png_file']);
    }

    /**
     * カスタムアイコンの属性変更および画像ファイルの差し替えができる
     */
    public function test_can_update_custom_icon_and_replace_image(): void
    {
        $initialPng = UploadedFile::fake()->image('initial.png', 64, 64);
        $icon = SiteMapIconDefinition::create([
            'icon_key' => 'custom_test_icon',
            'name' => '旧アイコン名',
            'category' => 'tent',
            'render_style' => 'image',
            'default_width' => 14.17,
            'default_height' => 14.17,
            'is_resizable' => false,
            'is_rotatable' => true,
            'is_preset' => false,
            'icon_asset_path' => 'maps/icons/custom/custom_old_file.png',
        ]);

        $customDir = public_path('maps/icons/custom');
        File::ensureDirectoryExists($customDir, 0777, true);
        File::put(public_path('maps/icons/custom/custom_old_file.png'), 'fake-png-binary');

        $replacementPng = UploadedFile::fake()->image('replacement.png', 128, 128);

        $response = $this->actingAs($this->user)
            ->post(route('timetables.site-maps.icons.update', [$this->timetable, $icon]), [
                'name' => '新アイコン名（更新済）',
                'category' => 'vehicle',
                'width_meters' => 4.0,
                'height_meters' => 2.0,
                'is_resizable' => true,
                'png_file' => $replacementPng,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'icon' => [
                    'id' => $icon->id,
                    'name' => '新アイコン名（更新済）',
                    'category' => 'vehicle',
                    'is_resizable' => true,
                ],
            ]);

        // 旧ファイルがクリーンアップされ、新ファイルが存在すること
        $this->assertFileDoesNotExist(public_path('maps/icons/custom/custom_old_file.png'));
        $json = $response->json();
        $this->assertFileExists(public_path($json['icon']['icon_asset_path']));
    }

    /**
     * 初期定義のプリセットアイコンは保護されており、編集・削除できない
     */
    public function test_cannot_edit_or_delete_preset_icon(): void
    {
        $preset = SiteMapIconDefinition::where('icon_key', 'booth_standard')->firstOrFail();
        $this->assertTrue($preset->is_preset);

        // 編集試行
        $updateRes = $this->actingAs($this->user)
            ->postJson(route('timetables.site-maps.icons.update', [$this->timetable, $preset]), [
                'name' => '改ざんブース',
                'category' => 'booth',
                'width_meters' => 2.0,
                'height_meters' => 2.0,
            ]);
        $updateRes->assertStatus(422);

        // 削除試行
        $deleteRes = $this->actingAs($this->user)
            ->deleteJson(route('timetables.site-maps.icons.destroy', [$this->timetable, $preset]));
        $deleteRes->assertStatus(422);
    }

    /**
     * 白地図上で使用されている件数（usage_count）を取得できる
     */
    public function test_can_get_icon_usage_count(): void
    {
        $icon = SiteMapIconDefinition::create([
            'icon_key' => 'custom_checkpoint',
            'name' => '警備チェックポイント',
            'category' => 'facility',
            'render_style' => 'image',
            'default_width' => 14.17,
            'default_height' => 14.17,
            'is_preset' => false,
            'icon_asset_path' => 'maps/icons/custom/custom_dummy.png',
        ]);

        SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'custom_checkpoint',
            'pos_x' => 100,
            'pos_y' => 100,
        ]);
        SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'custom_checkpoint',
            'pos_x' => 150,
            'pos_y' => 150,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('timetables.site-maps.icons.usage', [$this->timetable, $icon]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'usage_count' => 2,
            ]);
    }

    /**
     * カスタムアイコン削除時に白地図上の配置も連動削除され、物理画像ファイルが消去される
     */
    public function test_deleting_custom_icon_cascades_placements_and_cleans_file(): void
    {
        $dummyPath = 'maps/icons/custom/custom_to_delete.png';
        File::ensureDirectoryExists(public_path('maps/icons/custom'), 0777, true);
        File::put(public_path($dummyPath), 'fake-png-binary');

        $icon = SiteMapIconDefinition::create([
            'icon_key' => 'custom_guard',
            'name' => '警備詰所',
            'category' => 'facility',
            'render_style' => 'image',
            'default_width' => 14.17,
            'default_height' => 14.17,
            'is_preset' => false,
            'icon_asset_path' => $dummyPath,
        ]);

        $p = SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'custom_guard',
            'pos_x' => 200,
            'pos_y' => 200,
        ]);

        // force なしの場合は422で拒否される
        $resWithoutForce = $this->actingAs($this->user)
            ->deleteJson(route('timetables.site-maps.icons.destroy', [$this->timetable, $icon]));
        $resWithoutForce->assertStatus(422);

        // force=1 の場合は連動削除される
        $resWithForce = $this->actingAs($this->user)
            ->deleteJson(route('timetables.site-maps.icons.destroy', [$this->timetable, $icon, 'force' => 1]));
        $resWithForce->assertStatus(200)
            ->assertJson([
                'success' => true,
                'placements_deleted' => 1,
            ]);

        $this->assertDatabaseMissing('site_map_icon_definitions', ['id' => $icon->id]);
        $this->assertDatabaseMissing('site_map_placements', ['id' => $p->id]);
        $this->assertFileDoesNotExist(public_path($dummyPath));
    }

    /**
     * カスタムPNGアイコンが白地図エディタ画面およびA3ベクターPDFで正常にレンダリングされる
     */
    public function test_editor_and_pdf_render_custom_png_icon(): void
    {
        $dummyPath = 'maps/icons/custom/custom_render_test.png';
        File::ensureDirectoryExists(public_path('maps/icons/custom'), 0777, true);
        // 有効な 1x1 透過PNGバイナリ
        $valid1x1Png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        File::put(public_path($dummyPath), $valid1x1Png);

        $icon = SiteMapIconDefinition::create([
            'icon_key' => 'custom_kankou',
            'name' => '観光案内所',
            'category' => 'facility',
            'render_style' => 'image',
            'default_width' => 14.17,
            'default_height' => 14.17,
            'is_preset' => false,
            'icon_asset_path' => $dummyPath,
        ]);

        SiteMapPlacement::create([
            'site_base_map_id' => $this->siteBaseMap->id,
            'shape_type' => 'custom_kankou',
            'display_text' => '案内所',
            'color_theme' => 'blue',
            'pos_x' => 350.00,
            'pos_y' => 500.00,
            'width' => 14.17,
            'height' => 14.17,
            'rotation' => 45,
        ]);

        // 1. エディタ画面の描画検証
        $editorRes = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $this->timetable));
        $editorRes->assertStatus(200);
        $editorRes->assertSee('観光案内所');
        $editorRes->assertSee('custom_kankou');
        $editorRes->assertSee('modal-create-icon', false);
        $editorRes->assertSee('modal-edit-icon', false);
        $editorRes->assertSee('modal-delete-icon', false);

        // 2. PDF出力の検証
        $pdfRes = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.pdf', $this->timetable));
        $pdfRes->assertStatus(200);
        $pdfRes->assertHeader('Content-Type', 'application/pdf');
        $content = $pdfRes->getContent();
        $this->assertStringStartsWith('%PDF-', $content);
        $this->assertGreaterThan(20000, strlen($content));
    }

    /**
     * 通り標準ブースおよび通りテントブースの仕様とパレット表示順序を検証
     */
    public function test_booth_standard_and_booth_with_tent_specifications_and_palette_order(): void
    {
        // 1. booth_standard（通り標準ブース）の仕様検証
        $standard = SiteMapIconDefinition::where('icon_key', 'booth_standard')->firstOrFail();
        $this->assertSame('通り標準ブース', $standard->name);
        $this->assertSame('6.24', (string) $standard->default_width);
        $this->assertSame('12.48', (string) $standard->default_height);
        $this->assertSame('black', $standard->default_color);
        $this->assertFalse($standard->is_hygiene_target);
        $this->assertTrue($standard->is_preset);

        // 2. booth_with_tent（通りテントブース）の仕様検証
        $tent = SiteMapIconDefinition::where('icon_key', 'booth_with_tent')->firstOrFail();
        $this->assertSame('通りテントブース', $tent->name);
        $this->assertSame('13.86', (string) $tent->default_width);
        $this->assertSame('13.86', (string) $tent->default_height);
        $this->assertSame('red', $tent->default_color);
        $this->assertTrue($tent->is_hygiene_target);
        $this->assertTrue($tent->is_preset);

        // 3. リポジトリの getAllIconDefinitions によるパレット表示順（SQL非依存ソート）の検証
        /** @var SiteMapRepositoryInterface $repo */
        $repo = app(SiteMapRepositoryInterface::class);
        $allIcons = $repo->getAllIconDefinitions();

        $this->assertGreaterThanOrEqual(2, $allIcons->count());
        $this->assertSame('booth_standard', $allIcons[0]->icon_key);
        $this->assertSame('通り標準ブース', $allIcons[0]->name);
        $this->assertSame('booth_with_tent', $allIcons[1]->icon_key);
        $this->assertSame('通りテントブース', $allIcons[1]->name);
    }
}
