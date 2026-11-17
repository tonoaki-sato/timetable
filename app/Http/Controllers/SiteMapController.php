<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkUpdateSiteMapPlacementsRequest;
use App\Http\Requests\StoreSiteMapPlacementRequest;
use App\Http\Requests\UpdateSiteMapPlacementRequest;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Services\SiteMapPdfExporterService;
use App\Services\SiteMapService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SiteMapController extends Controller
{
    public function __construct(
        protected SiteMapService $siteMapService,
        protected SiteMapPdfExporterService $pdfExporterService
    ) {}

    /**
     * 白地図マッピングエディタ画面を表示する
     */
    public function editor(Request $request, Timetable $timetable): View
    {
        $editorData = $this->siteMapService->getEditorData($timetable);

        return view('site_layouts.editor', array_merge($editorData, [
            'timetable' => $timetable,
        ]));
    }

    /**
     * 新規配置を保存する (ドラッグ＆ドロップ配置・複製 API)
     */
    public function storePlacement(StoreSiteMapPlacementRequest $request, Timetable $timetable): JsonResponse
    {
        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        $placement = $this->siteMapService->createPlacement(
            $siteBaseMap,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'placement' => $placement,
        ], 201);
    }

    /**
     * 配置一覧を取得する API
     */
    public function getPlacements(Timetable $timetable): JsonResponse
    {
        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $placements = $this->siteMapService->getPlacements($siteBaseMap);

        return response()->json([
            'success' => true,
            'placements' => $placements,
        ]);
    }

    /**
     * 配置の位置・寸法・回転・属性を更新する API
     */
    public function updatePlacement(UpdateSiteMapPlacementRequest $request, Timetable $timetable, SiteMapPlacement $placement): JsonResponse
    {
        $updated = $this->siteMapService->updatePlacementGeometry($placement, $request->validated());

        return response()->json([
            'success' => true,
            'placement' => $updated,
        ]);
    }

    /**
     * 複数の配置を一括更新する API (一括整列・複数移動・一括カラー変更)
     */
    public function bulkUpdatePlacements(BulkUpdateSiteMapPlacementsRequest $request, Timetable $timetable): JsonResponse
    {
        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $placements = $this->siteMapService->bulkUpdatePlacements(
            $siteBaseMap,
            $request->validated('items')
        );

        return response()->json([
            'success' => true,
            'placements' => $placements,
        ]);
    }

    /**
     * 配置を削除する API
     */
    public function destroyPlacement(Timetable $timetable, SiteMapPlacement $placement): JsonResponse
    {
        $this->siteMapService->deletePlacement($placement);

        return response()->json([
            'success' => true,
        ]);
    }

    /**
     * 標準配置プリセット（添付PDF状態）へリセットする API
     */
    public function resetPreset(Timetable $timetable): JsonResponse
    {
        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $placements = $this->siteMapService->resetToStandardPreset($siteBaseMap);

        return response()->json([
            'success' => true,
            'placements' => $placements,
        ]);
    }

    /**
     * 編集済みの白地図配置を A3 1/400 ベクターPDFとしてダウンロード出力する
     */
    public function exportPdf(Timetable $timetable): Response
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
