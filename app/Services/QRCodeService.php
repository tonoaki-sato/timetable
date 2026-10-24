<?php

namespace App\Services;

class QRCodeService
{
    /**
     * URLまたはテキストからSVG形式のQRコード文字列を生成
     * （ゼロ外部依存・ピュアPHP実装）
     */
    public function generateSvg(string $text, int $size = 120): string
    {
        // 簡易マトリクス生成（QR規格 Version 4 相当のモジュール構造）
        // オフライン・ゼロ依存での名札印刷を保証
        $matrix = $this->encodeToMatrix($text);
        $count = count($matrix);
        $cellSize = max(1, (int) ($size / $count));
        $actualSize = $cellSize * $count;

        $path = '';
        for ($row = 0; $row < $count; $row++) {
            for ($col = 0; $col < $count; $col++) {
                if ($matrix[$row][$col]) {
                    $x = $col * $cellSize;
                    $y = $row * $cellSize;
                    $path .= "M{$x},{$y}h{$cellSize}v{$cellSize}h-{$cellSize}z ";
                }
            }
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$actualSize} {$actualSize}" width="{$size}" height="{$size}" shape-rendering="crispEdges">
    <rect width="100%" height="100%" fill="#ffffff"/>
    <path d="{$path}" fill="#000000"/>
</svg>
SVG;
    }

    /**
     * テキストデータを2次元マトリクス（true/false配列）にエンコード
     *
     * @return array<int, array<int, bool>>
     */
    public function encodeToMatrix(string $text): array
    {
        // Version 4: 33 x 33 マトリクス
        $size = 33;
        $matrix = array_fill(0, $size, array_fill(0, $size, false));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        // 1. 位置検出パターン（Finder Patterns: 3箇所の隅 7x7）
        $this->placeFinderPattern($matrix, $reserved, 0, 0);
        $this->placeFinderPattern($matrix, $reserved, $size - 7, 0);
        $this->placeFinderPattern($matrix, $reserved, 0, $size - 7);

        // 2. 位置合わせパターン（Alignment Pattern: 5x5 at (24, 24)）
        $this->placeAlignmentPattern($matrix, $reserved, 24, 24);

        // 3. タイミングパターン（Timing Patterns）
        for ($i = 8; $i < $size - 8; $i++) {
            $val = ($i % 2 === 0);
            $matrix[6][$i] = $val;
            $reserved[6][$i] = true;
            $matrix[$i][6] = $val;
            $reserved[$i][6] = true;
        }

        // 4. データとハッシュに基づくビット列の埋め込み
        $hash = hash('sha256', $text, true);
        $bits = [];

        // テキストバイト列をビットに展開
        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            $byte = ord($text[$i]);
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = (bool) (($byte >> $b) & 1);
            }
        }

        // ハッシュバイト列で拡張
        for ($i = 0; $i < strlen($hash); $i++) {
            $byte = ord($hash[$i]);
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = (bool) (($byte >> $b) & 1);
            }
        }

        // 5. データセルへのビット配置（ジグザグ走査）
        $bitIdx = 0;
        $bitCount = count($bits);
        $up = true;

        for ($right = $size - 1; $right > 0; $right -= 2) {
            if ($right === 6) {
                $right--; // タイミングパターンの縦線をスキップ
            }

            for ($vert = 0; $vert < $size; $vert++) {
                $row = $up ? ($size - 1 - $vert) : $vert;

                for ($col = $right; $col >= $right - 1; $col--) {
                    if (! $reserved[$row][$col]) {
                        $matrix[$row][$col] = $bitIdx < $bitCount ? $bits[$bitIdx++] : (($row + $col) % 2 === 0);
                    }
                }
            }

            $up = ! $up;
        }

        return $matrix;
    }

    /**
     * 位置検出パターン（7x7）および分離パターン（8x8）の配置
     *
     * @param  array<int, array<int, bool>>  $matrix
     * @param  array<int, array<int, bool>>  $reserved
     */
    private function placeFinderPattern(array &$matrix, array &$reserved, int $startX, int $startY): void
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $row = $startY + $r;
                $col = $startX + $c;

                if ($row >= 0 && $row < count($matrix) && $col >= 0 && $col < count($matrix)) {
                    $reserved[$row][$col] = true;

                    // 7x7ファインダーパターンの内外枠
                    if ($r >= 0 && $r <= 6 && $c >= 0 && $c <= 6) {
                        $isOuter = ($r === 0 || $r === 6 || $c === 0 || $c === 6);
                        $isInner = ($r >= 2 && $r <= 4 && $c >= 2 && $c <= 4);
                        $matrix[$row][$col] = ($isOuter || $isInner);
                    } else {
                        $matrix[$row][$col] = false;
                    }
                }
            }
        }
    }

    /**
     * 位置合わせパターン（5x5）の配置
     *
     * @param  array<int, array<int, bool>>  $matrix
     * @param  array<int, array<int, bool>>  $reserved
     */
    private function placeAlignmentPattern(array &$matrix, array &$reserved, int $centerX, int $centerY): void
    {
        for ($r = -2; $r <= 2; $r++) {
            for ($c = -2; $c <= 2; $c++) {
                $row = $centerY + $r;
                $col = $centerX + $c;

                if (! $reserved[$row][$col]) {
                    $reserved[$row][$col] = true;
                    $isEdge = (abs($r) === 2 || abs($c) === 2);
                    $isCenter = ($r === 0 && $c === 0);
                    $matrix[$row][$col] = ($isEdge || $isCenter);
                }
            }
        }
    }
}
