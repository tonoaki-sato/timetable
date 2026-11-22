<?php

namespace Tests\Concerns;

use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;

trait AssertsPdfStarGlyphs
{
    /**
     * DomPDF で描画された CPDF ストリーム内の ★ / ☆ グリフが DejaVuSans で重なりなく描画されていることを検証
     */
    protected function assertPdfStarGlyphsRenderedWithoutOverlap(
        Dompdf $dompdf,
        int $minSolidStars = 1,
        int $minHollowStars = 1
    ): void {
        $canvas = $dompdf->getCanvas();
        $this->assertInstanceOf(CPDF::class, $canvas);

        /** @var CPDF $canvas */
        $cpdf = $canvas->get_cpdf();

        /** @var array<int, string> $fontMap */
        $fontMap = [];
        $hasDejaVuSans = false;
        foreach ($cpdf->fonts as $fontKey => $fontMeta) {
            $fontNum = (int) ($fontMeta['fontNum'] ?? 0);
            $fontKeyStr = (string) $fontKey;
            $fontMap[$fontNum] = $fontKeyStr;
            if (str_contains($fontKeyStr, 'DejaVuSans')) {
                $hasDejaVuSans = true;
            }
        }

        $this->assertTrue($hasDejaVuSans, 'DejaVuSans font should be embedded in the generated PDF.');

        $solidStarCount = 0;
        $hollowStarCount = 0;

        foreach ($cpdf->objects as $obj) {
            if (($obj['t'] ?? '') !== 'contents') {
                continue;
            }

            /** @var array<int, array{x: float, y: float, fontNum: int, size: float, raw: string}> $drawCalls */
            $drawCalls = [];
            foreach (explode("\n", (string) ($obj['c'] ?? '')) as $line) {
                $trimmed = trim($line);
                if ($trimmed === '') {
                    continue;
                }

                if (preg_match('/^BT\s+([0-9.\-]+)\s+([0-9.\-]+)\s+Td\s+\/F(\d+)\s+([0-9.\-]+)\s+Tf\s+\[\((.*)\)\]\s+TJ\s+ET$/s', $trimmed, $matches)) {
                    $drawCalls[] = [
                        'x' => (float) $matches[1],
                        'y' => (float) $matches[2],
                        'fontNum' => (int) $matches[3],
                        'size' => (float) $matches[4],
                        'raw' => $matches[5],
                    ];
                }
            }

            foreach ($drawCalls as $call) {
                $isSolidStar = $call['raw'] === "\x26\x05";
                $isHollowStar = $call['raw'] === "\x26\x06";

                if (! $isSolidStar && ! $isHollowStar) {
                    continue;
                }

                if ($isSolidStar) {
                    $solidStarCount++;
                } else {
                    $hollowStarCount++;
                }

                $fontName = $fontMap[$call['fontNum']] ?? '';
                $this->assertStringContainsString(
                    'DejaVuSans',
                    $fontName,
                    sprintf('Star glyph at (%.2f, %.2f) used unexpected font: %s', $call['x'], $call['y'], $fontName)
                );

                $nextCall = null;
                foreach ($drawCalls as $candidate) {
                    if (abs($candidate['y'] - $call['y']) < 0.5 && $candidate['x'] > $call['x']) {
                        if ($nextCall === null || $candidate['x'] < $nextCall['x']) {
                            $nextCall = $candidate;
                        }
                    }
                }

                $this->assertNotNull(
                    $nextCall,
                    sprintf('Expected subsequent text on the same line after star glyph at (%.2f, %.2f).', $call['x'], $call['y'])
                );

                $deltaX = $nextCall['x'] - $call['x'];
                $minRequiredDeltaX = $call['size'] * 0.85;
                $this->assertGreaterThanOrEqual(
                    $minRequiredDeltaX,
                    $deltaX,
                    sprintf(
                        'Star glyph overlaps following text at y=%.2f (deltaX=%.3f < min=%.3f).',
                        $call['y'],
                        $deltaX,
                        $minRequiredDeltaX
                    )
                );
            }
        }

        $this->assertGreaterThanOrEqual($minSolidStars, $solidStarCount, 'Expected solid star (★) draw calls.');
        $this->assertGreaterThanOrEqual($minHollowStars, $hollowStarCount, 'Expected hollow star (☆) draw calls.');
    }
}
