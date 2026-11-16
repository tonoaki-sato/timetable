<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteMapPlacementRequest;
use App\Http\Requests\UpdateSiteMapPlacementRequest;
use App\Models\SiteMapIconDefinition;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Services\SiteMapService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SiteMapController extends Controller
{
    public function __construct(
        protected SiteMapService $siteMapService
    ) {}

    /**
     * 白地図マッピングエディタ画面を表示する
     */
    public function editor(Request $request, Timetable $timetable): View
    {
        // 運行情報に紐づくアクティブな白地図を取得、存在しない場合はデフォルト白地図を準備
        $siteBaseMap = $timetable->siteBaseMaps()
            ->where('is_active', true)
            ->first();

        if (! $siteBaseMap) {
            $siteBaseMap = $timetable->siteBaseMaps()->create([
                'name' => '商店街平面図 260813版 A3 1/400',
                'cad_version' => '260813',
                'paper_size' => 'A3',
                'scale_ratio' => 7.0866,
                'width_meters' => 32.4,
                'height_meters' => 45.8,
                'svg_path' => 'maps/shopping_street_base_map_260813.svg',
                'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
                'viewbox' => '0 0 842 1191',
                'is_active' => true,
            ]);
        }

        // アイコン定義マスター一覧
        $iconDefinitions = SiteMapIconDefinition::all();

        // 既存の配置データ
        $placements = $this->siteMapService->getPlacements($siteBaseMap);

        // 運行情報に紐づく既存のブース台帳データ一覧
        $layoutItems = $timetable->layoutItems()
            ->with(['vendor', 'area'])
            ->orderBy('code', 'asc')
            ->get();

        return view('site_layouts.editor', [
            'timetable' => $timetable,
            'siteBaseMap' => $siteBaseMap,
            'iconDefinitions' => $iconDefinitions,
            'placements' => $placements,
            'layoutItems' => $layoutItems,
        ]);
    }

    /**
     * 新規配置を保存する (ドラッグ＆ドロップ配置 API)
     */
    public function storePlacement(StoreSiteMapPlacementRequest $request, Timetable $timetable): JsonResponse
    {
        $siteBaseMap = $timetable->siteBaseMaps()->where('is_active', true)->firstOrFail();

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
        $siteBaseMap = $timetable->siteBaseMaps()->where('is_active', true)->firstOrFail();
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
        $validated = $request->validated();

        $updated = $this->siteMapService->updatePlacementGeometry($placement, $validated);

        return response()->json([
            'success' => true,
            'placement' => $updated,
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
}
