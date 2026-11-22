<?php

namespace App\Services;

use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use App\Repositories\Contracts\SiteMapRepositoryInterface;
use App\Services\SiteMapPdf\BaseMapVectorRenderer;
use App\Services\SiteMapPdf\PlacementSymbolRenderer;
use Dompdf\Adapter\CPDF;
use Dompdf\Dompdf;
use Illuminate\Database\Eloquent\Collection;

class SiteMapPdfExporterService
{
    public function __construct(
        protected SiteMapRepositoryInterface $siteMapRepository,
        protected BaseMapVectorRenderer $baseMapRenderer,
        protected PlacementSymbolRenderer $symbolRenderer,
    ) {}

    /**
     * 日本語フォントの実体ファイルパスを探索して取得
     */
    public function getJapaneseFontPath(): string
    {
        $candidates = [
            resource_path('fonts/DroidSansFallbackFull.ttf'),
            storage_path('fonts/DroidSansFallbackFull.ttf'),
            '/usr/share/fonts/google-droid-sans-fonts/DroidSansFallbackFull.ttf',
            '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc',
            '/usr/share/fonts/truetype/fonts-japanese-gothic.ttf',
        ];

        foreach ($candidates as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return resource_path('fonts/DroidSansFallbackFull.ttf');
    }

    /**
     * 白地図と全配置オブジェクトから A3 1/400 ベクターPDFバイナリを生成する
     *
     * @param  Collection<int, SiteMapPlacement>  $placements
     */
    public function exportA3VectorPdf(SiteBaseMap $siteBaseMap, Collection $placements): string
    {
        $fontPath = $this->getJapaneseFontPath();
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

        // 既に japanesefont が登録されている場合は不要な再登録（w+ でのUFM再生成）をスキップ
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

        // 初回生成時のWebサーバー・CLI間の所有権不一致（Permission denied）を防止
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

        $iconDefs = $this->siteMapRepository->getAllIconDefinitions()->keyBy('icon_key');

        // 1. 白地図ベースレイヤーの描画
        if ($isLegacyPreset) {
            $this->baseMapRenderer->drawLegacyPresetBaseMap($canvas, $font, $placements);
        } else {
            $this->baseMapRenderer->drawSvgFallbackBaseMap($canvas, $siteBaseMap);
        }

        // 2. 給水20m衛生サークルレイヤーの描画 (最背面配置レイヤー)
        foreach ($placements as $placement) {
            $iconDef = $iconDefs->get($placement->shape_type);
            $hasHygiene = $placement->shape_type === 'water_supply' || ($iconDef && $iconDef->has_hygiene_circle);
            if ($hasHygiene && $placement->show_hygiene_circle) {
                $this->symbolRenderer->drawWaterHygieneCircle($canvas, $placement);
            }
        }

        // 3. 配置オブジェクトレイヤーの描画 (z_index 順)
        $sorted = $placements->sortBy(fn (SiteMapPlacement $p) => [$p->z_index, $p->id]);
        foreach ($sorted as $placement) {
            $this->symbolRenderer->drawPlacementNode($canvas, $font, $placement, $iconDefs);
        }

        return (string) $dompdf->output();
    }
}
