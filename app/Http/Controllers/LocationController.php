<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderLocationsRequest;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct(
        protected LocationService $locationService
    ) {}

    /**
     * 拠点マスター一覧表示
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Location::class);

        $viewData = $this->locationService->getIndexViewData();
        $viewData['types'] = $this->locationService->getActiveLocationTypes();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'locations' => $viewData['locationsWithStatus'],
            ]);
        }

        return view('locations.index', $viewData);
    }

    /**
     * 拠点の新規登録処理
     */
    public function store(StoreLocationRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Location::class);

        $location = $this->locationService->createLocation(
            $request->validated(),
            $request->user()?->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点「{$location->name}」を登録しました。",
                'location' => $location,
            ]);
        }

        return redirect()->route('locations.index')
            ->with('status', "拠点「{$location->name}」を登録しました。");
    }

    /**
     * 拠点の表示順一括更新（ドラッグ＆ドロップ用）
     */
    public function reorder(ReorderLocationsRequest $request): JsonResponse
    {
        $this->authorize('update', Location::class);

        $this->locationService->reorderLocations($request->validated('location_ids'));

        return response()->json([
            'success' => true,
            'message' => '拠点の表示順を更新しました。',
        ]);
    }

    /**
     * 拠点の更新処理
     */
    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse|JsonResponse
    {
        $this->authorize('update', $location);

        $location = $this->locationService->updateLocation($location, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点「{$location->name}」を更新しました。",
                'location' => $location,
            ]);
        }

        return redirect()->route('locations.index')
            ->with('status', "拠点「{$location->name}」の基本情報を更新しました。");
    }

    /**
     * 拠点の削除処理
     */
    public function destroy(Request $request, Location $location): RedirectResponse|JsonResponse
    {
        $this->authorize('delete', $location);

        $name = $location->name;
        $this->locationService->deleteLocation($location);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点「{$name}」を削除しました。",
            ]);
        }

        return redirect()->route('locations.index')
            ->with('status', "拠点「{$name}」を削除しました。");
    }
}
