<?php

namespace App\Services\SiteMapPdf;

use App\Models\SiteMapIconDefinition;
use App\Models\SiteMapPlacement;
use Dompdf\Adapter\CPDF;
use Illuminate\Support\Collection as SupportCollection;

class PlacementSymbolRenderer
{
    public function __construct(
        protected SvgGeometryParser $svgParser,
        protected PdfMixedTextRenderer $textRenderer,
    ) {}

    /**
     * 給水設備の半径20m（138pt）衛生チェック円・円弧を描画
     */
    public function drawWaterHygieneCircle(CPDF $canvas, SiteMapPlacement $placement): void
    {
        $cx = (float) $placement->pos_x + (float) $placement->width / 2;
        $cy = (float) $placement->pos_y + (float) $placement->height / 2;
        $radius = 138.0;
        $blue = $this->svgParser->colorThemeToRgb('blue');

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
     * @param  SupportCollection<string, SiteMapIconDefinition>|null  $iconDefs
     */
    public function drawPlacementNode(
        CPDF $canvas,
        string $font,
        SiteMapPlacement $placement,
        ?SupportCollection $iconDefs = null
    ): void {
        $x = (float) $placement->pos_x;
        $y = (float) $placement->pos_y;
        $w = (float) $placement->width;
        $h = (float) $placement->height;
        $rot = (int) $placement->rotation;
        $rgb = $this->svgParser->colorThemeToRgb((string) $placement->color_theme);

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
                $this->textRenderer->drawCenteredTextWithMask($canvas, $font, $placement, $x, $y, $w, $h, $rgb);
            }
        } elseif ($shape === 'water_supply') {
            $this->drawWaterSupplySymbol($canvas, $x, $y, $w, $h);
        } elseif ($shape === 'area_note') {
            $this->drawAreaNoteSymbol($canvas, $font, $placement, $x, $y, $w, $h, $rgb);
        } elseif ($shape === 'free_area') {
            $this->drawHatchRect($canvas, $x, $y, $w, $h, $rgb, 0.42);
            $this->textRenderer->drawCenteredTextWithMask($canvas, $font, $placement, $x, $y, $w, $h, $rgb);
        } elseif ($shape === 'kitchen_car') {
            $this->drawKitchenCarSymbol($canvas, $x, $y, $w, $h, $rgb);
            $this->textRenderer->drawCenteredTextWithMask($canvas, $font, $placement, $x, $y, $w, $h, $rgb);
        } else {
            // CADテント・ブース (外枠 + 45°ハッチング + 四隅支柱 + 寄棟屋根稜線 + 白抜きマスク文字)
            $this->drawCadTentSymbol($canvas, $x, $y, $w, $h, $rgb);
            $this->textRenderer->drawCenteredTextWithMask($canvas, $font, $placement, $x, $y, $w, $h, $rgb);
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
        $blue = $this->svgParser->colorThemeToRgb('blue');
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
    protected function drawAreaNoteSymbol(
        CPDF $canvas,
        string $font,
        SiteMapPlacement $placement,
        float $x,
        float $y,
        float $w,
        float $h,
        array $rgb
    ): void {
        $lines = explode("\n", (string) $placement->display_text);
        $fontSize = $placement->font_size ? (float) $placement->font_size : 6.2;
        $lineH = $fontSize * 1.25;
        $canvas->filled_rectangle($x, $y, $w, max($h, count($lines) * $lineH), [1.0, 1.0, 1.0]);
        foreach ($lines as $idx => $line) {
            $this->textRenderer->drawMixedText($canvas, $x + 1.0, $y + $idx * $lineH, $line, $font, $fontSize, $rgb);
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
}
