<?php

namespace Tests\Feature;

use App\Models\SiteBaseMap;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteBaseMapViewerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Timetable $timetable;

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
    }

    /**
     * 未ログインユーザーはログイン画面にリダイレクトされる
     */
    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get(route('timetables.site-maps.editor', $this->timetable));

        $response->assertRedirect(route('login'));
    }

    /**
     * 白地図エディタ画面が正常に表示され、必要なDOM要素と専用Viteスクリプトが含まれる
     */
    public function test_editor_screen_renders_successfully_with_vite_script(): void
    {
        // 既存の白地図レコードを作成
        $siteBaseMap = SiteBaseMap::create([
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

        $response = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $this->timetable));

        $response->assertStatus(200);

        // タイトルや白地図メタ情報
        $response->assertSee('白地図配置エディタ');
        $response->assertSee('A3 1/400 CADベクター図面');
        $response->assertSee('図面配置サマリー');

        // 主要なDOMコンテナ・ツールバーボタン
        $response->assertSee('id="site-layout-editor-app"', false);
        $response->assertSee('id="site-map-canvas-container"', false);
        $response->assertSee('id="site-map-viewport"', false);
        $response->assertSee('id="btn-zoom-in"', false);
        $response->assertSee('id="btn-zoom-out"', false);
        $response->assertSee('id="btn-zoom-fit"', false);

        // 画面専用 Vite JavaScript のアセット読み込み検証（規約遵守）
        $response->assertSee('site-layout-editor', false);

        // 厳格な CSP 検証: インライン script タグや onclick ハンドラが存在しないこと
        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/onclick\s*=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/onload\s*=/i', $content);
        // 通常のインラインスクリプトブロック（<script>...</script>でsrcを持たないもの）がないことを確認
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*src=)[^>]*>/i', $content);
    }

    /**
     * 白地図未登録の場合はデフォルト白地図が自動生成される
     */
    public function test_editor_creates_default_base_map_if_none_exists(): void
    {
        $this->assertDatabaseCount('site_base_maps', 0);

        $response = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $this->timetable));

        $response->assertStatus(200);
        $this->assertDatabaseCount('site_base_maps', 1);

        $this->assertDatabaseHas('site_base_maps', [
            'timetable_id' => $this->timetable->id,
            'name' => '保土ヶ谷宿場まつり 平面図 1/400 A3',
            'is_active' => true,
        ]);
    }

    /**
     * 白地図（SVGおよびJSON）内に旧ブース矩形枠線（1m×1.8m）が残存せず、クリーンであることを検証
     */
    public function test_base_map_svg_and_json_have_no_residual_booth_rectangles(): void
    {
        $svgPath = public_path('maps/shopping_street_base_map_260813.svg');
        $this->assertFileExists($svgPath);

        $svgContent = file_get_contents($svgPath);

        // 必須の建物名テキストが含まれていることを確認
        $this->assertStringContainsString('更地（旧ふとん池田屋）', $svgContent);
        $this->assertStringContainsString('国際会館', $svgContent);
        $this->assertStringContainsString('長島駐車場（旧北川駐車場）内', $svgContent);

        // 凡例外に 1m x 1.8m ブース矩形（幅 5.8〜6.6pt かつ 高さ 12.0〜13.0pt）の閉じたサブパスが存在しないことを検証
        preg_match_all('/(M\s+[^Z]+?\s*Z)/', $svgContent, $matches);
        $residualBoothCount = 0;

        foreach ($matches[1] as $subpath) {
            preg_match_all('/([-\d\.]+)\s+([-\d\.]+)/', $subpath, $ptMatches, PREG_SET_ORDER);
            if (count($ptMatches) >= 3) {
                $xs = array_map(fn ($p) => (float) $p[1], $ptMatches);
                $ys = array_map(fn ($p) => (float) $p[2], $ptMatches);
                $minX = min($xs);
                $minY = min($ys);
                $width = max($xs) - $minX;
                $height = max($ys) - $minY;

                if ($width >= 5.8 && $width <= 6.6 && $height >= 12.0 && $height <= 13.0) {
                    if ($minX > 105 || $minY > 180) {
                        $residualBoothCount++;
                    }
                }
            }
        }

        $this->assertSame(0, $residualBoothCount, '白地図SVG内に旧ブース矩形枠線（1m×1.8m）が残存してはならない');

        // JSONデータ側の検証
        $jsonPath = storage_path('app/site_map_base_20260927.json');
        if (file_exists($jsonPath)) {
            $jsonData = json_decode(file_get_contents($jsonPath), true);
            $this->assertIsArray($jsonData);
            $this->assertArrayHasKey('paths', $jsonData);

            $jsonResidualCount = 0;
            foreach ($jsonData['paths'] as $pathItem) {
                $d = $pathItem['d'] ?? '';
                preg_match_all('/(M\s+[^M]+)/i', $d, $subMatches);
                foreach ($subMatches[1] as $subpath) {
                    preg_match_all('/([-\d\.]+)\s+([-\d\.]+)/', $subpath, $ptMatches, PREG_SET_ORDER);
                    if (count($ptMatches) >= 3) {
                        $xs = array_map(fn ($p) => (float) $p[1], $ptMatches);
                        $ys = array_map(fn ($p) => (float) $p[2], $ptMatches);
                        $minX = min($xs);
                        $minY = min($ys);
                        $width = max($xs) - $minX;
                        $height = max($ys) - $minY;

                        if ($width >= 5.8 && $width <= 6.6 && $height >= 12.0 && $height <= 13.0) {
                            if ($minX > 105 || $minY > 180) {
                                $jsonResidualCount++;
                            }
                        }
                    }
                }
            }

            $this->assertSame(0, $jsonResidualCount, '白地図JSON内に旧ブース矩形枠線（1m×1.8m）が残存してはならない');
        }
    }
}
