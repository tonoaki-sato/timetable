<?php

namespace App\Services;

use App\DTOs\ConeMap\ConeMapSummaryDTO;
use App\Models\ConeMapBar;
use App\Models\ConeMapBarricade;
use App\Models\ConeMapCallout;
use App\Models\ConeMapCone;
use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Services\SiteMapPdf\BaseMapVectorRenderer;
use App\Services\SiteMapPdf\ConeMapSymbolRenderer;
use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;
use Illuminate\Database\Eloquent\Collection;

class ConeMapPdfExporterService
{
    public function __construct(
        protected SiteMapPdfExporterService $siteMapPdfExporter,
        protected BaseMapVectorRenderer $baseMapRenderer,
        protected ConeMapSymbolRenderer $symbolRenderer,
    ) {}

    /**
     * カラーコーン・バリケード配置の A3 1/400 ベクターPDFバイナリを生成する
     *
     * @param  Collection<int, ConeMapCone>  $cones
     * @param  Collection<int, ConeMapBar>  $bars
     * @param  Collection<int, ConeMapBarricade>  $barricades
     * @param  Collection<int, ConeMapCallout>  $callouts
     * @param  Collection<int, SiteMapPlacement>  $boothPlacements
     */
    public function exportA3VectorPdf(
        Timetable $timetable,
        SiteBaseMap $siteBaseMap,
        Collection $cones,
        Collection $bars,
        Collection $barricades,
        Collection $callouts,
        Collection $boothPlacements,
        ConeMapSummaryDTO $summary,
        bool $enhanced = true,
        bool $showBooths = false,
    ): string {
        $fontPath = $this->siteMapPdfExporter->getJapaneseFontPath();
        $fontDir = storage_path('fonts');
        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0777, true);
        }

        $dompdf = new Dompdf;
        $options = $dompdf->getOptions();
        $options->setFontDir($fontDir);
        $options->setFontCache($fontDir);
        $options->setChroot(base_path());
        $options->setIsRemoteEnabled(true);

        $existingFont = $dompdf->getFontMetrics()->getFont('japanesefont', 'normal');
        if (! $existingFont) {
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>'
                .'@font-face { font-family: "japanesefont"; src: url("file://'.$fontPath.'") format("truetype"); }'
                .'@page { margin: 0; } body { font-family: "japanesefont"; margin: 0; padding: 0; }'
                .'</style></head><body>&nbsp;</body></html>';
        } else {
            $html = '<!DOCTYPE html><html><head><meta charset="utf-8"><style>@page { margin: 0; }</style></head><body style="margin:0;padding:0;"></body></html>';
        }

        $isLegacyPreset = $siteBaseMap->svg_path === 'maps/shopping_street_base_map_260813.svg'
            && file_exists(storage_path('app/site_map_base_20260927.json'));
        $paperDims = $isLegacyPreset
            ? ['width' => 842.0, 'height' => 1191.0]
            : $siteBaseMap->getViewboxDimensions();

        $dompdf->loadHtml($html);
        $dompdf->setPaper([0, 0, $paperDims['width'], $paperDims['height']], 'portrait');
        $dompdf->render();

        if (! $existingFont) {
            foreach (glob($fontDir.'/*') ?: [] as $file) {
                if (is_file($file)) {
                    @chmod($file, 0666);
                }
            }
        }

        /** @var CPDF $canvas */
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('japanesefont', 'normal');
        if (! $font) {
            $font = $fontPath;
        }

        $scaleRatio = max(0.1, (float) $siteBaseMap->scale_ratio);

        // 1. 白地図ベースレイヤー
        if ($isLegacyPreset) {
            $this->baseMapRenderer->drawPresetJsonBaseMap($canvas, $font, $boothPlacements, false);
        } else {
            $this->baseMapRenderer->drawSvgFallbackBaseMap($canvas, $siteBaseMap);
        }

        // 2. ブース配置参考レイヤー (オプションON時)
        if ($showBooths && $boothPlacements->isNotEmpty()) {
            $this->symbolRenderer->drawBoothReferenceLayer($canvas, $boothPlacements);
        }

        // 3. コーンバー描画 (両端コーン間)
        $conesById = $cones->keyBy('id');
        foreach ($bars as $bar) {
            $startCone = $conesById->get($bar->start_cone_id);
            $endCone = $conesById->get($bar->end_cone_id);
            if ($startCone && $endCone) {
                $this->symbolRenderer->drawConeBar($canvas, $bar, $startCone, $endCone, $scaleRatio, $enhanced);
            }
        }

        // 4. バリケード（A型 / 樹脂製単管）描画
        foreach ($barricades as $barricade) {
            $this->symbolRenderer->drawBarricade($canvas, $font, $barricade, $scaleRatio, $enhanced);
        }

        // 5. カラーコーン描画
        foreach ($cones as $cone) {
            $this->symbolRenderer->drawCone($canvas, $font, $cone, $scaleRatio, $enhanced);
        }

        // 6. 吹き出し（注意事項）描画
        foreach ($callouts as $callout) {
            $this->symbolRenderer->drawCallout($canvas, $font, $callout);
        }

        // 7. 集計凡例ボックス描画
        $this->symbolRenderer->drawLegendBox($canvas, $font, $timetable, $summary, $enhanced);

        return (string) $dompdf->output();
    }
}
