<?php

namespace App\Services\SiteMapPdf;

use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use Dompdf\Adapter\CPDF;
use Illuminate\Database\Eloquent\Collection;

class BaseMapVectorRenderer
{
    /**
     * パース済み白地図ベースデータのプロセス内キャッシュ
     *
     * @var array<string, array{paths: array<int, array<string, mixed>>, labels: array<int, array<string, mixed>>}>
     */
    protected static array $parsedBaseMapCache = [];

    public function __construct(
        protected SvgGeometryParser $svgParser,
        protected PdfMixedTextRenderer $textRenderer,
    ) {}

    /**
     * 白地図ベース（道路・建物枠・3大駐車場詳細・凡例・タイトル枠・修正済建物名）を描画
     *
     * @param  Collection<int, SiteMapPlacement>  $placements
     */
    public function drawPresetJsonBaseMap(CPDF $canvas, string $font, Collection $placements): void
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
                $this->textRenderer->drawMixedText($canvas, $x, $y - $size * 0.82, $text, $font, $size, $labelColor);
            }
        }

        // 凡例「手洗い設備設置 Nヵ所」の動的数値描画
        $waterCount = $placements->where('shape_type', 'water_supply')->count();
        $canvas->filled_rectangle(82.0, 169.0, 100.0, 10.5, [1.0, 1.0, 1.0]);
        $this->textRenderer->drawMixedText($canvas, 84.30, 170.5, "手洗い設備設置　{$waterCount}ヵ所", $font, 7.8, $labelColor);
    }

    /**
     * カスタムSVG白地図ファイルから基本図形（path/rect/line/polygon/polyline）をパースして描画
     */
    public function drawSvgFallbackBaseMap(CPDF $canvas, SiteBaseMap $siteBaseMap): void
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
            $segments = $this->svgParser->parseSvgPathToSegments($d);
            if ($segments === []) {
                continue;
            }

            $fill = $pathItem['fill'] ?? null;
            $stroke = $pathItem['stroke'] ?? null;

            $parsedPaths[] = [
                'segments' => $segments,
                'fill_rgb' => $fill !== null ? $this->svgParser->hexToRgb((string) $fill) : null,
                'stroke_rgb' => $stroke !== null ? $this->svgParser->hexToRgb((string) $stroke) : null,
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

        $fillRgb = $this->svgParser->parseSvgColor($fillAttr);
        $strokeRgb = $this->svgParser->parseSvgColor($strokeAttr);

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
            $points = $this->svgParser->parseSvgPoints($element->getAttribute('points'), $offsetX, $offsetY);
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
            $segments = $this->svgParser->parseSvgPathToSegments($element->getAttribute('d'));
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
}
