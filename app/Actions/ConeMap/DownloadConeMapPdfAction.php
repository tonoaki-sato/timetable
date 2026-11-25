<?php

namespace App\Actions\ConeMap;

use App\Models\Timetable;
use App\Services\ConeMapPdfExporterService;
use App\Services\ConeMapService;
use Illuminate\Http\Response;

class DownloadConeMapPdfAction
{
    public function __construct(
        protected ConeMapService $coneMapService,
        protected ConeMapPdfExporterService $pdfExporterService,
    ) {}

    /**
     * カラーコーン・バリケード配置図の A3 1/400 ベクターPDF ダウンロードレスポンスを生成
     */
    public function execute(Timetable $timetable, bool $enhanced = true, bool $showBooths = false): Response
    {
        $editorData = $this->coneMapService->getEditorData($timetable);

        $pdfBinary = $this->pdfExporterService->exportA3VectorPdf(
            timetable: $timetable,
            siteBaseMap: $editorData->siteBaseMap,
            cones: $editorData->cones,
            bars: $editorData->bars,
            barricades: $editorData->barricades,
            callouts: $editorData->callouts,
            boothPlacements: $editorData->boothPlacements,
            summary: $editorData->summary,
            enhanced: $enhanced,
            showBooths: $showBooths,
        );

        $dateStr = $timetable->target_date ? $timetable->target_date->format('Ymd') : date('Ymd');
        $filename = "カラーコーン配置図_{$dateStr}_A3_1-400.pdf";

        return response($pdfBinary, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
