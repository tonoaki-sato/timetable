<?php

namespace App\Http\Controllers;

use App\Http\Requests\DestroySiteMapIconRequest;
use App\Http\Requests\StoreSiteMapIconRequest;
use App\Http\Requests\UpdateSiteMapIconRequest;
use App\Models\SiteBaseMap;
use App\Models\SiteMapIconDefinition;
use App\Models\Timetable;
use App\Services\SiteMapIconService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class SiteMapIconController extends Controller
{
    public function __construct(
        protected SiteMapIconService $iconService,
    ) {}

    /**
     * PNG画像からカスタムアイコン定義を新規作成する API
     */
    public function store(StoreSiteMapIconRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('create', SiteBaseMap::class);

        /** @var UploadedFile $file */
        $file = $request->file('png_file');
        $icon = $this->iconService->storeIconFromPng(
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
    public function update(UpdateSiteMapIconRequest $request, Timetable $timetable, SiteMapIconDefinition $iconDefinition): JsonResponse
    {
        $this->authorize('update', SiteBaseMap::class);

        try {
            $file = $request->file('png_file');
            $updated = $this->iconService->updateIcon(
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
    public function destroy(DestroySiteMapIconRequest $request, Timetable $timetable, SiteMapIconDefinition $iconDefinition): JsonResponse
    {
        $this->authorize('delete', SiteBaseMap::class);

        try {
            $force = $request->boolean('force', false);
            $result = $this->iconService->deleteIcon($iconDefinition, $force);

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
    public function usage(Timetable $timetable, SiteMapIconDefinition $iconDefinition): JsonResponse
    {
        $this->authorize('viewAny', SiteBaseMap::class);

        $count = $this->iconService->getIconUsageCount($iconDefinition->icon_key);

        return response()->json([
            'success' => true,
            'usage_count' => $count,
        ]);
    }
}
