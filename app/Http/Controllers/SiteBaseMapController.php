<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSiteBaseMapRequest;
use App\Http\Requests\UpdateSiteBaseMapRequest;
use App\Models\SiteBaseMap;
use App\Services\SiteBaseMapService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SiteBaseMapController extends Controller
{
    public function __construct(
        protected SiteBaseMapService $siteBaseMapService,
    ) {}

    /**
     * 白地図マスター一覧画面の表示
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', SiteBaseMap::class);

        $viewData = $this->siteBaseMapService->getIndexViewData();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'base_maps' => $viewData['baseMaps'],
            ]);
        }

        return view('site_base_maps.index', $viewData);
    }

    /**
     * 白地図マスターの新規登録
     */
    public function store(StoreSiteBaseMapRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', SiteBaseMap::class);

        try {
            $baseMap = $this->siteBaseMapService->createBaseMap(
                $request->validated(),
                $request->file('svg_file'),
                $request->file('pdf_file')
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "白地図「{$baseMap->name}」を登録しました。",
                    'base_map' => $baseMap,
                ]);
            }

            return redirect()->route('site-base-maps.index')
                ->with('status', "白地図「{$baseMap->name}」を登録しました。");
        } catch (\DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('site-base-maps.index')
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * 白地図マスターの更新
     */
    public function update(UpdateSiteBaseMapRequest $request, SiteBaseMap $siteBaseMap): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $siteBaseMap);

        try {
            $updated = $this->siteBaseMapService->updateBaseMap(
                $siteBaseMap,
                $request->validated(),
                $request->file('svg_file'),
                $request->file('pdf_file')
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "白地図「{$updated->name}」を更新しました。",
                    'base_map' => $updated,
                ]);
            }

            return redirect()->route('site-base-maps.index')
                ->with('status', "白地図「{$updated->name}」を更新しました。");
        } catch (\DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('site-base-maps.index')
                ->withErrors(['error' => $e->getMessage()])
                ->withInput();
        }
    }

    /**
     * 白地図マスターの削除
     */
    public function destroy(Request $request, SiteBaseMap $siteBaseMap): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $siteBaseMap);

        $name = $siteBaseMap->name;

        try {
            $this->siteBaseMapService->deleteBaseMap($siteBaseMap);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => "白地図「{$name}」を削除しました。",
                ]);
            }

            return redirect()->route('site-base-maps.index')
                ->with('status', "白地図「{$name}」を削除しました。");
        } catch (\DomainException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->route('site-base-maps.index')
                ->withErrors(['error' => $e->getMessage()]);
        }
    }
}
