<?php

namespace App\Http\Controllers;

use App\Http\Requests\BulkUpdateSiteMapPlacementsRequest;
use App\Http\Requests\StoreSiteMapPlacementRequest;
use App\Http\Requests\UpdateSiteMapPlacementRequest;
use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Services\SiteMapService;
use Illuminate\Http\JsonResponse;

class SiteMapPlacementController extends Controller
{
    public function __construct(
        protected SiteMapService $siteMapService,
    ) {}

    /**
     * 配置一覧を取得する API
     */
    public function index(Timetable $timetable): JsonResponse
    {
        $this->authorize('viewAny', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $placements = $this->siteMapService->getPlacements($timetable, $siteBaseMap);

        return response()->json([
            'success' => true,
            'placements' => $placements,
        ]);
    }

    /**
     * 新規配置を保存する (ドラッグ＆ドロップ配置・複製 API)
     */
    public function store(StoreSiteMapPlacementRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('create', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        $placement = $this->siteMapService->createPlacement(
            $timetable,
            $siteBaseMap,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'placement' => $placement,
        ], 201);
    }

    /**
     * 配置の位置・寸法・回転・属性を更新する API
     */
    public function update(UpdateSiteMapPlacementRequest $request, Timetable $timetable, SiteMapPlacement $placement): JsonResponse
    {
        $this->authorize('update', SiteBaseMap::class);

        $updated = $this->siteMapService->updatePlacementGeometry($placement, $request->validated());

        return response()->json([
            'success' => true,
            'placement' => $updated,
        ]);
    }

    /**
     * 複数の配置を一括更新する API (一括整列・複数移動・一括カラー変更)
     */
    public function bulkUpdate(BulkUpdateSiteMapPlacementsRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('update', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $placements = $this->siteMapService->bulkUpdatePlacements(
            $timetable,
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
    public function destroy(Timetable $timetable, SiteMapPlacement $placement): JsonResponse
    {
        $this->authorize('delete', SiteBaseMap::class);

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
        $this->authorize('update', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $placements = $this->siteMapService->resetToStandardPreset($timetable, $siteBaseMap);

        return response()->json([
            'success' => true,
            'placements' => $placements,
        ]);
    }

    /**
     * 全配置オブジェクトを一括削除（クリア）する API
     */
    public function clear(Timetable $timetable): JsonResponse
    {
        $this->authorize('delete', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $this->siteMapService->clearPlacements($timetable, $siteBaseMap);

        return response()->json([
            'success' => true,
            'placements' => [],
        ]);
    }
}
