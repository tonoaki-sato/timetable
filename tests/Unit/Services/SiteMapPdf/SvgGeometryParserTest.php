<?php

namespace Tests\Unit\Services\SiteMapPdf;

use App\Services\SiteMapPdf\SvgGeometryParser;
use PHPUnit\Framework\TestCase;

class SvgGeometryParserTest extends TestCase
{
    private SvgGeometryParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new SvgGeometryParser;
    }

    public function test_color_theme_to_rgb_returns_expected_and_fallback_red(): void
    {
        $this->assertSame([0.145, 0.388, 0.922], $this->parser->colorThemeToRgb('blue'));
        $this->assertSame([0.059, 0.090, 0.165], $this->parser->colorThemeToRgb('black'));
        $this->assertSame([0.863, 0.149, 0.149], $this->parser->colorThemeToRgb('unknown-theme'));
    }

    public function test_parse_svg_path_to_segments_handles_multiple_subpaths_and_close_command(): void
    {
        $d = 'M 10 20 L 30 20 L 30 40 Z M 50.5 60.5 L 70.5 80.5';
        $segments = $this->parser->parseSvgPathToSegments($d);

        $this->assertCount(2, $segments);
        $this->assertSame([10.0, 20.0, 30.0, 20.0, 30.0, 40.0, 10.0, 20.0], $segments[0]);
        $this->assertSame([50.5, 60.5, 70.5, 80.5], $segments[1]);
    }

    public function test_parse_svg_points_applies_viewbox_offset(): void
    {
        $coords = $this->parser->parseSvgPoints('100,200 150.5 225.5', 10.0, 20.0);

        $this->assertSame([90.0, 180.0, 140.5, 205.5], $coords);
    }

    public function test_parse_svg_color_handles_none_named_hex_and_rgb(): void
    {
        $this->assertNull($this->parser->parseSvgColor(null));
        $this->assertNull($this->parser->parseSvgColor('none'));
        $this->assertNull($this->parser->parseSvgColor(' transparent '));

        $this->assertSame([0.0, 0.0, 0.0], $this->parser->parseSvgColor('black'));
        $this->assertSame([1.0, 1.0, 1.0], $this->parser->parseSvgColor('#fff'));
        $this->assertSame([1.0, 0.0, 0.0], $this->parser->parseSvgColor('#FF0000'));
        $this->assertSame([0.0, 1.0, 0.0], $this->parser->parseSvgColor('rgb(0, 255, 0)'));
        $this->assertSame([0.278, 0.333, 0.412], $this->parser->parseSvgColor('currentColor'));
    }

    public function test_hex_to_rgb_handles_valid_and_invalid_lengths(): void
    {
        $rgb = $this->parser->hexToRgb('#1e293b');
        $this->assertEqualsWithDelta(30 / 255.0, $rgb[0], 0.0001);
        $this->assertEqualsWithDelta(41 / 255.0, $rgb[1], 0.0001);
        $this->assertEqualsWithDelta(59 / 255.0, $rgb[2], 0.0001);

        $this->assertSame([0.278, 0.333, 0.412], $this->parser->hexToRgb('#12345'));
    }
}
