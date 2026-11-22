<?php

namespace App\Services\SiteMapPdf;

class SvgGeometryParser
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
     * カラーテーマ名からRGB値 [R, G, B] (0.0〜1.0) を取得（未定義時は red）
     *
     * @return array{float, float, float}
     */
    public function colorThemeToRgb(string $theme): array
    {
        return self::COLOR_RGB_MAP[$theme] ?? self::COLOR_RGB_MAP['red'];
    }

    /**
     * SVGパス `d` 文字列をポリゴン座標配列にパース
     *
     * @return array<int, array<int, float>>
     */
    public function parseSvgPathToSegments(string $d): array
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
     * SVG の points 属性をフラット座標配列にパース
     *
     * @return array<int, float>
     */
    public function parseSvgPoints(string $pointsAttr, float $offsetX, float $offsetY): array
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
    public function parseSvgColor(?string $color): ?array
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
    public function hexToRgb(string $hex): array
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
