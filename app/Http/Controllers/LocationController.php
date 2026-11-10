<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReorderLocationsRequest;
use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use App\Models\Timetable;
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
     * 指定タイムテーブルにおける拠点一覧表示
     */
    public function index(Timetable $timetable, Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Location::class);

        $viewData = $this->locationService->getIndexViewData($timetable);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'timetable' => $timetable,
                'locations' => $viewData['locationsWithStatus'],
            ]);
        }

        return view('locations.index', $viewData);
    }

    /**
     * 拠点の新規登録画面
     */
    public function create(Timetable $timetable): View
    {
        $this->authorize('create', Location::class);

        $types = $this->locationService->getActiveLocationTypes();

        return view('locations.create', [
            'timetable' => $timetable,
            'types' => $types,
        ]);
    }

    /**
     * 拠点の新規登録処理
     */
    public function store(StoreLocationRequest $request, Timetable $timetable): RedirectResponse|JsonResponse
    {
        $this->authorize('create', Location::class);

        $location = $this->locationService->createLocation(
            $timetable,
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

        return redirect()->route('timetables.locations.show', [$timetable, $location])
            ->with('status', "拠点「{$location->name}」を登録しました。");
    }

    /**
     * 拠点の表示順一括更新（ドラッグ＆ドロップ用）
     */
    public function reorder(ReorderLocationsRequest $request, Timetable $timetable): JsonResponse
    {
        $this->authorize('update', Location::class);

        $this->locationService->reorderLocations($timetable, $request->validated('location_ids'));

        return response()->json([
            'success' => true,
            'message' => '拠点の表示順を更新しました。',
        ]);
    }

    /**
     * 拠点の詳細・時間帯スケジュール管理画面
     */
    public function show(Timetable $timetable, Location $location): View
    {
        abort_if($location->timetable_id !== $timetable->id, 404);
        $this->authorize('view', $location);

        return view('locations.show', $this->locationService->getShowViewData($timetable, $location));
    }

    /**
     * 拠点の編集画面
     */
    public function edit(Timetable $timetable, Location $location): View
    {
        abort_if($location->timetable_id !== $timetable->id, 404);
        $this->authorize('update', $location);

        return view('locations.edit', $this->locationService->getEditViewData($timetable, $location));
    }

    /**
     * 拠点の更新処理
     */
    public function update(UpdateLocationRequest $request, Timetable $timetable, Location $location): RedirectResponse|JsonResponse
    {
        abort_if($location->timetable_id !== $timetable->id, 404);
        $this->authorize('update', $location);

        $location = $this->locationService->updateLocation($location, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点「{$location->name}」を更新しました。",
                'location' => $location,
            ]);
        }

        return redirect()->route('timetables.locations.index', $timetable)
            ->with('status', "拠点「{$location->name}」の基本情報を更新しました。");
    }

    /**
     * 拠点の削除処理
     */
    public function destroy(Request $request, Timetable $timetable, Location $location): RedirectResponse|JsonResponse
    {
        abort_if($location->timetable_id !== $timetable->id, 404);
        $this->authorize('delete', $location);

        $name = $location->name;
        $this->locationService->deleteLocation($location);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点「{$name}」を削除しました。",
            ]);
        }

        return redirect()->route('timetables.locations.index', $timetable)
            ->with('status', "拠点「{$name}」を削除しました。");
    }

    // ==========================================
    // 既存互換フォールバックアクション群
    // ==========================================

    public function createRedirect(Request $request): View
    {
        $timetable = $this->resolveFallbackTimetable($request);

        return $this->create($timetable);
    }

    public function storeLegacy(StoreLocationRequest $request): RedirectResponse|JsonResponse
    {
        $timetable = $this->resolveFallbackTimetable($request);

        return $this->store($request, $timetable);
    }

    public function showLegacy(Location $location): RedirectResponse|View
    {
        $timetable = $location->timetable ?? $this->resolveFallbackTimetable();

        return $this->show($timetable, $location);
    }

    public function editLegacy(Location $location): RedirectResponse|View
    {
        $timetable = $location->timetable ?? $this->resolveFallbackTimetable();

        return $this->edit($timetable, $location);
    }

    public function updateLegacy(UpdateLocationRequest $request, Location $location): RedirectResponse|JsonResponse
    {
        $timetable = $location->timetable ?? $this->resolveFallbackTimetable($request);

        return $this->update($request, $timetable, $location);
    }

    public function destroyLegacy(Request $request, Location $location): RedirectResponse|JsonResponse
    {
        $timetable = $location->timetable ?? $this->resolveFallbackTimetable($request);

        return $this->destroy($request, $timetable, $location);
    }

    protected function resolveFallbackTimetable(?Request $request = null): Timetable
    {
        $timetableId = ($request && $request->filled('timetable_id'))
            ? $request->integer('timetable_id')
            : null;

        return $this->locationService->resolveFallbackTimetable($timetableId);
    }
}
