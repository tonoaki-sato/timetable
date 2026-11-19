<?php

namespace App\Actions\SiteMap;

use App\Models\Timetable;
use App\Services\SiteMapPdfExporterService;
use App\Services\SiteMapService;
use Illuminate\Http\Response;

class DownloadSiteMapPdfAction
{
    public function __construct(
        protected SiteMapService $siteMapService,
        protected SiteMapPdfExporterService $pdfExporterService,
    ) {}

    /**
     * 編集済み白地図の A3 1/400 ベクターPDF ダウンロードレスポンスを生成
     */
    public function execute(Timetable $timetable): Response
    {
        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $placements = $this->siteMapService->getPlacements($siteBaseMap);

        $pdfBinary = $this->pdfExporterService->exportA3VectorPdf($siteBaseMap, $placements);

        $dateStr = $timetable->target_date ? $timetable->target_date->format('Ymd') : date('Ymd');
        $filename = "ござ市配置図_{$dateStr}_A3_1-400.pdf";

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
