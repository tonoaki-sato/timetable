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
     * パース済み白地図ベースデータのプロセス内キャッシュ
     *
     * @var array<string, array{paths: array<int, array<string, mixed>>, labels: array<int, array<string, mixed>>}>
     */
    protected static array $parsedBaseMapCache = [];

    /**
     * 日本語フォントの実体ファイルパスを探索して取得
     */
    public function getJapaneseFontPath(): string
    {
        $candidates = [
            resource_path('fonts/DroidSansFallbackFull.ttf'),
            storage_path('fonts/DroidSansFallbackFull.ttf'),
            '/usr/share/fonts/google-droid-sans-fonts/DroidSansFallbackFull.ttf',
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/truetype/fonts-japanese-gothic.ttf',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return resource_path('fonts/DroidSansFallbackFull.ttf');
    }

    /**
     * 白地図と全配置オブジェクトから A3 1/400 ベクターPDFバイナリを生成する
     *
     * @param  Collection<int, SiteMapPlacement>  $placements
     */
    public function exportA3VectorPdf(SiteBaseMap $siteBaseMap, Collection $placements): string
    {
        $fontPath = $this->getJapaneseFontPath();
        $fontDir = storage_path('fonts');
        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0777, true);
        }

        $dompdf = new Dompdf;
        $options = $dompdf->getOptions();
        $options->setFontDir($fontDir);
        $options->setFontCache($fontDir);
        $options->setChroot(base_path());
        $options->setIsRemoteEnabled(true);

        // 既に japanesefont が登録されている場合は不要な再登録（w+ でのUFM再生成）をスキップ
        $existingFont = $dompdf->getFontMetrics()->getFont('japanesefont', 'normal');
        if (! $existingFont) {
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
                .'@font-face { font-family: "japanesefont"; src: url("file://'.$fontPath.'") format("truetype"); }'
                .'@page { margin: 0; } body { font-family: "japanesefont"; margin: 0; padding: 0; }'
                .'</style></head><body>&nbsp;</body></html>';
        } else {
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>@page { margin: 0; }</style></head><body style="margin:0;padding:0;"></body></html>';
        }

        $isLegacyPreset = $siteBaseMap->svg_path === 'maps/shopping_street_base_map_260813.svg'
            && file_exists(storage_path('app/site_map_base_20260927.json'));
        $paperDims = $isLegacyPreset
            ? ['width' => 842.0, 'height' => 1191.0]
            : $siteBaseMap->getViewboxDimensions();

        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, $paperDims['width'], $paperDims['height']], 'portrait');
        $dompdf->render();

        // 初回生成時のWebサーバー・CLI間の所有権不一致（Permission denied）を防止
        if (! $existingFont) {
            foreach (glob($fontDir.'/*') ?: [] as $file) {
                if (is_file($file)) {
                    @chmod($file, 0666);
                }
            }
        }

        /** @var CPDF $canvas */
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('japanesefont', 'normal');
        if (! $font) {
            $font = $fontPath;
        }

        $iconDefs = SiteMapIconDefinition::all()->keyBy('icon_key');

        // 1. 白地図ベースレイヤーの描画
        if ($isLegacyPreset) {
            $this->drawBaseMapLayer($canvas, $font, $placements);
        } else {
            $this->drawSvgFallbackBaseMapLayer($canvas, $siteBaseMap);
        }

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
        $parsedBaseMap = $this->loadParsedBaseMapData();
        if ($parsedBaseMap === null) {
            return;
        }

        // パス描画
        foreach ($parsedBaseMap['paths'] as $pathItem) {
            $segments = $pathItem['segments'];
            $fillRgb = $pathItem['fill_rgb'];
            $strokeRgb = $pathItem['stroke_rgb'];
            $w = $pathItem['w'];

            if ($fillRgb !== null) {
                foreach ($segments as $poly) {
                    if (count($poly) >= 6) {
                        $canvas->polygon($poly, $fillRgb, null, [], true);
                    }
                }
            }

            if ($strokeRgb !== null) {
                foreach ($segments as $poly) {
                    if (count($poly) === 4) {
                        $canvas->line($poly[0], $poly[1], $poly[2], $poly[3], $strokeRgb, $w);
                    } elseif (count($poly) > 4) {
                        for ($i = 0; $i < count($poly) - 2; $i += 2) {
                            $canvas->line($poly[$i], $poly[$i + 1], $poly[$i + 2], $poly[$i + 3], $strokeRgb, $w);
                        }
                    }
                }
            }
        }

        // 建物・凡例・タイトルラベル描画
        $labelColor = [0.118, 0.161, 0.231]; // #1e293b
        foreach ($parsedBaseMap['labels'] as $lbl) {
            $text = $lbl['text'];
            $x = $lbl['x'];
            $y = $lbl['y'];
            $size = $lbl['size'];
            if ($text !== '' && $size > 0) {
                $this->drawMixedText($canvas, $x, $y - $size * 0.82, $text, $font, $size, $labelColor);
            }
        }

        // 凡例「手洗い設備設置 Nヵ所」の動的数値描画
        $waterCount = $placements->where('shape_type', 'water_supply')->count();
        $canvas->filled_rectangle(82.0, 169.0, 100.0, 10.5, [1.0, 1.0, 1.0]);
        $this->drawMixedText($canvas, 84.30, 170.5, "手洗い設備設置　{$waterCount}ヵ所", $font, 7.8, $labelColor);
    }

    /**
     * 白地図ベースJSONの読み込みとSVGパスパース結果をプロセス内キャッシュして返却
     *
     * @return array{paths: array<int, array<string, mixed>>, labels: array<int, array<string, mixed>>}|null
     */
    protected function loadParsedBaseMapData(): ?array
    {
        $jsonPath = storage_path('app/site_map_base_20260927.json');
        if (! file_exists($jsonPath)) {
            return null;
        }

        $mtime = (int) (filemtime($jsonPath) ?: 0);
        $cacheKey = $jsonPath.'|'.$mtime;
        if (isset(self::$parsedBaseMapCache[$cacheKey])) {
            return self::$parsedBaseMapCache[$cacheKey];
        }

        $baseData = json_decode((string) file_get_contents($jsonPath), true);
        if (! is_array($baseData)) {
            return null;
        }

        $parsedPaths = [];
        foreach ($baseData['paths'] ?? [] as $pathItem) {
            $d = (string) ($pathItem['d'] ?? '');
            $segments = $this->parseSvgPathToSegments($d);
            if ($segments === []) {
                continue;
            }

            $fill = $pathItem['fill'] ?? null;
            $stroke = $pathItem['stroke'] ?? null;

            $parsedPaths[] = [
                'segments' => $segments,
                'fill_rgb' => $fill !== null ? $this->hexToRgb((string) $fill) : null,
                'stroke_rgb' => $stroke !== null ? $this->hexToRgb((string) $stroke) : null,
                'w' => (float) ($pathItem['w'] ?? 0.42),
            ];
        }

        $parsedLabels = [];
        foreach ($baseData['labels'] ?? [] as $lbl) {
            $parsedLabels[] = [
                'text' => (string) ($lbl['text'] ?? ''),
                'x' => (float) ($lbl['x'] ?? 0),
                'y' => (float) ($lbl['y'] ?? 0),
                'size' => (float) ($lbl['size'] ?? 8.0),
            ];
        }

        self::$parsedBaseMapCache[$cacheKey] = [
            'paths' => $parsedPaths,
            'labels' => $parsedLabels,
        ];

        return self::$parsedBaseMapCache[$cacheKey];
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
            $this->drawMixedText($canvas, $x + 1.0, $y + $idx * $lineH, $line, $font, $fontSize, $rgb);
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

        // 各行のチャンク分割と幅計測を1回のみ実行し、マスク算出と中央描画の両方で再利用する
        $measuredLines = [];
        $maxTextW = 0.0;
        foreach ($lines as $line) {
            $chunks = $this->splitMixedText($line);
            $chunkMeasurements = [];
            $lineWidth = 0.0;
            foreach ($chunks as $chunk) {
                $chunkFont = $chunk['is_ascii'] ? 'Helvetica' : $font;
                $chunkWidth = $canvas->get_text_width($chunk['text'], $chunkFont, $fontSize);
                $lineWidth += $chunkWidth;
                $chunkMeasurements[] = [
                    'text' => $chunk['text'],
                    'font' => $chunkFont,
                    'width' => $chunkWidth,
                ];
            }
            $measuredLines[] = [
                'width' => $lineWidth,
                'chunks' => $chunkMeasurements,
            ];
            if ($lineWidth > $maxTextW) {
                $maxTextW = $lineWidth;
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
        $baseBaseline = $canvas->get_font_baseline($font, $fontSize);
        $baselines = [
            $font => $baseBaseline,
            'Helvetica' => $canvas->get_font_baseline('Helvetica', $fontSize),
        ];

        foreach ($measuredLines as $idx => $measuredLine) {
            $tx = $x + ($w - $measuredLine['width']) / 2;
            $ty = $startY + $idx * $lineHeight;
            $currentX = $tx;
            foreach ($measuredLine['chunks'] as $chunk) {
                $yOffset = $baseBaseline - $baselines[$chunk['font']];
                $canvas->text($currentX, $ty + $yOffset, $chunk['text'], $chunk['font'], $fontSize, $rgb);
                $currentX += $chunk['width'];
            }
        }
    }

    /**
     * 文字列を ASCII印刷可能文字（半角英数字・記号）と非ASCII（日本語等）のチャンク配列に分割
     *
     * @return array<int, array{text: string, is_ascii: bool}>
     */
    protected function splitMixedText(string $text): array
    {
        if ($text === '') {
            return [];
        }

        preg_match_all('/([\x20-\x7E]+|[^\x20-\x7E]+)/u', $text, $matches);
        $chunks = [];
        foreach ($matches[1] as $chunk) {
            $chunks[] = [
                'text' => $chunk,
                'is_ascii' => preg_match('/^[\x20-\x7E]+$/', $chunk) === 1,
            ];
        }

        return $chunks;
    }

    /**
     * 日本語と英数字・ASCII記号が混在する文字列の合計描画幅(pt)を計測
     */
    protected function getMixedTextWidth(CPDF $canvas, string $text, string $japaneseFont, float $size): float
    {
        $chunks = $this->splitMixedText($text);
        $totalWidth = 0.0;
        foreach ($chunks as $chunk) {
            $font = $chunk['is_ascii'] ? 'Helvetica' : $japaneseFont;
            $totalWidth += $canvas->get_text_width($chunk['text'], $font, $size);
        }

        return $totalWidth;
    }

    /**
     * 日本語と英数字・ASCII記号が混在する文字列をフォント切り替え（Helvetica / japanesefont）しながら連続描画
     *
     * @param  array{float, float, float}  $color
     */
    protected function drawMixedText(CPDF $canvas, float $x, float $y, string $text, string $japaneseFont, float $size, array $color): float
    {
        $chunks = $this->splitMixedText($text);
        $baseBaseline = $canvas->get_font_baseline($japaneseFont, $size);
        $currentX = $x;
        foreach ($chunks as $chunk) {
            $font = $chunk['is_ascii'] ? 'Helvetica' : $japaneseFont;
            $width = $canvas->get_text_width($chunk['text'], $font, $size);
            $yOffset = $baseBaseline - $canvas->get_font_baseline($font, $size);
            $canvas->text($currentX, $y + $yOffset, $chunk['text'], $font, $size, $color);
            $currentX += $width;
        }

        return $currentX - $x;
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
     * カスタムSVG白地図ファイルから基本図形（path/rect/line/polygon/polyline）をパースして描画
     */
    protected function drawSvgFallbackBaseMapLayer(CPDF $canvas, SiteBaseMap $siteBaseMap): void
    {
        $svgFullPath = public_path((string) $siteBaseMap->svg_path);
        if (! is_file($svgFullPath)) {
            return;
        }

        $xml = (string) file_get_contents($svgFullPath);
        if (trim($xml) === '') {
            return;
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $prevErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($xml, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prevErrors);

        if (! $loaded || ! $dom->documentElement instanceof \DOMElement) {
            return;
        }

        $offsetX = 0.0;
        $offsetY = 0.0;
        $rawVb = trim($dom->documentElement->getAttribute('viewBox') ?: $dom->documentElement->getAttribute('viewbox'));
        $vbParts = preg_split('/[\s,]+/', $rawVb);
        if (is_array($vbParts) && count($vbParts) === 4) {
            $offsetX = (float) $vbParts[0];
            $offsetY = (float) $vbParts[1];
        }

        $this->renderSvgNodeForPdf($canvas, $dom->documentElement, 'none', '#1e293b', 0.5, $offsetX, $offsetY);
    }

    /**
     * SVGノードツリーを再帰走査して基本ベクター図形を描画
     */
    protected function renderSvgNodeForPdf(
        CPDF $canvas,
        \DOMElement $element,
        string $inheritedFill,
        string $inheritedStroke,
        float $inheritedStrokeWidth,
        float $offsetX,
        float $offsetY
    ): void {
        $tag = strtolower((string) ($element->localName ?: $element->nodeName));
        if (in_array($tag, ['defs', 'style', 'script', 'metadata', 'title', 'desc'], true)) {
            return;
        }

        $fillAttr = $element->hasAttribute('fill') ? trim($element->getAttribute('fill')) : $inheritedFill;
        $strokeAttr = $element->hasAttribute('stroke') ? trim($element->getAttribute('stroke')) : $inheritedStroke;
        $strokeW = $element->hasAttribute('stroke-width')
            ? max(0.1, (float) $element->getAttribute('stroke-width'))
            : $inheritedStrokeWidth;

        $fillRgb = $this->parseSvgColor($fillAttr);
        $strokeRgb = $this->parseSvgColor($strokeAttr);

        if ($tag === 'rect') {
            $rx = (float) $element->getAttribute('x') - $offsetX;
            $ry = (float) $element->getAttribute('y') - $offsetY;
            $rw = (float) $element->getAttribute('width');
            $rh = (float) $element->getAttribute('height');
            if ($rw > 0 && $rh > 0) {
                if ($fillRgb !== null) {
                    $canvas->filled_rectangle($rx, $ry, $rw, $rh, $fillRgb);
                }
                if ($strokeRgb !== null) {
                    $canvas->rectangle($rx, $ry, $rw, $rh, $strokeRgb, $strokeW);
                }
            }
        } elseif ($tag === 'line') {
            if ($strokeRgb !== null) {
                $canvas->line(
                    (float) $element->getAttribute('x1') - $offsetX,
                    (float) $element->getAttribute('y1') - $offsetY,
                    (float) $element->getAttribute('x2') - $offsetX,
                    (float) $element->getAttribute('y2') - $offsetY,
                    $strokeRgb,
                    $strokeW
                );
            }
        } elseif ($tag === 'polygon' || $tag === 'polyline') {
            $points = $this->parseSvgPoints($element->getAttribute('points'), $offsetX, $offsetY);
            if (count($points) >= 4) {
                if ($tag === 'polygon' && $fillRgb !== null && count($points) >= 6) {
                    $canvas->polygon($points, $fillRgb, null, [], true);
                }
                if ($strokeRgb !== null) {
                    for ($i = 0; $i < count($points) - 2; $i += 2) {
                        $canvas->line($points[$i], $points[$i + 1], $points[$i + 2], $points[$i + 3], $strokeRgb, $strokeW);
                    }
                    if ($tag === 'polygon' && count($points) >= 6) {
                        $last = count($points) - 2;
                        $canvas->line($points[$last], $points[$last + 1], $points[0], $points[1], $strokeRgb, $strokeW);
                    }
                }
            }
        } elseif ($tag === 'path') {
            $segments = $this->parseSvgPathToSegments($element->getAttribute('d'));
            foreach ($segments as $poly) {
                if ($offsetX !== 0.0 || $offsetY !== 0.0) {
                    for ($i = 0; $i < count($poly); $i += 2) {
                        $poly[$i] -= $offsetX;
                        if (isset($poly[$i + 1])) {
                            $poly[$i + 1] -= $offsetY;
                        }
                    }
                }
                if ($fillRgb !== null && count($poly) >= 6) {
                    $canvas->polygon($poly, $fillRgb, null, [], true);
                }
                if ($strokeRgb !== null) {
                    for ($i = 0; $i < count($poly) - 2; $i += 2) {
                        $canvas->line($poly[$i], $poly[$i + 1], $poly[$i + 2], $poly[$i + 3], $strokeRgb, $strokeW);
                    }
                }
            }
        }

        foreach ($element->childNodes as $child) {
            if ($child instanceof \DOMElement) {
                $this->renderSvgNodeForPdf($canvas, $child, $fillAttr, $strokeAttr, $strokeW, $offsetX, $offsetY);
            }
        }
    }

    /**
     * SVG の points 属性をフラット座標配列にパース
     *
     * @return array<int, float>
     */
    protected function parseSvgPoints(string $pointsAttr, float $offsetX, float $offsetY): array
    {
        $coords = [];
        if (preg_match_all('/-?\d+(?:\.\d+)?/', $pointsAttr, $matches)) {
            $nums = $matches[0];
            $count = count($nums);
            for ($i = 0; $i + 1 < $count; $i += 2) {
                $coords[] = (float) $nums[$i] - $offsetX;
                $coords[] = (float) $nums[$i + 1] - $offsetY;
            }
        }

        return $coords;
    }

    /**
     * SVG の色表現を [R, G, B] (0.0〜1.0) に変換（none/transparent は null）
     *
     * @return array{float, float, float}|null
     */
    protected function parseSvgColor(?string $color): ?array
    {
        if ($color === null) {
            return null;
        }

        $trimmed = strtolower(trim($color));
        if ($trimmed === '' || $trimmed === 'none' || $trimmed === 'transparent') {
            return null;
        }

        $named = [
            'black' => '#000000',
            'white' => '#ffffff',
            'gray' => '#808080',
            'grey' => '#808080',
            'red' => '#dc2626',
            'blue' => '#2563eb',
            'green' => '#16a34a',
        ];
        if (isset($named[$trimmed])) {
            $trimmed = $named[$trimmed];
        }

        if (preg_match('/^rgb\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/', $trimmed, $m) === 1) {
            return [
                min(255, (int) $m[1]) / 255.0,
                min(255, (int) $m[2]) / 255.0,
                min(255, (int) $m[3]) / 255.0,
            ];
        }

        $hex = ltrim($trimmed, '#');
        if (strlen($hex) === 3) {
            $hex = $hex[0].$hex[0].$hex[1].$hex[1].$hex[2].$hex[2];
        }
        if (strlen($hex) === 6 && ctype_xdigit($hex)) {
            return $this->hexToRgb($hex);
        }

        return [0.278, 0.333, 0.412];
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
