<?php

namespace App\Services\SiteMapPdf;

use App\Models\SiteMapPlacement;
use Dompdf\Adapter\CPDF;

class PdfMixedTextRenderer
{
    /**
     * 文字列を ASCII印刷可能文字（半角英数字・記号）と非ASCII（日本語等）のチャンク配列に分割
     *
     * @return array<int, array{text: string, is_ascii: bool}>
     */
    public function splitMixedText(string $text): array
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
     * 日本語と英数字・ASCII記号が混在する文字列をフォント切り替え（Helvetica / japanesefont）しながら連続描画
     *
     * @param  array{float, float, float}  $color
     */
    public function drawMixedText(
        CPDF $canvas,
        float $x,
        float $y,
        string $text,
        string $japaneseFont,
        float $size,
        array $color
    ): float {
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
     * 白抜き背景マスク付きの中央テキスト描画（複数行対応）
     *
     * @param  array{float, float, float}  $rgb
     */
    public function drawCenteredTextWithMask(
        CPDF $canvas,
        string $font,
        SiteMapPlacement $placement,
        float $x,
        float $y,
        float $w,
        float $h,
        array $rgb
    ): void {
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
}
