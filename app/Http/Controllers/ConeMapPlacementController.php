<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConeMap\BulkUpdateConeMapBarricadesRequest;
use App\Http\Requests\ConeMap\BulkUpdateConeMapConesRequest;
use App\Http\Requests\ConeMap\StoreConeMapBarRequest;
use App\Http\Requests\ConeMap\StoreConeMapBarricadeRequest;
use App\Http\Requests\ConeMap\StoreConeMapCalloutRequest;
use App\Http\Requests\ConeMap\StoreConeMapConeLineRequest;
use App\Http\Requests\ConeMap\StoreConeMapConeRequest;
use App\Http\Requests\ConeMap\UpdateConeMapBarRequest;
use App\Http\Requests\ConeMap\UpdateConeMapBarricadeRequest;
use App\Http\Requests\ConeMap\UpdateConeMapCalloutRequest;
use App\Http\Requests\ConeMap\UpdateConeMapConeRequest;
use App\Models\ConeMapBar;
use App\Models\ConeMapBarricade;
use App\Models\ConeMapCallout;
use App\Models\ConeMapCone;
use App\Models\SiteBaseMap;
use App\Models\Timetable;
use App\Services\ConeMapService;
use App\Services\SiteMapService;
use Illuminate\Http\JsonResponse;

class ConeMapPlacementController extends Controller
{
    public function __construct(
        protected ConeMapService $coneMapService,
        protected SiteMapService $siteMapService,
    ) {}

    /**
     * カラーコーン・バー・バリケード・吹き出しの一覧と最新サマリーを取得する API
     */
    public function index(Timetable $timetable): JsonResponse
    {
        $this->authorize('viewAny', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $data = $this->coneMapService->getMapData($timetable, $siteBaseMap);

        return response()->json(array_merge(['success' => true], $data));
    }

    /**
     * 単独カラーコーン（＋ウェイト）を追加する
     */
    public function storeCone(StoreConeMapConeRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('create', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $cone = $this->coneMapService->createCone($timetable, $siteBaseMap, $request->validated());

        return response()->json([
            'success' => true,
            'cone' => $cone,
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ], 201);
    }

    /**
     * カラーコーンの位置・色・属性を更新する
     */
    public function updateCone(UpdateConeMapConeRequest $request, Timetable $timetable, ConeMapCone $coneMapCone): JsonResponse
    {
        $this->authorize('update', SiteBaseMap::class);

        $updated = $this->coneMapService->updateCone($coneMapCone, $request->validated());
        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        return response()->json([
            'success' => true,
            'cone' => $updated,
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ]);
    }

    /**
     * 複数のカラーコーンを一括更新する
     */
    public function bulkUpdateCones(BulkUpdateConeMapConesRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('update', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $cones = $this->coneMapService->bulkUpdateCones($timetable, $siteBaseMap, $request->validated('items'));

        return response()->json([
            'success' => true,
            'cones' => $cones,
            'callouts' => $this->coneMapService->getMapData($timetable, $siteBaseMap)['callouts'],
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ]);
    }

    /**
     * カラーコーンを削除する（接続バーも連動削除）
     */
    public function destroyCone(Timetable $timetable, ConeMapCone $coneMapCone): JsonResponse
    {
        $this->authorize('delete', SiteBaseMap::class);

        $result = $this->coneMapService->deleteCone($coneMapCone);
        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        return response()->json([
            'success' => true,
            'deleted_bar_ids' => $result['deleted_bar_ids'],
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ]);
    }

    /**
     * 既存の2個のカラーコーン間をコーンバーで連結する
     */
    public function storeBar(StoreConeMapBarRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('create', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        try {
            $bar = $this->coneMapService->createBar($timetable, $siteBaseMap, $request->validated());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'bar' => $bar,
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ], 201);
    }

    /**
     * コーンバーの種別・メモを更新する
     */
    public function updateBar(UpdateConeMapBarRequest $request, Timetable $timetable, ConeMapBar $coneMapBar): JsonResponse
    {
        $this->authorize('update', SiteBaseMap::class);

        $updated = $this->coneMapService->updateBar($coneMapBar, $request->validated());
        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        return response()->json([
            'success' => true,
            'bar' => $updated,
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ]);
    }

    /**
     * コーンバーを削除する
     */
    public function destroyBar(Timetable $timetable, ConeMapBar $coneMapBar): JsonResponse
    {
        $this->authorize('delete', SiteBaseMap::class);

        $this->coneMapService->deleteBar($coneMapBar);
        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        return response()->json([
            'success' => true,
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ]);
    }

    /**
     * 始点〜終点間に複数カラーコーン＋連結コーンバーを一括作成する
     */
    public function storeConeLine(StoreConeMapConeLineRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('create', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        try {
            $created = $this->coneMapService->createConeLine($timetable, $siteBaseMap, $request->validated());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'cones' => $created['cones'],
            'bars' => $created['bars'],
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ], 201);
    }

    /**
     * バリケード（A型 / 樹脂製単管）を追加する
     */
    public function storeBarricade(StoreConeMapBarricadeRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('create', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $barricade = $this->coneMapService->createBarricade($timetable, $siteBaseMap, $request->validated());

        return response()->json([
            'success' => true,
            'barricade' => $barricade,
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ], 201);
    }

    /**
     * バリケードの位置・角度・種別・属性を更新する
     */
    public function updateBarricade(
        UpdateConeMapBarricadeRequest $request,
        Timetable $timetable,
        ConeMapBarricade $coneMapBarricade
    ): JsonResponse {
        $this->authorize('update', SiteBaseMap::class);

        $updated = $this->coneMapService->updateBarricade($coneMapBarricade, $request->validated());
        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        return response()->json([
            'success' => true,
            'barricade' => $updated,
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ]);
    }

    /**
     * 複数のバリケードを一括更新する
     */
    public function bulkUpdateBarricades(BulkUpdateConeMapBarricadesRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('update', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $barricades = $this->coneMapService->bulkUpdateBarricades($timetable, $siteBaseMap, $request->validated('items'));

        return response()->json([
            'success' => true,
            'barricades' => $barricades,
            'callouts' => $this->coneMapService->getMapData($timetable, $siteBaseMap)['callouts'],
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ]);
    }

    /**
     * バリケードを削除する
     */
    public function destroyBarricade(Timetable $timetable, ConeMapBarricade $coneMapBarricade): JsonResponse
    {
        $this->authorize('delete', SiteBaseMap::class);

        $this->coneMapService->deleteBarricade($coneMapBarricade);
        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        return response()->json([
            'success' => true,
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ]);
    }

    /**
     * 吹き出し（注意事項）を追加する
     */
    public function storeCallout(StoreConeMapCalloutRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('create', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        try {
            $callout = $this->coneMapService->createCallout($timetable, $siteBaseMap, $request->validated());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'callout' => $callout,
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ], 201);
    }

    /**
     * 吹き出し（注意事項）を更新する
     */
    public function updateCallout(
        UpdateConeMapCalloutRequest $request,
        Timetable $timetable,
        ConeMapCallout $coneMapCallout
    ): JsonResponse {
        $this->authorize('update', SiteBaseMap::class);

        try {
            $updated = $this->coneMapService->updateCallout($coneMapCallout, $request->validated());
        } catch (\DomainException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        return response()->json([
            'success' => true,
            'callout' => $updated,
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ]);
    }

    /**
     * 吹き出しを削除する
     */
    public function destroyCallout(Timetable $timetable, ConeMapCallout $coneMapCallout): JsonResponse
    {
        $this->authorize('delete', SiteBaseMap::class);

        $this->coneMapService->deleteCallout($coneMapCallout);
        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);

        return response()->json([
            'success' => true,
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ]);
    }

    /**
     * 白地図上の全カラーコーン・バー・バリケード・吹き出しを一括クリアする
     */
    public function clear(Timetable $timetable): JsonResponse
    {
        $this->authorize('delete', SiteBaseMap::class);

        $siteBaseMap = $this->siteMapService->getActiveBaseMap($timetable);
        $this->coneMapService->clearAll($timetable, $siteBaseMap);

        return response()->json([
            'success' => true,
            'cones' => [],
            'bars' => [],
            'barricades' => [],
            'callouts' => [],
            'summary' => $this->coneMapService->getSummaryFor($timetable, $siteBaseMap)->toArray(),
        ]);
    }
}
