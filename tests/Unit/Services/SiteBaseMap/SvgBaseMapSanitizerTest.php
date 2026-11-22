<?php

namespace Tests\Unit\Services\SiteBaseMap;

use App\Services\SiteBaseMap\SvgBaseMapSanitizer;
use DomainException;
use Tests\TestCase;

class SvgBaseMapSanitizerTest extends TestCase
{
    private SvgBaseMapSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = new SvgBaseMapSanitizer;
    }

    public function test_it_throws_on_empty_content(): void
    {
        $this->expectException(DomainException::class);

        $this->sanitizer->sanitizeAndExtract("   \n\t  ");
    }

    public function test_it_throws_on_non_svg_or_malformed_xml(): void
    {
        $this->expectException(DomainException::class);

        $this->sanitizer->sanitizeAndExtract('<html><body><p>Not SVG</p></body></html>');
    }

    public function test_it_removes_forbidden_tags_event_handlers_and_dangerous_uris(): void
    {
        $rawSvg = <<<'SVG'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0, 0, 1200, 800" onload="alert(1)">
    <script>alert('xss')</script>
    <foreignObject><div>bad</div></foreignObject>
    <iframe src="https://evil.example"></iframe>
    <object data="https://evil.example"></object>
    <embed src="https://evil.example" />
    <g onclick="evil()">
        <a href="javascript:alert(1)"><text x="10" y="20">Bad Link</text></a>
        <a href="#safe-anchor"><rect x="10" y="10" width="50" height="30" style="fill: #fff; background: expression(alert(1))" /></a>
        <rect x="20" y="20" width="40" height="40" style="fill: #123456; stroke: #000;" />
        <image href="https://example.com/bg.png" formaction="vbscript:msgbox(1)" src="data:text/html,alert(1)" />
    </g>
</svg>
SVG;

        $result = $this->sanitizer->sanitizeAndExtract($rawSvg);

        $this->assertSame('0 0 1200 800', $result['viewbox']);
        $this->assertStringNotContainsString('<script', $result['xml']);
        $this->assertStringNotContainsString('foreignObject', $result['xml']);
        $this->assertStringNotContainsString('<iframe', $result['xml']);
        $this->assertStringNotContainsString('<object', $result['xml']);
        $this->assertStringNotContainsString('<embed', $result['xml']);
        $this->assertStringNotContainsString('onload=', $result['xml']);
        $this->assertStringNotContainsString('onclick=', $result['xml']);
        $this->assertStringNotContainsString('javascript:', $result['xml']);
        $this->assertStringNotContainsString('vbscript:', $result['xml']);
        $this->assertStringNotContainsString('data:text/html', $result['xml']);
        $this->assertStringNotContainsString('expression(', $result['xml']);

        $this->assertStringContainsString('#safe-anchor', $result['xml']);
        $this->assertStringContainsString('fill: #123456; stroke: #000;', $result['xml']);
    }

    public function test_it_resolves_viewbox_from_lowercase_attribute_width_height_or_default(): void
    {
        $lowercaseViewboxSvg = '<svg xmlns="http://www.w3.org/2000/svg" viewbox="-10.5  20  500.5, 300.25"><rect width="10" height="10"/></svg>';
        $this->assertSame('-10.5 20 500.5 300.25', $this->sanitizer->sanitizeAndExtract($lowercaseViewboxSvg)['viewbox']);

        $dimensionsSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="800px" height="600mm"><rect width="10" height="10"/></svg>';
        $this->assertSame('0 0 800 600', $this->sanitizer->sanitizeAndExtract($dimensionsSvg)['viewbox']);

        $percentSvg = '<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="100%"><rect width="10" height="10"/></svg>';
        $this->assertSame('0 0 842 1191', $this->sanitizer->sanitizeAndExtract($percentSvg)['viewbox']);
    }
}
