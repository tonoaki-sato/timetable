<?php

namespace App\Services;

use App\Models\SiteBaseMap;
use App\Models\SiteMapIconDefinition;
use App\Models\SiteMapPlacement;
use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;
use Illuminate\Database\Eloquent\Collection;

class SiteMapPdfExporterService
{
    /**
     * カラーテーマとRGB値 [R, G, B] (0.0〜1.0) のマッピング
     *
     * @var array<string, array{float, float, float}>
     */
    private const array COLOR_RGB_MAP = [
        'red' => [0.863, 0.149, 0.149],     // #dc2626
        'green' => [0.086, 0.639, 0.290],   // #16a34a
        'black' => [0.059, 0.090, 0.165],   // #0f172a
        'cyan' => [0.024, 0.714, 0.831],    // #06b6d4
        'orange' => [0.976, 0.451, 0.086],  // #f97316
        'blue' => [0.145, 0.388, 0.922],    // #2563eb
    ];

    /**
     * 白地図と全配置オブジェクトから A3 1/400 ベクターPDFバイナリを生成する
     *
     * @param  Collection<int, SiteMapPlacement>  $placements
     */
    public function exportA3VectorPdf(SiteBaseMap $siteBaseMap, Collection $placements): string
    {
        $dompdf = new Dompdf;
        $options = $dompdf->getOptions();
        $options->setFontDir(storage_path('fonts'));
        $options->setFontCache(storage_path('fonts'));
        $options->setIsRemoteEnabled(false);

        $dompdf->loadHtml('<!DOCTYPE html><html><head><meta charset="utf-8"><style>@page { margin: 0; }</style></head><body style="margin:0;padding:0;"></body></html>');
        $dompdf->setPaper([0, 0, 842.0, 1191.0], 'portrait');
        $dompdf->render();

        /** @var CPDF $canvas */
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('japanesefont', 'normal') ?: 'Helvetica';

        $iconDefs = SiteMapIconDefinition::all()->keyBy('icon_key');

        // 1. 白地図ベースレイヤーの描画
        $this->drawBaseMapLayer($canvas, $font, $placements);

        // 2. 給水20m衛生サークルレイヤーの描画 (最背面配置レイヤー)
        foreach ($placements as $placement) {
            $iconDef = $iconDefs->get($placement->shape_type);
            $hasHygiene = $placement->shape_type === 'water_supply' || ($iconDef && $iconDef->has_hygiene_circle);
            if ($hasHygiene && $placement->show_hygiene_circle) {
                $this->drawWaterHygieneCircle($canvas, $placement);
            }
        }

        // 3. 配置オブジェクトレイヤーの描画 (z_index 順)
        $sorted = $placements->sortBy(fn (SiteMapPlacement $p) => [$p->z_index, $p->id]);
        foreach ($sorted as $placement) {
            $this->drawPlacementNode($canvas, $font, $placement, $iconDefs);
        }

        return (string) $dompdf->output();
    }

    /**
     * 白地図ベース（道路・建物枠・3大駐車場詳細・凡例・タイトル枠・修正済建物名）を描画
     *
     * @param  Collection<int, SiteMapPlacement>  $placements
     */
    protected function drawBaseMapLayer(CPDF $canvas, string $font, Collection $placements): void
    {
        $jsonPath = storage_path('app/site_map_base_20260927.json');
        if (! file_exists($jsonPath)) {
            return;
        }

        $baseData = json_decode((string) file_get_contents($jsonPath), true);
        if (! is_array($baseData)) {
            return;
        }

        // パス描画
        foreach ($baseData['paths'] ?? [] as $pathItem) {
            $d = (string) ($pathItem['d'] ?? '');
            $fill = $pathItem['fill'] ?? null;
            $stroke = $pathItem['stroke'] ?? null;
            $w = (float) ($pathItem['w'] ?? 0.42);

            $segments = $this->parseSvgPathToSegments($d);
            if (empty($segments)) {
                continue;
            }

            if ($fill !== null) {
                $rgb = $this->hexToRgb($fill);
                foreach ($segments as $poly) {
                    if (count($poly) >= 6) {
                        $canvas->polygon($poly, $rgb, null, [], true);
                    }
                }
            }

            if ($stroke !== null) {
                $rgb = $this->hexToRgb($stroke);
                foreach ($segments as $poly) {
                    if (count($poly) === 4) {
                        $canvas->line($poly[0], $poly[1], $poly[2], $poly[3], $rgb, $w);
                    } elseif (count($poly) > 4) {
                        for ($i = 0; $i < count($poly) - 2; $i += 2) {
                            $canvas->line($poly[$i], $poly[$i + 1], $poly[$i + 2], $poly[$i + 3], $rgb, $w);
                        }
                    }
                }
            }
        }

        // 建物・凡例・タイトルラベル描画
        $labelColor = [0.118, 0.161, 0.231]; // #1e293b
        foreach ($baseData['labels'] ?? [] as $lbl) {
            $text = (string) ($lbl['text'] ?? '');
            $x = (float) ($lbl['x'] ?? 0);
            $y = (float) ($lbl['y'] ?? 0);
            $size = (float) ($lbl['size'] ?? 8.0);
            if ($text !== '' && $size > 0) {
                $canvas->text($x, $y - $size * 0.82, $text, $font, $size, $labelColor);
            }
        }

        // 凡例「手洗い設備設置 Nヵ所」の動的数値描画
        $waterCount = $placements->where('shape_type', 'water_supply')->count();
        $canvas->filled_rectangle(82.0, 169.0, 100.0, 10.5, [1.0, 1.0, 1.0]);
        $canvas->text(84.30, 170.5, "手洗い設備設置　{$waterCount}ヵ所", $font, 7.8, $labelColor);
    }

    /**
     * 給水設備の半径20m（138pt）衛生チェック円・円弧を描画
     */
    protected function drawWaterHygieneCircle(CPDF $canvas, SiteMapPlacement $placement): void
    {
        $cx = (float) $placement->pos_x + (float) $placement->width / 2;
        $cy = (float) $placement->pos_y + (float) $placement->height / 2;
        $radius = 138.0;
        $blue = [0.145, 0.388, 0.922];

        $startDeg = (int) $placement->arc_start_angle;
        $endDeg = (int) $placement->arc_end_angle;
        if ($endDeg <= $startDeg) {
            $endDeg = $startDeg + 360;
        }

        $steps = max(36, (int) (($endDeg - $startDeg) / 4));
        $stepDeg = ($endDeg - $startDeg) / $steps;

        for ($i = 0; $i < $steps; $i++) {
            // 2本に1本を描画して破線表現
            if ($i % 2 === 1) {
                continue;
            }
            $a1 = deg2rad($startDeg + $i * $stepDeg - 90);
            $a2 = deg2rad($startDeg + ($i + 1) * $stepDeg - 90);
            $x1 = $cx + $radius * cos($a1);
            $y1 = $cy + $radius * sin($a1);
            $x2 = $cx + $radius * cos($a2);
            $y2 = $cy + $radius * sin($a2);
            $canvas->line($x1, $y1, $x2, $y2, $blue, 0.45);
        }
    }

    /**
     * 配置オブジェクトをCADベクター図形または画像シンボルとして描画
     *
     * @param  \Illuminate\Support\Collection<string, SiteMapIconDefinition>|null  $iconDefs
     */
    protected function drawPlacementNode(CPDF $canvas, string $font, SiteMapPlacement $placement, ?\Illuminate\Support\Collection $iconDefs = null): void
    {
        $x = (float) $placement->pos_x;
        $y = (float) $placement->pos_y;
        $w = (float) $placement->width;
        $h = (float) $placement->height;
        $rot = (int) $placement->rotation;
        $rgb = self::COLOR_RGB_MAP[$placement->color_theme] ?? self::COLOR_RGB_MAP['red'];

        $canvas->save();
        if ($rot !== 0) {
            $cx = $x + $w / 2;
            $cy = $y + $h / 2;
            $canvas->rotate($rot, $cx, $cy);
        }

        $shape = $placement->shape_type;
        $iconDef = $iconDefs?->get($shape);

        if ($iconDef && $iconDef->render_style === 'image' && $iconDef->icon_asset_path) {
            $fullPath = public_path($iconDef->icon_asset_path);
            if (file_exists($fullPath)) {
                $canvas->image($fullPath, $x, $y, $w, $h);
            } else {
                $canvas->rectangle($x, $y, $w, $h, $rgb, 0.5);
            }
            if (! empty(trim($placement->display_text))) {
                $this->drawCenteredTextWithMask($canvas, $font, $placement, $x, $y, $w, $h, $rgb);
            }
        } elseif ($shape === 'water_supply') {
            $this->drawWaterSupplySymbol($canvas, $x, $y, $w, $h);
        } elseif ($shape === 'area_note') {
            $this->drawAreaNoteSymbol($canvas, $font, $placement, $x, $y, $w, $h, $rgb);
        } elseif ($shape === 'free_area') {
            $this->drawHatchRect($canvas, $x, $y, $w, $h, $rgb, 0.42);
            $this->drawCenteredTextWithMask($canvas, $font, $placement, $x, $y, $w, $h, $rgb);
        } elseif ($shape === 'kitchen_car') {
            $this->drawKitchenCarSymbol($canvas, $x, $y, $w, $h, $rgb);
            $this->drawCenteredTextWithMask($canvas, $font, $placement, $x, $y, $w, $h, $rgb);
        } else {
            // CADテント・ブース (外枠 + 45°ハッチング + 四隅支柱 + 寄棟屋根稜線 + 白抜きマスク文字)
            $this->drawCadTentSymbol($canvas, $x, $y, $w, $h, $rgb);
            $this->drawCenteredTextWithMask($canvas, $font, $placement, $x, $y, $w, $h, $rgb);
        }

        $canvas->restore();
    }

    /**
     * CADテント・ブース記号（外枠 + 斜めハッチング + 四隅支柱 + 寄棟屋根稜線）
     *
     * @param  array{float, float, float}  $rgb
     */
    protected function drawCadTentSymbol(CPDF $canvas, float $x, float $y, float $w, float $h, array $rgb): void
    {
        // 1. 45°斜めハッチング (細線)
        $this->drawDiagonalHatching($canvas, $x, $y, $w, $h, $rgb, 3.2, 0.12);

        // 2. 外枠 (0.84pt)
        $canvas->rectangle($x, $y, $w, $h, $rgb, 0.84);

        // 3. 寄棟屋根の稜線 (コーナーから内側へ)
        $inset = min($w, $h) * 0.25;
        $x1 = $x + $inset;
        $y1 = $y + $inset;
        $x2 = $x + $w - $inset;
        $y2 = $y + $h - $inset;
        $canvas->line($x, $y, $x1, $y1, $rgb, 0.35);
        $canvas->line($x + $w, $y, $x2, $y1, $rgb, 0.35);
        $canvas->line($x, $y + $h, $x1, $y2, $rgb, 0.35);
        $canvas->line($x + $w, $y + $h, $x2, $y2, $rgb, 0.35);
        if ($w > $h * 1.15) {
            $canvas->line($x1, $y + $h / 2, $x2, $y + $h / 2, $rgb, 0.35);
        } elseif ($h > $w * 1.15) {
            $canvas->line($x + $w / 2, $y1, $x + $w / 2, $y2, $rgb, 0.35);
        } else {
            $canvas->line($x, $y, $x + $w, $y + $h, $rgb, 0.25);
            $canvas->line($x + $w, $y, $x, $y + $h, $rgb, 0.25);
        }

        // 4. 四隅の支柱ドット
        $p = 0.7;
        foreach ([[$x, $y], [$x + $w - $p, $y], [$x, $y + $h - $p], [$x + $w - $p, $y + $h - $p]] as [$px, $py]) {
            $canvas->filled_rectangle($px, $py, $p, $p, $rgb);
        }
    }

    /**
     * キッチンカー・車両記号
     *
     * @param  array{float, float, float}  $rgb
     */
    protected function drawKitchenCarSymbol(CPDF $canvas, float $x, float $y, float $w, float $h, array $rgb): void
    {
        $canvas->filled_rectangle($x, $y, $w, $h, [1.0, 1.0, 1.0]);
        $canvas->rectangle($x, $y, $w, $h, $rgb, 0.90);
        // 運転席側の二重線装飾
        $cabX = $x + $w * 0.18;
        $canvas->line($cabX, $y, $cabX, $y + $h, $rgb, 0.45);
    }

    /**
     * フリーエリア（斜線ハッチング枠）
     *
     * @param  array{float, float, float}  $rgb
     */
    protected function drawHatchRect(CPDF $canvas, float $x, float $y, float $w, float $h, array $rgb, float $strokeW): void
    {
        $this->drawDiagonalHatching($canvas, $x, $y, $w, $h, $rgb, 4.0, 0.14);
        $canvas->rectangle($x, $y, $w, $h, $rgb, $strokeW);
    }

    /**
     * 洗浄・手洗い用設備記号（青色二重四角＋十字）
     */
    protected function drawWaterSupplySymbol(CPDF $canvas, float $x, float $y, float $w, float $h): void
    {
        $blue = self::COLOR_RGB_MAP['blue'];
        $canvas->filled_rectangle($x, $y, $w, $h, [1.0, 1.0, 1.0]);
        $canvas->rectangle($x, $y, $w, $h, $blue, 0.55);
        $canvas->rectangle($x + 1.1, $y + 1.1, max(1, $w - 2.2), max(1, $h - 2.2), $blue, 0.40);
        $canvas->line($x + $w / 2, $y + 1.1, $x + $w / 2, $y + $h - 1.1, $blue, 0.40);
        $canvas->line($x + 1.1, $y + $h / 2, $x + $w - 1.1, $y + $h / 2, $blue, 0.40);
    }

    /**
     * エリア注記テキスト記号
     *
     * @param  array{float, float, float}  $rgb
     */
    protected function drawAreaNoteSymbol(CPDF $canvas, string $font, SiteMapPlacement $placement, float $x, float $y, float $w, float $h, array $rgb): void
    {
        $lines = explode("\n", (string) $placement->display_text);
        $fontSize = $placement->font_size ? (float) $placement->font_size : 6.2;
        $lineH = $fontSize * 1.25;
        $canvas->filled_rectangle($x, $y, $w, max($h, count($lines) * $lineH), [1.0, 1.0, 1.0]);
        foreach ($lines as $idx => $line) {
            $canvas->text($x + 1.0, $y + $idx * $lineH, $line, $font, $fontSize, $rgb);
        }
    }

    /**
     * 45°斜めハッチング描画
     *
     * @param  array{float, float, float}  $rgb
     */
    protected function drawDiagonalHatching(CPDF $canvas, float $x, float $y, float $w, float $h, array $rgb, float $step, float $lineW): void
    {
        for ($d = -$h; $d <= $w; $d += $step) {
            $x1 = $x + $d;
            $y1 = $y + $h;
            $x2 = $x + $d + $h;
            $y2 = $y;

            // クリッピング: [x, x+w] x [y, y+h]
            if ($x1 < $x) {
                $y1 -= ($x - $x1);
                $x1 = $x;
            }
            if ($x2 > $x + $w) {
                $y2 += ($x2 - ($x + $w));
                $x2 = $x + $w;
            }
            if ($x1 <= $x + $w && $x2 >= $x && $y1 >= $y && $y2 <= $y + $h) {
                $canvas->line($x1, $y1, $x2, $y2, $rgb, $lineW);
            }
        }
    }

    /**
     * 白抜き背景マスク付きの中央テキスト描画（複数行対応）
     *
     * @param  array{float, float, float}  $rgb
     */
    protected function drawCenteredTextWithMask(CPDF $canvas, string $font, SiteMapPlacement $placement, float $x, float $y, float $w, float $h, array $rgb): void
    {
        $text = trim((string) $placement->display_text);
        if ($text === '') {
            return;
        }

        $lines = explode("\n", $text);
        $lineCount = count($lines);

        // 文字サイズ自動決定
        if ($placement->font_size) {
            $fontSize = (float) $placement->font_size;
        } elseif (preg_match('/^[AB]\d+$/', $text)) {
            $fontSize = 6.2;
        } else {
            $maxChars = max(array_map(fn (string $l) => mb_strwidth($l, 'UTF-8') * 0.5, $lines));
            $fontSize = min(5.8, max(3.6, ($w - 2.0) / max(1.0, $maxChars)));
            if ($lineCount > 1) {
                $fontSize = min($fontSize, ($h - 2.0) / ($lineCount * 1.25));
            }
        }

        $lineHeight = $fontSize * 1.18;
        $totalTextH = $lineHeight * $lineCount;

        // 最大幅を計測
        $maxTextW = 0.0;
        foreach ($lines as $line) {
            $tw = $canvas->get_text_width($line, $font, $fontSize);
            if ($tw > $maxTextW) {
                $maxTextW = $tw;
            }
        }

        $maskW = min($w - 1.4, $maxTextW + 1.6);
        $maskH = min($h - 1.4, $totalTextH + 1.0);
        $maskX = $x + ($w - $maskW) / 2;
        $maskY = $y + ($h - $maskH) / 2;

        // 白抜き背景マスク
        $canvas->filled_rectangle($maskX, $maskY, max(1.0, $maskW), max(1.0, $maskH), [1.0, 1.0, 1.0]);

        // 各行を中央揃えで描画
        $startY = $y + ($h - $totalTextH) / 2;
        foreach ($lines as $idx => $line) {
            $tw = $canvas->get_text_width($line, $font, $fontSize);
            $tx = $x + ($w - $tw) / 2;
            $ty = $startY + $idx * $lineHeight;
            $canvas->text($tx, $ty, $line, $font, $fontSize, $rgb);
        }
    }

    /**
     * SVGパス `d` 文字列をポリゴン座標配列にパース
     *
     * @return array<int, array<int, float>>
     */
    protected function parseSvgPathToSegments(string $d): array
    {
        $segments = [];
        $current = [];
        if (preg_match_all('/([MLZ])\s*([-\d\.]+)?\s*([-\d\.]+)?/i', $d, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $cmd = strtoupper($m[1]);
                if ($cmd === 'M') {
                    if (count($current) >= 4) {
                        $segments[] = $current;
                    }
                    $current = [(float) $m[2], (float) $m[3]];
                } elseif ($cmd === 'L') {
                    $current[] = (float) $m[2];
                    $current[] = (float) $m[3];
                } elseif ($cmd === 'Z') {
                    if (count($current) >= 4) {
                        $current[] = $current[0];
                        $current[] = $current[1];
                        $segments[] = $current;
                    }
                    $current = [];
                }
            }
            if (count($current) >= 4) {
                $segments[] = $current;
            }
        }

        return $segments;
    }

    /**
     * Hex カラーを [R, G, B] (0.0〜1.0) に変換
     *
     * @return array{float, float, float}
     */
    protected function hexToRgb(string $hex): array
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) {
            return [0.278, 0.333, 0.412];
        }

        return [
            hexdec(substr($hex, 0, 2)) / 255.0,
            hexdec(substr($hex, 2, 2)) / 255.0,
            hexdec(substr($hex, 4, 2)) / 255.0,
        ];
    }
}
