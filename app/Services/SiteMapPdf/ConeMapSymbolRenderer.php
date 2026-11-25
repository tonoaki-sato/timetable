<?php

namespace App\Services\SiteMapPdf;

use App\DTOs\ConeMap\ConeMapSummaryDTO;
use App\Enums\BarricadeType;
use App\Enums\ConeBarStyle;
use App\Enums\ConeCalloutColor;
use App\Enums\ConeColor;
use App\Models\ConeMapBar;
use App\Models\ConeMapBarricade;
use App\Models\ConeMapCallout;
use App\Models\ConeMapCone;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use Dompdf\Adapter\CPDF;
use Illuminate\Database\Eloquent\Collection;

class ConeMapSymbolRenderer
{
    public function __construct(
        protected PdfMixedTextRenderer $textRenderer,
    ) {}

    /**
     * 既存ブース配置の参考レイヤーを淡色細線で描画
     *
     * @param  Collection<int, SiteMapPlacement>  $boothPlacements
     */
    public function drawBoothReferenceLayer(CPDF $canvas, Collection $boothPlacements): void
    {
        $strokeRgb = [0.74, 0.78, 0.84];
        $fillRgb = [0.96, 0.97, 0.98];

        foreach ($boothPlacements as $placement) {
            $x = (float) $placement->pos_x;
            $y = (float) $placement->pos_y;
            $w = (float) $placement->width;
            $h = (float) $placement->height;
            $rot = (int) $placement->rotation;

            $canvas->save();
            if ($rot !== 0) {
                $canvas->rotate($rot, $x + $w / 2, $y + $h / 2);
            }
            $canvas->filled_rectangle($x, $y, $w, $h, $fillRgb);
            $canvas->rectangle($x, $y, $w, $h, $strokeRgb, 0.30);
            $canvas->restore();
        }
    }

    /**
     * 2個のカラーコーン間を連結するコーンバーを描画（長さ200cm・太さ3.4cm）
     */
    public function drawConeBar(
        CPDF $canvas,
        ConeMapBar $bar,
        ConeMapCone $startCone,
        ConeMapCone $endCone,
        float $scaleRatio,
        bool $enhanced
    ): void {
        $x1 = (float) $startCone->pos_x;
        $y1 = (float) $startCone->pos_y;
        $x2 = (float) $endCone->pos_x;
        $y2 = (float) $endCone->pos_y;

        $style = ConeBarStyle::tryFrom((string) $bar->bar_style) ?? ConeBarStyle::YellowBlack;
        $primaryRgb = $style->primaryRgb();
        $secondaryRgb = $style->secondaryRgb();

        $realWidth = max(0.24, (ConeMapBar::DEFAULT_DIAMETER_CM / 100.0) * $scaleRatio);
        $strokeWidth = $enhanced ? max(1.9, $realWidth * 7.5) : $realWidth;
        $haloWidth = $strokeWidth + ($enhanced ? 0.9 : 0.35);

        // 視認性保護の白フチ下地
        $canvas->line($x1, $y1, $x2, $y2, [1.0, 1.0, 1.0], $haloWidth);

        // ストライプ交互描画
        $dx = $x2 - $x1;
        $dy = $y2 - $y1;
        $dist = hypot($dx, $dy);
        $segments = max(4, min(20, (int) round($dist / 1.8)));

        for ($i = 0; $i < $segments; $i++) {
            $t1 = $i / $segments;
            $t2 = ($i + 1) / $segments;
            $sx1 = $x1 + $dx * $t1;
            $sy1 = $y1 + $dy * $t1;
            $sx2 = $x1 + $dx * $t2;
            $sy2 = $y1 + $dy * $t2;
            $segColor = ($i % 2 === 0) ? $primaryRgb : $secondaryRgb;
            $canvas->line($sx1, $sy1, $sx2, $sy2, $segColor, $strokeWidth);
        }

        // 外枠輪郭線（白抜きセグメントが白地図と同化するのを防止）
        if ($enhanced && $dist > 0.5) {
            $nx = (-$dy / $dist) * ($strokeWidth / 2);
            $ny = ($dx / $dist) * ($strokeWidth / 2);
            $outlineRgb = [0.15, 0.20, 0.28];
            $canvas->line($x1 + $nx, $y1 + $ny, $x2 + $nx, $y2 + $ny, $outlineRgb, 0.25);
            $canvas->line($x1 - $nx, $y1 - $ny, $x2 - $nx, $y2 - $ny, $outlineRgb, 0.25);
        }
    }

    /**
     * A型バリケード / 樹脂製単管バリケードを描画
     */
    public function drawBarricade(
        CPDF $canvas,
        string $font,
        ConeMapBarricade $barricade,
        float $scaleRatio,
        bool $enhanced
    ): void {
        $type = BarricadeType::tryFrom((string) $barricade->barricade_type) ?? BarricadeType::TypeA;
        $cx = (float) $barricade->pos_x;
        $cy = (float) $barricade->pos_y;
        $rot = (int) $barricade->rotation;

        // 長辺(120cm)は常に実寸を維持し、視認強調時は奥行方向のみ見やすく拡張
        $w = ($type->lengthCm() / 100.0) * $scaleRatio;
        $realH = ($type->depthCm() / 100.0) * $scaleRatio;
        $h = $enhanced ? $realH * ($type === BarricadeType::TypeA ? 1.55 : 1.40) : $realH;

        $x = $cx - $w / 2;
        $y = $cy - $h / 2;

        $canvas->save();
        if ($rot !== 0) {
            $canvas->rotate($rot, $cx, $cy);
        }

        if ($type === BarricadeType::TypeA) {
            // A型バリケード: オレンジ外枠＋開脚線＋中央黄黒トラ柄帯
            $orange = [0.918, 0.345, 0.047]; // #ea580c
            $yellow = [0.918, 0.702, 0.031];
            $dark = [0.059, 0.090, 0.165];

            $canvas->filled_rectangle($x, $y, $w, $h, [1.0, 0.969, 0.929]);
            $canvas->rectangle($x, $y, $w, $h, $orange, $enhanced ? 0.85 : 0.45);

            // 左右のA型開脚ライン
            $legInset = $w * 0.10;
            $canvas->line($x + $legInset, $y, $x + $legInset, $y + $h, $orange, 0.45);
            $canvas->line($x + $w - $legInset, $y, $x + $w - $legInset, $y + $h, $orange, 0.45);

            // 中央の黄黒ゼブラ反射板帯
            $bandH = $h * 0.42;
            $bandY = $y + ($h - $bandH) / 2;
            $bandX = $x + $legInset * 0.6;
            $bandW = $w - $legInset * 1.2;
            $canvas->filled_rectangle($bandX, $bandY, $bandW, $bandH, $yellow);
            $stripes = 4;
            $step = $bandW / $stripes;
            for ($i = 0; $i < $stripes; $i++) {
                if ($i % 2 === 1) {
                    $canvas->filled_rectangle($bandX + $i * $step, $bandY, $step * 0.85, $bandH, $dark);
                }
            }
            $canvas->rectangle($bandX, $bandY, $bandW, $bandH, $dark, 0.25);
        } else {
            // 樹脂製単管バリケード: 左右緑色樹脂スタンド脚＋シルバー単管パイプ＋黄色クランプ
            $green = [0.086, 0.639, 0.290]; // #16a34a
            $silver = [0.580, 0.639, 0.722]; // #94a3b8
            $dark = [0.200, 0.255, 0.333];
            $yellow = [0.918, 0.702, 0.031];

            $canvas->filled_rectangle($x, $y, $w, $h, [0.941, 0.992, 0.957]);

            // 左右の緑色樹脂A型スタンド脚
            $legW = max(1.1, $w * 0.16);
            $canvas->filled_rectangle($x + $w * 0.08, $y, $legW, $h, $green);
            $canvas->filled_rectangle($x + $w * 0.92 - $legW, $y, $legW, $h, $green);

            // 中央を貫通する単管パイプ（2段表現）
            $pipeH = max(0.85, $h * 0.24);
            $pipeY = $cy - $pipeH / 2;
            $canvas->filled_rectangle($x, $pipeY, $w, $pipeH, $silver);
            $canvas->rectangle($x, $pipeY, $w, $pipeH, $dark, 0.30);

            // 黄色クランプ部
            $clampW = max(0.7, $legW * 0.55);
            $canvas->filled_rectangle($x + $w * 0.08 + ($legW - $clampW) / 2, $pipeY - 0.3, $clampW, $pipeH + 0.6, $yellow);
            $canvas->filled_rectangle($x + $w * 0.92 - $legW + ($legW - $clampW) / 2, $pipeY - 0.3, $clampW, $pipeH + 0.6, $yellow);
        }

        // ラベル描画
        $label = trim((string) $barricade->label);
        if ($label !== '') {
            $fontSize = 4.2;
            $this->textRenderer->drawMixedText($canvas, $x + 1.0, $y - 5.2, $label, $font, $fontSize, [0.059, 0.090, 0.165]);
        }

        $canvas->restore();
    }

    /**
     * カラーコーンを描画（底面 38.8cm × 38.8cm 正方形）
     */
    public function drawCone(
        CPDF $canvas,
        string $font,
        ConeMapCone $cone,
        float $scaleRatio,
        bool $enhanced
    ): void {
        $color = ConeColor::tryFrom((string) $cone->color) ?? ConeColor::Red;
        $rgb = $color->rgb();
        $cx = (float) $cone->pos_x;
        $cy = (float) $cone->pos_y;

        $realSize = (ConeMapCone::BASE_SIZE_CM / 100.0) * $scaleRatio;
        $size = $enhanced ? max(5.4, $realSize * 2.0) : $realSize;
        $half = $size / 2;

        // 白背景ハロー
        $canvas->filled_rectangle($cx - $half - 0.6, $cy - $half - 0.6, $size + 1.2, $size + 1.2, [1.0, 1.0, 1.0]);
        // 底面正方形
        $canvas->filled_rectangle($cx - $half, $cy - $half, $size, $size, $rgb);
        $canvas->rectangle($cx - $half, $cy - $half, $size, $size, [0.059, 0.090, 0.165], $enhanced ? 0.45 : 0.25);

        // 内接円＋頂点白ドット
        $canvas->circle($cx, $cy, $half * 0.62, [1.0, 1.0, 1.0], 0.45, [], false);
        $canvas->circle($cx, $cy, max(0.55, $half * 0.22), [1.0, 1.0, 1.0], null, [], true);

        $label = trim((string) $cone->label);
        if ($label !== '') {
            $this->textRenderer->drawMixedText($canvas, $cx + $half + 1.2, $cy - 2.2, $label, $font, 4.2, $rgb);
        }
    }

    /**
     * 吹き出し（注意事項）を描画
     */
    public function drawCallout(CPDF $canvas, string $font, ConeMapCallout $callout): void
    {
        $theme = ConeCalloutColor::tryFrom((string) $callout->color_theme) ?? ConeCalloutColor::Amber;
        $borderRgb = $theme->borderRgb();
        $bgRgb = $theme->bgRgb();
        $textRgb = $theme->textRgb();

        $ax = (float) $callout->anchor_x;
        $ay = (float) $callout->anchor_y;
        $bx = (float) $callout->box_x;
        $by = (float) $callout->box_y;
        $bw = max(24.0, (float) $callout->box_width);
        $bh = max(14.0, (float) $callout->box_height);

        // ボックス上の最寄り縁点を算出して引出線を描画
        $edgeX = max($bx, min($ax, $bx + $bw));
        $edgeY = max($by, min($ay, $by + $bh));

        $canvas->line($ax, $ay, $edgeX, $edgeY, [1.0, 1.0, 1.0], 1.8);
        $canvas->line($ax, $ay, $edgeX, $edgeY, $borderRgb, 0.9);
        $canvas->circle($ax, $ay, 1.8, $borderRgb, null, [], true);
        $canvas->circle($ax, $ay, 0.7, [1.0, 1.0, 1.0], null, [], true);

        // 吹き出しボックス本体
        $canvas->filled_rectangle($bx, $by, $bw, $bh, $bgRgb);
        $canvas->rectangle($bx, $by, $bw, $bh, $borderRgb, 0.9);

        // 左アクセントバー
        $canvas->filled_rectangle($bx, $by, 2.2, $bh, $borderRgb);

        $fontSize = $callout->font_size ? (float) $callout->font_size : 5.4;
        $lineHeight = $fontSize * 1.25;
        $curY = $by + 2.5;
        $textX = $bx + 4.5;
        $maxTextW = max(14.0, $bw - 7.5);

        $title = trim((string) ($callout->title ?? ''));
        if ($title !== '') {
            $titleLines = $this->wrapTextToWidth($canvas, $title, $font, $fontSize, $maxTextW);
            foreach ($titleLines as $tLine) {
                if ($curY + $lineHeight > $by + $bh - 1.5) {
                    break;
                }
                $this->textRenderer->drawMixedText($canvas, $textX, $curY, $tLine, $font, $fontSize, $borderRgb);
                $curY += $lineHeight;
            }
        }

        $bodyLines = explode("\n", str_replace("\r\n", "\n", (string) $callout->body));
        foreach ($bodyLines as $rawLine) {
            $wrapped = $this->wrapTextToWidth($canvas, $rawLine, $font, $fontSize * 0.94, $maxTextW);
            foreach ($wrapped as $wLine) {
                if ($curY + $lineHeight > $by + $bh - 1.5) {
                    break 2;
                }
                $this->textRenderer->drawMixedText($canvas, $textX, $curY, $wLine, $font, $fontSize * 0.94, $textRgb);
                $curY += $lineHeight * 0.95;
            }
        }
    }

    /**
     * 左上凡例エリアにカラーコーン・バリケード専用集計凡例ボックスを描画
     */
    public function drawLegendBox(
        CPDF $canvas,
        string $font,
        Timetable $timetable,
        ConeMapSummaryDTO $summary,
        bool $enhanced
    ): void {
        $x = 20.0;
        $y = 58.0;
        $w = 248.0;
        $h = 152.0;

        $dark = [0.059, 0.090, 0.165];
        $muted = [0.278, 0.333, 0.412];
        $indigo = [0.263, 0.220, 0.792];
        $border = [0.580, 0.639, 0.722];

        // 既存白地図の衛生凡例上へ白背景マスク＋枠線を描画
        $canvas->filled_rectangle($x, $y, $w, $h, [1.0, 1.0, 1.0]);
        $canvas->rectangle($x, $y, $w, $h, $border, 0.8);
        $canvas->filled_rectangle($x, $y, $w, 16.0, [0.945, 0.961, 0.976]);
        $canvas->line($x, $y + 16.0, $x + $w, $y + 16.0, $border, 0.6);

        $dateStr = $timetable->target_date ? $timetable->target_date->format('Y/m/d') : '';
        $modeLabel = $enhanced ? '視認強調表示' : '1/400実寸表示';
        $this->textRenderer->drawMixedText(
            $canvas,
            $x + 6.0,
            $y + 4.0,
            "カラーコーン・バリケード配置図 ({$dateStr})",
            $font,
            7.4,
            $dark
        );
        $this->textRenderer->drawMixedText(
            $canvas,
            $x + $w - 58.0,
            $y + 4.5,
            $modeLabel,
            $font,
            6.0,
            $indigo
        );

        // 集計行
        $rows = [
            [
                'symbol' => 'cone',
                'label' => 'カラーコーン (H70cm / 38.8cm角)',
                'count' => "{$summary->conesCount}個 (単独{$summary->standaloneConesCount} / 連結{$summary->linkedConesCount})",
            ],
            [
                'symbol' => 'weight',
                'label' => 'コーンウェイト (コーンと同数セット)',
                'count' => "{$summary->weightsCount}個",
            ],
            [
                'symbol' => 'bar',
                'label' => 'コーンバー (L200cm / φ3.4cm)',
                'count' => "{$summary->barsCount}本",
            ],
            [
                'symbol' => 'type_a',
                'label' => 'A型バリケード (120×46×H80cm)',
                'count' => "{$summary->barricadesTypeACount}台",
            ],
            [
                'symbol' => 'resin_pipe',
                'label' => '樹脂製単管バリケード (120×64×H82cm)',
                'count' => "{$summary->barricadesResinPipeCount}台",
            ],
            [
                'symbol' => 'callout',
                'label' => '吹き出し注意事項',
                'count' => "{$summary->calloutsCount}件",
            ],
        ];

        $rowY = $y + 23.0;
        $rowStep = 17.5;
        foreach ($rows as $row) {
            $symX = $x + 8.0;
            $symY = $rowY + 1.5;

            if ($row['symbol'] === 'cone') {
                $canvas->filled_rectangle($symX, $symY, 6.5, 6.5, [0.863, 0.149, 0.149]);
                $canvas->circle($symX + 3.25, $symY + 3.25, 1.8, [1.0, 1.0, 1.0], 0.4, [], false);
            } elseif ($row['symbol'] === 'weight') {
                $canvas->filled_rectangle($symX, $symY + 1.0, 6.5, 4.5, [0.200, 0.255, 0.333]);
            } elseif ($row['symbol'] === 'bar') {
                $canvas->filled_rectangle($symX - 1.0, $symY + 2.2, 4.2, 2.0, [0.918, 0.702, 0.031]);
                $canvas->filled_rectangle($symX + 3.2, $symY + 2.2, 4.2, 2.0, [0.059, 0.090, 0.165]);
            } elseif ($row['symbol'] === 'type_a') {
                $canvas->filled_rectangle($symX - 1.0, $symY + 0.5, 8.5, 5.0, [1.0, 0.969, 0.929]);
                $canvas->rectangle($symX - 1.0, $symY + 0.5, 8.5, 5.0, [0.918, 0.345, 0.047], 0.7);
                $canvas->filled_rectangle($symX + 0.2, $symY + 2.0, 6.0, 2.0, [0.918, 0.702, 0.031]);
            } elseif ($row['symbol'] === 'resin_pipe') {
                $canvas->filled_rectangle($symX - 0.5, $symY + 0.5, 1.8, 5.0, [0.086, 0.639, 0.290]);
                $canvas->filled_rectangle($symX + 5.2, $symY + 0.5, 1.8, 5.0, [0.086, 0.639, 0.290]);
                $canvas->filled_rectangle($symX - 1.2, $symY + 2.1, 8.8, 1.8, [0.580, 0.639, 0.722]);
            } elseif ($row['symbol'] === 'callout') {
                $canvas->filled_rectangle($symX - 0.5, $symY + 0.5, 7.5, 5.0, [1.0, 0.984, 0.922]);
                $canvas->rectangle($symX - 0.5, $symY + 0.5, 7.5, 5.0, [0.851, 0.467, 0.024], 0.7);
            }

            $this->textRenderer->drawMixedText($canvas, $x + 20.0, $rowY, $row['label'], $font, 5.8, $muted);
            $this->textRenderer->drawMixedText($canvas, $x + 158.0, $rowY, $row['count'], $font, 6.2, $dark);
            $rowY += $rowStep;
        }

        // フッター注記（色別内訳）
        $colorSummaryParts = [];
        foreach (ConeColor::cases() as $colorCase) {
            $cnt = $summary->colorBreakdown[$colorCase->value] ?? 0;
            if ($cnt > 0) {
                $colorSummaryParts[] = "{$colorCase->label()}:{$cnt}個";
            }
        }
        $colorFooter = $colorSummaryParts !== []
            ? '内訳: '.implode(' / ', $colorSummaryParts)
            : '※ 連結時のバー本数はカラーコーン個数−1本';
        $this->textRenderer->drawMixedText($canvas, $x + 6.0, $y + $h - 14.0, $colorFooter, $font, 5.4, $muted);
    }

    /**
     * 指定幅内に収まるようテキストを行折り返しする
     *
     * @return array<int, string>
     */
    protected function wrapTextToWidth(
        CPDF $canvas,
        string $text,
        string $font,
        float $fontSize,
        float $maxWidth
    ): array {
        if ($text === '') {
            return [''];
        }

        $lines = [];
        $current = '';
        $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        foreach ($chars as $ch) {
            $candidate = $current.$ch;
            $width = $this->measureMixedTextWidth($canvas, $candidate, $font, $fontSize);
            if ($current !== '' && $width > $maxWidth) {
                $lines[] = $current;
                $current = $ch;
            } else {
                $current = $candidate;
            }
        }

        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    protected function measureMixedTextWidth(CPDF $canvas, string $text, string $japaneseFont, float $size): float
    {
        $total = 0.0;
        foreach ($this->textRenderer->splitMixedText($text) as $chunk) {
            $font = $chunk['is_ascii'] ? 'Helvetica' : $japaneseFont;
            $total += $canvas->get_text_width($chunk['text'], $font, $size);
        }

        return $total;
    }
}
