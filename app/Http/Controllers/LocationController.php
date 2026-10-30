<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use App\Models\LocationType;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    /**
     * 拠点一覧表示（スリムビュー）
     */
    public function index(Request $request): View|JsonResponse
    {
        $now = Carbon::now('Asia/Tokyo');
        $today = $now->format('Y-m-d');

        $locations = Location::with(['type', 'schedules' => function ($query) {
            $query->orderBy('start_at', 'asc');
        }])
            ->ordered()
            ->get();

        $locationsWithStatus = $locations->map(function (Location $location) use ($now, $today) {
            $evaluation = $location->evaluateStatusAt($now);
            $todaySchedules = $location->schedules->filter(function ($s) use ($today) {
                return $s->start_at->format('Y-m-d') === $today || $s->end_at->format('Y-m-d') === $today;
            });

            return [
                'location' => $location,
                'evaluation' => $evaluation,
                'todaySchedules' => $todaySchedules,
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'locations' => $locationsWithStatus,
            ]);
        }

        return view('locations.index', [
            'locationsWithStatus' => $locationsWithStatus,
            'now' => $now,
        ]);
    }

    /**
     * 拠点の新規登録画面
     */
    public function create(): View
    {
        $types = LocationType::active()->ordered()->get();

        return view('locations.create', compact('types'));
    }

    /**
     * 拠点の新規登録処理
     */
    public function store(StoreLocationRequest $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validated();
        $validated['created_by'] = $request->user()?->id;

        $location = Location::create($validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点「{$location->name}」を登録しました。",
                'location' => $location,
            ]);
        }

        return redirect()->route('locations.show', $location)
            ->with('status', "拠点「{$location->name}」を登録しました。続けて利用可能時間帯を設定してください。");
    }

    /**
     * 拠点の詳細・時間帯スケジュール管理画面
     */
    public function show(Location $location): View
    {
        $location->load(['type', 'schedules' => function ($query) {
            $query->orderBy('start_at', 'asc');
        }]);

        $evaluation = $location->evaluateStatusAt(Carbon::now('Asia/Tokyo'));

        return view('locations.show', compact('location', 'evaluation'));
    }

    /**
     * 拠点の編集画面
     */
    public function edit(Location $location): View
    {
        $types = LocationType::active()->ordered()->get();

        return view('locations.edit', compact('location', 'types'));
    }

    /**
     * 拠点の更新処理
     */
    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse|JsonResponse
    {
        $location->update($request->validated());

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
        $name = $location->name;
        $location->delete();

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
