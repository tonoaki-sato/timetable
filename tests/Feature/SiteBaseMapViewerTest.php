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
        // 既存の白地図マスターレコードを作成
        $siteBaseMap = SiteBaseMap::create([
            'name' => '商店街平面図 260813版 A3 1/400',
            'cad_version' => '260813',
            'paper_size' => 'A3',
            'scale_ratio' => 26.000,
            'width_meters' => 118.80,
            'height_meters' => 168.00,
            'svg_path' => 'maps/shopping_street_base_map_260813.svg',
            'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
            'viewbox' => '0 0 842 1191',
            'is_default' => true,
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
     * 白地図未登録の場合はデフォルト白地図が1件だけ作成され、複数の運行日程で共有される
     */
    public function test_editor_creates_default_base_map_if_none_exists_and_shares_across_timetables(): void
    {
        $this->assertDatabaseCount('site_base_maps', 0);

        $secondTimetable = Timetable::factory()->create([
            'name' => '2026年本祭 2日目',
            'target_date' => '2026-10-19',
            'created_by' => $this->user->id,
        ]);

        $response1 = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $this->timetable));

        $response1->assertStatus(200);
        $this->assertDatabaseCount('site_base_maps', 1);

        // 別日程でエディタを開いても同一デフォルト白地図が共有され重複生成されないこと
        $response2 = $this->actingAs($this->user)
            ->get(route('timetables.site-maps.editor', $secondTimetable));

        $response2->assertStatus(200);
        $this->assertDatabaseCount('site_base_maps', 1);

        $this->assertDatabaseHas('site_base_maps', [
            'name' => '保土ヶ谷宿場まつり 平面図 1/400 A3',
            'is_default' => true,
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

    /**
     * 白地図（SVGおよびJSON）内にPDF取り込み時の消し忘れ斜線ハッチングが残存せず、かつSVG/JSONが一致していることを検証
     */
    public function test_base_map_svg_and_json_have_no_residual_diagonal_hatch_lines(): void
    {
        $svgPath = public_path('maps/shopping_street_base_map_260813.svg');
        $jsonPath = storage_path('app/site_map_base_20260927.json');

        $this->assertFileExists($svgPath);
        $this->assertFileExists($jsonPath);

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $dom->load($svgPath, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        $this->assertTrue($loaded, '白地図SVGをXMLとしてパースできること');

        $svgDs = [];
        $svgHatchCount = 0;
        foreach ($dom->getElementsByTagName('path') as $pathEl) {
            $d = (string) $pathEl->getAttribute('d');
            $sw = (float) $pathEl->getAttribute('stroke-width');
            $svgDs[] = $d;

            if ($this->isResidualDiagonalHatchPath($d, $sw)) {
                $svgHatchCount++;
            }
        }

        $this->assertSame(0, $svgHatchCount, '白地図SVG内に消し忘れの斜線ハッチングが残存してはならない');

        $jsonData = json_decode((string) file_get_contents($jsonPath), true);
        $this->assertIsArray($jsonData);
        $this->assertArrayHasKey('paths', $jsonData);

        $jsonDs = [];
        $jsonHatchCount = 0;
        foreach ($jsonData['paths'] as $pathItem) {
            $d = (string) ($pathItem['d'] ?? '');
            $w = (float) ($pathItem['w'] ?? 0);
            $jsonDs[] = $d;

            if ($this->isResidualDiagonalHatchPath($d, $w)) {
                $jsonHatchCount++;
            }
        }

        $this->assertSame(0, $jsonHatchCount, '白地図JSON内に消し忘れの斜線ハッチングが残存してはならない');
        $this->assertCount(1510, $svgDs);
        $this->assertCount(1510, $jsonDs);
        $this->assertSame($svgDs, $jsonDs, '白地図SVGとJSONのパス配列が1対1で一致していること');

        // 削除済みの長島駐車場中央右寄り不要矩形が残存していないことを検証
        $removedNagashimaRect = 'M 205.32 899.16 L 238.50 899.16 L 238.50 886.74 L 205.32 886.74 Z M 238.50 899.16';
        $this->assertNotContains($removedNagashimaRect, $svgDs, '長島駐車場の不要矩形がSVGに残存してはならない');
        $this->assertNotContains($removedNagashimaRect, $jsonDs, '長島駐車場の不要矩形がJSONに残存してはならない');

        // 削除済みのS&S前駐車場の不要な寸法線（縦・横の鍵線）が残存していないことを検証
        $removedSsDimensionLines = [
            'M 195.12 700.74 L 195.12 645.42',
            'M 98.70 708.54 L 188.58 708.54',
        ];
        foreach ($removedSsDimensionLines as $lineD) {
            $this->assertNotContains($lineD, $svgDs, "不要な寸法線 {$lineD} がSVGに残存してはならない");
            $this->assertNotContains($lineD, $jsonDs, "不要な寸法線 {$lineD} がJSONに残存してはならない");
        }

        // ラベルの整合性および削除済みテキストの非残存を検証
        $svgLabels = [];
        foreach ($dom->getElementsByTagName('text') as $textEl) {
            $svgLabels[] = trim((string) $textEl->textContent);
        }
        $jsonLabels = array_map(
            fn (array $lbl): string => trim((string) ($lbl['text'] ?? '')),
            $jsonData['labels'] ?? []
        );

        $this->assertCount(82, $svgLabels);
        $this->assertCount(82, $jsonLabels);
        $this->assertSame($svgLabels, $jsonLabels, '白地図SVGとJSONのラベル配列が1対1で一致していること');
        $this->assertNotContains('(手洗い建物内対応)', $svgLabels, '不要なラベルがSVGに残存してはならない');
        $this->assertNotContains('(手洗い建物内対応)', $jsonLabels, '不要なラベルがJSONに残存してはならない');

        // 道路西側のHermoniaがアパートへ更新され、東側のHermoniaは1件保持されていることを検証
        $this->assertContains('アパート', $svgLabels);
        $this->assertSame(1, count(array_filter($svgLabels, fn (string $label): bool => $label === 'Hermonia')));

        // ストークマンション直下に4行のテナント・設備ラベルが順序通り追加されていることを検証
        $stokeIndex = array_search('ストークマンション', $svgLabels, true);
        $this->assertIsInt($stokeIndex);
        $this->assertSame(
            ['スロープ', 'クリーニングネオ２１', 'キクバル', 'はらぺこらいおん'],
            array_slice($svgLabels, $stokeIndex + 1, 4)
        );

        // 誤削除されてはならない正当な斜線構造線が保持されていることを検証
        $preservedLines = [
            'M 69.30 954.42 L 77.70 962.82',
            'M 95.40 897.12 L 60.84 881.22',
        ];
        foreach ($preservedLines as $lineD) {
            $this->assertContains($lineD, $svgDs, "正当な構造線 {$lineD} がSVGに保持されていること");
            $this->assertContains($lineD, $jsonDs, "正当な構造線 {$lineD} がJSONに保持されていること");
        }
    }

    /**
     * 消し忘れ斜線ハッチング（stroke-width 0.42、単一線分、長さ8pt以下、45°±3°または135°±3°）を判定
     */
    private function isResidualDiagonalHatchPath(string $d, float $strokeWidth): bool
    {
        if (abs($strokeWidth - 0.42) > 0.01) {
            return false;
        }

        if (preg_match('/^M\s+([-\d.]+)\s+([-\d.]+)\s+L\s+([-\d.]+)\s+([-\d.]+)$/', trim($d), $matches) !== 1) {
            return false;
        }

        $dx = (float) $matches[3] - (float) $matches[1];
        $dy = (float) $matches[4] - (float) $matches[2];
        $length = hypot($dx, $dy);

        if ($length <= 0.01 || $length > 8.0) {
            return false;
        }

        $angle = fmod(rad2deg(atan2($dy, $dx)) + 360.0, 180.0);

        return ($angle >= 42.0 && $angle <= 48.0) || ($angle >= 132.0 && $angle <= 138.0);
    }
}
