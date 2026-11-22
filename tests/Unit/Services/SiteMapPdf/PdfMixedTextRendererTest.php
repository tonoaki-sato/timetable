<?php

namespace Tests\Unit\Services\SiteMapPdf;

use App\Services\SiteMapPdf\PdfMixedTextRenderer;
use PHPUnit\Framework\TestCase;

class PdfMixedTextRendererTest extends TestCase
{
    private PdfMixedTextRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new PdfMixedTextRenderer;
    }

    public function test_split_mixed_text_handles_empty_japanese_ascii_and_mixed_strings(): void
    {
        $this->assertSame([], $this->renderer->splitMixedText(''));

        $this->assertSame([
            ['text' => 'WANDA', 'is_ascii' => true],
        ], $this->renderer->splitMixedText('WANDA'));

        $this->assertSame([
            ['text' => 'まるお酒場', 'is_ascii' => false],
        ], $this->renderer->splitMixedText('まるお酒場'));

        $this->assertSame([
            ['text' => '保土ヶ谷宿場まつり', 'is_ascii' => false],
            ['text' => ' 1/400 A3 ', 'is_ascii' => true],
            ['text' => '配置図', 'is_ascii' => false],
        ], $this->renderer->splitMixedText('保土ヶ谷宿場まつり 1/400 A3 配置図'));
    }
}
