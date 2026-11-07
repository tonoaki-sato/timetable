<?php

namespace Tests\Unit\Services;

use App\Services\QRCodeService;
use PHPUnit\Framework\TestCase;

class QRCodeServiceTest extends TestCase
{
    private QRCodeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new QRCodeService;
    }

    /**
     * テキストから規格準拠のQRマトリクス（true/false 2次元配列）が生成されること
     */
    public function test_can_encode_url_to_matrix(): void
    {
        $url = 'https://timetable.tonoaki-sato.home/staff/myshift?token=abcdef1234567890abcdef1234567890';
        $matrix = $this->service->encodeToMatrix($url);

        $this->assertIsArray($matrix);
        $size = count($matrix);
        $this->assertGreaterThanOrEqual(21, $size);
        $this->assertSame($size, count($matrix[0]));

        // 3隅のファインダーパターン（7x7）の外枠黒・中心黒の検証
        // 左上 (0,0)〜(6,6)
        $this->assertTrue($matrix[0][0]);
        $this->assertTrue($matrix[0][6]);
        $this->assertTrue($matrix[6][0]);
        $this->assertTrue($matrix[6][6]);
        $this->assertTrue($matrix[3][3]);
        $this->assertFalse($matrix[1][1]); // 白枠部

        // 右上 (0, size-7)
        $this->assertTrue($matrix[0][$size - 7]);
        $this->assertTrue($matrix[0][$size - 1]);
        $this->assertTrue($matrix[3][$size - 4]);

        // 左下 (size-7, 0)
        $this->assertTrue($matrix[$size - 7][0]);
        $this->assertTrue($matrix[$size - 1][0]);
        $this->assertTrue($matrix[$size - 4][3]);
    }

    /**
     * PNG Data URI形式のQR画像が生成されること
     */
    public function test_can_generate_png_data_uri(): void
    {
        $url = 'https://example.com/test';
        $dataUri = $this->service->generatePngDataUri($url, 140);

        $this->assertStringStartsWith('data:image/png;base64,', $dataUri);
        $base64 = substr($dataUri, strlen('data:image/png;base64,'));
        $binary = base64_decode($base64, true);

        $this->assertNotFalse($binary);
        // PNGマジックナンバー（\x89PNG\r\n\x1a\n）の検証
        $this->assertStringStartsWith("\x89PNG\r\n\x1a\n", $binary);
    }

    /**
     * 既存互換のSVG文字列が生成されること
     */
    public function test_can_generate_svg(): void
    {
        $url = 'https://example.com/svg-test';
        $svg = $this->service->generateSvg($url, 120);

        $this->assertStringStartsWith('<svg', $svg);
        $this->assertStringEndsWith('</svg>', trim($svg));
        $this->assertStringContainsString('viewBox=', $svg);
        $this->assertStringContainsString('<path', $svg);
    }
}
