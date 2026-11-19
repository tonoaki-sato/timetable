<?php

namespace App\Http\Controllers;

use App\Actions\SiteMap\DownloadSiteMapPdfAction;
use App\Http\Requests\BulkUpdateSiteMapPlacementsRequest;
use App\Http\Requests\DestroySiteMapIconRequest;
use App\Http\Requests\StoreSiteMapIconRequest;
use App\Http\Requests\StoreSiteMapPlacementRequest;
use App\Http\Requests\UpdateSiteMapIconRequest;
use App\Http\Requests\UpdateSiteMapPlacementRequest;
use App\Models\SiteBaseMap;
use App\Models\SiteMapIconDefinition;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Services\SiteMapService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;

class SiteMapController extends Controller
{
    public function __construct(
        protected SiteMapService $siteMapService,
        protected DownloadSiteMapPdfAction $downloadPdfAction,
    ) {}

    /**
     * 白地図マッピングエディタ画面を表示する
     */
    public function editor(Request $request, Timetable $timetable): View
    {
        $this->authorize('viewAny', SiteBaseMap::class);

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
        $this->authorize('create', SiteBaseMap::class);

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
        $this->authorize('viewAny', SiteBaseMap::class);

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
    public function bulkUpdatePlacements(BulkUpdateSiteMapPlacementsRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('update', SiteBaseMap::class);

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
        $placements = $this->siteMapService->resetToStandardPreset($siteBaseMap);

        return response()->json([
            'success' => true,
            'placements' => $placements,
        ]);
    }

    /**
     * 全配置オブジェクトを一括削除（クリア）する API
     */
    public function clearPlacements(Timetable $timetable): JsonResponse
    {
        $this->authorize('delete', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $this->siteMapService->clearPlacements($siteBaseMap);

        return response()->json([
            'success' => true,
            'placements' => [],
        ]);
    }

    /**
     * 編集済みの白地図配置を A3 1/400 ベクターPDFとしてダウンロード出力する
     */
    public function exportPdf(Timetable $timetable): Response
    {
        $this->authorize('viewAny', SiteBaseMap::class);

        return $this->downloadPdfAction->execute($timetable);
    }

    /**
     * PNG画像からカスタムアイコン定義を新規作成する API
     */
    public function storeIcon(StoreSiteMapIconRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('create', SiteBaseMap::class);

        /** @var UploadedFile $file */
        $file = $request->file('png_file');
        $icon = $this->siteMapService->storeIconFromPng(
            $timetable,
            $request->validated(),
            $file
        );

        return response()->json([
            'success' => true,
            'icon' => $icon,
        ], 201);
    }

    /**
     * カスタムアイコン定義を更新する API (画像差し替え対応)
     */
    public function updateIcon(UpdateSiteMapIconRequest $request, Timetable $timetable, SiteMapIconDefinition $iconDefinition): JsonResponse
    {
        $this->authorize('update', SiteBaseMap::class);

        try {
            $file = $request->file('png_file');
            $updated = $this->siteMapService->updateIcon(
                $timetable,
                $iconDefinition,
                $request->validated(),
                $file
            );

            return response()->json([
                'success' => true,
                'icon' => $updated,
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * カスタムアイコン定義を削除する API
     */
    public function destroyIcon(DestroySiteMapIconRequest $request, Timetable $timetable, SiteMapIconDefinition $iconDefinition): JsonResponse
    {
        $this->authorize('delete', SiteBaseMap::class);

        try {
            $force = $request->boolean('force', false);
            $result = $this->siteMapService->deleteIcon($iconDefinition, $force);

            return response()->json([
                'success' => true,
                'placements_deleted' => $result['placements_deleted'],
            ]);
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * アイコンの白地図上での配置使用状況を取得する API
     */
    public function getIconUsage(Timetable $timetable, SiteMapIconDefinition $iconDefinition): JsonResponse
    {
        $this->authorize('viewAny', SiteBaseMap::class);

        $count = $this->siteMapService->getIconUsageCount($iconDefinition->icon_key);

        return response()->json([
            'success' => true,
            'usage_count' => $count,
        ]);
    }
}
