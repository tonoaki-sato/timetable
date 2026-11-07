<?php

namespace App\Services;

use InvalidArgumentException;

class QRCodeService
{
    private static bool $gfInit = false;

    /** @var array<int, int> */
    private static array $exp = [];

    /** @var array<int, int> */
    private static array $log = [];

    /**
     * QRコード Version 1〜10 (誤り訂正レベル M) の規格テーブル
     * [TotalCodewords, EcPerBlock, [[numBlocks, dataCodewordsPerBlock], ...], [alignCoords]]
     *
     * @var array<int, array{0: int, 1: int, 2: array<int, array{0: int, 1: int}>, 3: array<int, int>}>
     */
    private static array $table = [
        1 => [26, 10, [[1, 16]], []],
        2 => [44, 16, [[1, 28]], [6, 18]],
        3 => [70, 26, [[1, 44]], [6, 22]],
        4 => [100, 18, [[2, 32]], [6, 26]],
        5 => [134, 24, [[2, 43]], [6, 30]],
        6 => [172, 16, [[4, 27]], [6, 34]],
        7 => [196, 18, [[4, 31]], [6, 22, 38]],
        8 => [242, 22, [[2, 38], [2, 39]], [6, 24, 42]],
        9 => [292, 22, [[3, 36], [2, 37]], [6, 26, 46]],
        10 => [346, 26, [[4, 43], [1, 44]], [6, 28, 50]],
    ];

    /**
     * テキストまたはURLから規格準拠のPNG Data URI形式のQRコードを生成
     */
    public function generatePngDataUri(string $text, int $size = 140): string
    {
        $matrix = $this->encodeToMatrix($text);
        $count = count($matrix);
        $margin = 4; // 規格準拠のクワイエットゾーン（4モジュール）
        $totalModules = $count + ($margin * 2);

        $scale = max(2, (int) round($size / $totalModules));
        $imgSize = $totalModules * $scale;

        $im = imagecreatetruecolor($imgSize, $imgSize);
        if ($im === false) {
            throw new InvalidArgumentException('GDの画像生成に失敗しました。');
        }

        $white = imagecolorallocate($im, 255, 255, 255);
        $black = imagecolorallocate($im, 0, 0, 0);

        imagefilledrectangle($im, 0, 0, $imgSize - 1, $imgSize - 1, $white);

        for ($r = 0; $r < $count; $r++) {
            for ($c = 0; $c < $count; $c++) {
                if ($matrix[$r][$c]) {
                    $x1 = ($c + $margin) * $scale;
                    $y1 = ($r + $margin) * $scale;
                    $x2 = $x1 + $scale - 1;
                    $y2 = $y1 + $scale - 1;
                    imagefilledrectangle($im, $x1, $y1, $x2, $y2, $black);
                }
            }
        }

        ob_start();
        imagepng($im);
        $pngData = (string) ob_get_clean();

        return 'data:image/png;base64,'.base64_encode($pngData);
    }

    /**
     * URLまたはテキストからSVG形式のQRコード文字列を生成（互換性維持）
     */
    public function generateSvg(string $text, int $size = 120): string
    {
        $matrix = $this->encodeToMatrix($text);
        $count = count($matrix);
        $margin = 4;
        $totalModules = $count + ($margin * 2);
        $scale = 3;
        $viewBoxSize = $totalModules * $scale;

        $path = '';
        for ($row = 0; $row < $count; $row++) {
            for ($col = 0; $col < $count; $col++) {
                if ($matrix[$row][$col]) {
                    $x = ($col + $margin) * $scale;
                    $y = ($row + $margin) * $scale;
                    $path .= "M{$x},{$y}h{$scale}v{$scale}h-{$scale}z ";
                }
            }
        }

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {$viewBoxSize} {$viewBoxSize}" width="{$size}" height="{$size}" shape-rendering="crispEdges">
    <rect width="100%" height="100%" fill="#ffffff"/>
    <path d="{$path}" fill="#000000"/>
</svg>
SVG;
    }

    /**
     * テキストデータを2次元マトリクス（true/false配列）にエンコード（JIS X 0510準拠）
     *
     * @return array<int, array<int, bool>>
     */
    public function encodeToMatrix(string $text): array
    {
        $len = strlen($text);
        $version = 0;
        $totalDataCapacity = 0;

        foreach (self::$table as $v => $spec) {
            $cap = 0;
            foreach ($spec[2] as $group) {
                $cap += $group[0] * $group[1];
            }
            $charCountBits = ($v <= 9) ? 8 : 16;
            $neededBits = 4 + $charCountBits + ($len * 8);
            if ($neededBits <= ($cap * 8)) {
                $version = $v;
                $totalDataCapacity = $cap;
                break;
            }
        }

        if ($version === 0) {
            throw new InvalidArgumentException("名札QRコードの文字数超過（最大Version 10まで対応）: {$len} bytes");
        }

        $spec = self::$table[$version];
        $ecPerBlock = $spec[1];
        $groups = $spec[2];
        $alignCoords = $spec[3];

        // 1. ビットストリーム生成（Byte mode = 0100）
        $bits = [0, 1, 0, 0];
        $charCountBits = ($version <= 9) ? 8 : 16;
        for ($b = $charCountBits - 1; $b >= 0; $b--) {
            $bits[] = ($len >> $b) & 1;
        }
        for ($i = 0; $i < $len; $i++) {
            $c = ord($text[$i]);
            for ($b = 7; $b >= 0; $b--) {
                $bits[] = ($c >> $b) & 1;
            }
        }

        // 終端パターン（最大4ビットのゼロ）
        $maxDataBits = $totalDataCapacity * 8;
        $termLen = min(4, $maxDataBits - count($bits));
        for ($i = 0; $i < $termLen; $i++) {
            $bits[] = 0;
        }

        // 8ビット境界にパディング
        while (count($bits) % 8 !== 0) {
            $bits[] = 0;
        }

        // データ語バイト列へ変換
        $dataBytes = [];
        for ($i = 0; $i < count($bits); $i += 8) {
            $byte = 0;
            for ($b = 0; $b < 8; $b++) {
                $byte = ($byte << 1) | $bits[$i + $b];
            }
            $dataBytes[] = $byte;
        }

        // 規格パディング語（0xEC, 0x11）で容量を満たす
        $pad = [0xEC, 0x11];
        $padIdx = 0;
        while (count($dataBytes) < $totalDataCapacity) {
            $dataBytes[] = $pad[$padIdx];
            $padIdx = 1 - $padIdx;
        }

        // 2. ブロック分割 & リード・ソロモン誤り訂正コード計算
        $dataBlocks = [];
        $ecBlocks = [];
        $dataOffset = 0;
        foreach ($groups as $group) {
            $numBlocks = $group[0];
            $dataPerBlock = $group[1];
            for ($b = 0; $b < $numBlocks; $b++) {
                $block = array_slice($dataBytes, $dataOffset, $dataPerBlock);
                $dataBlocks[] = $block;
                $ecBlocks[] = self::calculateEc($block, $ecPerBlock);
                $dataOffset += $dataPerBlock;
            }
        }

        // 3. データ語および誤り訂正語のインターリーブ
        $interleaved = [];
        $maxDataBlockLen = 0;
        foreach ($dataBlocks as $b) {
            if (count($b) > $maxDataBlockLen) {
                $maxDataBlockLen = count($b);
            }
        }
        for ($i = 0; $i < $maxDataBlockLen; $i++) {
            foreach ($dataBlocks as &$block) {
                if ($i < count($block)) {
                    $interleaved[] = $block[$i];
                }
            }
        }
        for ($i = 0; $i < $ecPerBlock; $i++) {
            foreach ($ecBlocks as &$block) {
                $interleaved[] = $block[$i];
            }
        }

        // インターリーブされたコード語をビット列へ
        $finalBits = [];
        foreach ($interleaved as $byte) {
            for ($b = 7; $b >= 0; $b--) {
                $finalBits[] = ($byte >> $b) & 1;
            }
        }

        // 4. マトリクス構築と機能パターン配置
        $size = ($version - 1) * 4 + 21;
        $matrix = array_fill(0, $size, array_fill(0, $size, false));
        $reserved = array_fill(0, $size, array_fill(0, $size, false));

        // ファインダーパターン（3隅）
        self::placeFinder($matrix, $reserved, 0, 0);
        self::placeFinder($matrix, $reserved, $size - 7, 0);
        self::placeFinder($matrix, $reserved, 0, $size - 7);

        // 位置合わせパターン（アライメント）
        if (count($alignCoords) > 0) {
            foreach ($alignCoords as $r) {
                foreach ($alignCoords as $c) {
                    if ($reserved[$r][$c]) {
                        continue;
                    }
                    self::placeAlignment($matrix, $reserved, $c, $r);
                }
            }
        }

        // タイミングパターン
        for ($i = 8; $i < $size - 8; $i++) {
            if (! $reserved[6][$i]) {
                $matrix[6][$i] = ($i % 2 === 0);
                $reserved[6][$i] = true;
            }
            if (! $reserved[$i][6]) {
                $matrix[$i][6] = ($i % 2 === 0);
                $reserved[$i][6] = true;
            }
        }

        // ダークモジュール
        $matrix[4 * $version + 9][8] = true;
        $reserved[4 * $version + 9][8] = true;

        // 形式情報予約エリア
        for ($i = 0; $i <= 8; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }
        for ($i = $size - 8; $i < $size; $i++) {
            $reserved[8][$i] = true;
            $reserved[$i][8] = true;
        }

        // バージョン情報予約エリア (v >= 7)
        if ($version >= 7) {
            for ($r = 0; $r < 6; $r++) {
                for ($c = $size - 11; $c < $size - 8; $c++) {
                    $reserved[$r][$c] = true;
                    $reserved[$c][$r] = true;
                }
            }
        }

        // データビットをジグザグ走査で配置
        $bitIdx = 0;
        $bitCount = count($finalBits);
        $up = true;
        for ($right = $size - 1; $right > 0; $right -= 2) {
            if ($right === 6) {
                $right--;
            }
            for ($vert = 0; $vert < $size; $vert++) {
                $r = $up ? ($size - 1 - $vert) : $vert;
                for ($c = $right; $c >= $right - 1; $c--) {
                    if (! $reserved[$r][$c]) {
                        $matrix[$r][$c] = $bitIdx < $bitCount ? ($finalBits[$bitIdx++] === 1) : false;
                    }
                }
            }
            $up = ! $up;
        }

        // 5. 8種類のマスクを評価しペナルティ最小のマスクを選択
        $minPenalty = PHP_INT_MAX;
        $bestMatrix = $matrix;

        for ($mask = 0; $mask < 8; $mask++) {
            $testMatrix = $matrix;
            for ($r = 0; $r < $size; $r++) {
                for ($c = 0; $c < $size; $c++) {
                    if (! $reserved[$r][$c]) {
                        if (self::maskCondition($mask, $r, $c)) {
                            $testMatrix[$r][$c] = ! $testMatrix[$r][$c];
                        }
                    }
                }
            }
            // 形式情報を埋め込む（Level M = 00）
            self::embedFormatInfo($testMatrix, 0, $mask);
            $penalty = self::calculatePenalty($testMatrix, $size);
            if ($penalty < $minPenalty) {
                $minPenalty = $penalty;
                $bestMatrix = $testMatrix;
            }
        }

        return $bestMatrix;
    }

    private static function initGf(): void
    {
        if (self::$gfInit) {
            return;
        }
        self::$exp = array_fill(0, 512, 0);
        self::$log = array_fill(0, 256, 0);
        $x = 1;
        for ($i = 0; $i < 255; $i++) {
            self::$exp[$i] = $x;
            self::$log[$x] = $i;
            $x <<= 1;
            if ($x & 0x100) {
                $x ^= 0x11D;
            }
        }
        for ($i = 255; $i < 512; $i++) {
            self::$exp[$i] = self::$exp[$i - 255];
        }
        self::$gfInit = true;
    }

    private static function gfMul(int $x, int $y): int
    {
        if ($x === 0 || $y === 0) {
            return 0;
        }

        return self::$exp[self::$log[$x] + self::$log[$y]];
    }

    /**
     * @return array<int, int>
     */
    private static function rsPoly(int $n): array
    {
        $poly = [1];
        for ($i = 0; $i < $n; $i++) {
            $next = array_fill(0, count($poly) + 1, 0);
            $factor = self::$exp[$i];
            for ($j = 0; $j < count($poly); $j++) {
                $next[$j] ^= $poly[$j];
                $next[$j + 1] ^= self::gfMul($poly[$j], $factor);
            }
            $poly = $next;
        }

        return $poly;
    }

    /**
     * @param  array<int, int>  $data
     * @return array<int, int>
     */
    private static function calculateEc(array $data, int $ecCount): array
    {
        self::initGf();
        $gen = self::rsPoly($ecCount);
        $remainder = array_fill(0, $ecCount, 0);
        foreach ($data as $byte) {
            $factor = $byte ^ $remainder[0];
            array_shift($remainder);
            $remainder[] = 0;
            if ($factor !== 0) {
                for ($i = 0; $i < $ecCount; $i++) {
                    $remainder[$i] ^= self::gfMul($gen[$i + 1], $factor);
                }
            }
        }

        return $remainder;
    }

    private static function maskCondition(int $mask, int $r, int $c): bool
    {
        return match ($mask) {
            0 => ($r + $c) % 2 === 0,
            1 => $r % 2 === 0,
            2 => $c % 3 === 0,
            3 => ($r + $c) % 3 === 0,
            4 => ((int) ($r / 2) + (int) ($c / 3)) % 2 === 0,
            5 => (($r * $c) % 2) + (($r * $c) % 3) === 0,
            6 => (((($r * $c) % 2) + (($r * $c) % 3)) % 2) === 0,
            7 => (((($r + $c) % 2) + (($r * $c) % 3)) % 2) === 0,
            default => false,
        };
    }

    /**
     * @param  array<int, array<int, bool>>  $matrix
     */
    private static function embedFormatInfo(array &$matrix, int $errorLevel, int $mask): void
    {
        $size = count($matrix);
        // Level M = 0 (2-bit: L=01, M=00, Q=11, H=10)
        $formatData = ($errorLevel << 3) | $mask;
        $d = $formatData << 10;
        $poly = 0x537;
        for ($i = 14; $i >= 10; $i--) {
            if ($d & (1 << $i)) {
                $d ^= $poly << ($i - 10);
            }
        }
        $formatBits = (($formatData << 10) | $d) ^ 0x5412;

        // ISO/IEC 18004 Format bits placement (bit 14=MSB to bit 0=LSB)
        $bits = [];
        for ($i = 14; $i >= 0; $i--) {
            $bits[] = (bool) (($formatBits >> $i) & 1);
        }

        // Top-left
        $matrix[8][0] = $bits[0];
        $matrix[8][1] = $bits[1];
        $matrix[8][2] = $bits[2];
        $matrix[8][3] = $bits[3];
        $matrix[8][4] = $bits[4];
        $matrix[8][5] = $bits[5];
        $matrix[8][7] = $bits[6]; // col 6 is timing
        $matrix[8][8] = $bits[7];
        $matrix[7][8] = $bits[8];
        $matrix[5][8] = $bits[9]; // row 6 is timing
        $matrix[4][8] = $bits[10];
        $matrix[3][8] = $bits[11];
        $matrix[2][8] = $bits[12];
        $matrix[1][8] = $bits[13];
        $matrix[0][8] = $bits[14];

        // Bottom-left & Top-right
        for ($i = 0; $i <= 7; $i++) {
            $matrix[$size - 1 - $i][8] = $bits[$i];
        }
        for ($i = 8; $i < 15; $i++) {
            $matrix[8][$size - 15 + $i] = $bits[$i];
        }
    }

    /**
     * @param  array<int, array<int, bool>>  $matrix
     */
    private static function calculatePenalty(array &$matrix, int $size): int
    {
        $penalty = 0;

        // Rule 1: 5 or more consecutive same color modules
        for ($r = 0; $r < $size; $r++) {
            $runColor = $matrix[$r][0];
            $runLen = 1;
            for ($c = 1; $c < $size; $c++) {
                if ($matrix[$r][$c] === $runColor) {
                    $runLen++;
                } else {
                    if ($runLen >= 5) {
                        $penalty += 3 + ($runLen - 5);
                    }
                    $runColor = $matrix[$r][$c];
                    $runLen = 1;
                }
            }
            if ($runLen >= 5) {
                $penalty += 3 + ($runLen - 5);
            }
        }

        for ($c = 0; $c < $size; $c++) {
            $runColor = $matrix[0][$c];
            $runLen = 1;
            for ($r = 1; $r < $size; $r++) {
                if ($matrix[$r][$c] === $runColor) {
                    $runLen++;
                } else {
                    if ($runLen >= 5) {
                        $penalty += 3 + ($runLen - 5);
                    }
                    $runColor = $matrix[$r][$c];
                    $runLen = 1;
                }
            }
            if ($runLen >= 5) {
                $penalty += 3 + ($runLen - 5);
            }
        }

        // Rule 2: 2x2 blocks of same color
        for ($r = 0; $r < $size - 1; $r++) {
            for ($c = 0; $c < $size - 1; $c++) {
                $val = $matrix[$r][$c];
                if ($val === $matrix[$r + 1][$c] && $val === $matrix[$r][$c + 1] && $val === $matrix[$r + 1][$c + 1]) {
                    $penalty += 3;
                }
            }
        }

        return $penalty;
    }

    /**
     * @param  array<int, array<int, bool>>  $matrix
     * @param  array<int, array<int, bool>>  $reserved
     */
    private static function placeFinder(array &$matrix, array &$reserved, int $startX, int $startY): void
    {
        for ($r = -1; $r <= 7; $r++) {
            for ($c = -1; $c <= 7; $c++) {
                $row = $startY + $r;
                $col = $startX + $c;

                if ($row >= 0 && $row < count($matrix) && $col >= 0 && $col < count($matrix)) {
                    $reserved[$row][$col] = true;

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
     * @param  array<int, array<int, bool>>  $matrix
     * @param  array<int, array<int, bool>>  $reserved
     */
    private static function placeAlignment(array &$matrix, array &$reserved, int $centerX, int $centerY): void
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
