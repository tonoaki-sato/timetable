<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationRequest;
use App\Http\Requests\UpdateLocationRequest;
use App\Models\Location;
use App\Models\LocationType;
use App\Models\Timetable;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function __construct(
        protected LocationRepositoryInterface $locationRepository
    ) {}

    /**
     * 指定タイムテーブルにおける拠点一覧表示
     */
    public function index(Timetable $timetable, Request $request): View|JsonResponse
    {
        $this->authorize('viewAny', Location::class);

        $now = Carbon::now('Asia/Tokyo');
        $targetDate = $timetable->target_date?->format('Y-m-d') ?? $now->format('Y-m-d');

        $locations = $this->locationRepository->getForTimetable($timetable);

        $locationsWithStatus = $locations->map(function (Location $location) use ($now, $targetDate) {
            $evaluation = $location->evaluateStatusAt($now);
            $targetSchedules = $location->schedules->filter(function ($s) use ($targetDate) {
                return $s->start_at->format('Y-m-d') === $targetDate || $s->end_at->format('Y-m-d') === $targetDate;
            });

            return [
                'location' => $location,
                'evaluation' => $evaluation,
                'todaySchedules' => $targetSchedules,
            ];
        });

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'timetable' => $timetable,
                'locations' => $locationsWithStatus,
            ]);
        }

        return view('locations.index', [
            'timetable' => $timetable,
            'locationsWithStatus' => $locationsWithStatus,
            'now' => $now,
        ]);
    }

    /**
     * 拠点の新規登録画面
     */
    public function create(Timetable $timetable): View
    {
        $this->authorize('create', Location::class);

        $types = LocationType::active()->ordered()->get();

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

        $validated = $request->validated();
        $validated['created_by'] = $request->user()?->id;

        $location = $this->locationRepository->create($timetable, $validated);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "拠点「{$location->name}」を登録しました。",
                'location' => $location,
            ]);
        }

        return redirect()->route('timetables.locations.show', [$timetable, $location])
            ->with('status', "拠点「{$location->name}」を登録しました。続けて利用可能時間帯を設定してください。");
    }

    /**
     * 拠点の詳細・時間帯スケジュール管理画面
     */
    public function show(Timetable $timetable, Location $location): View
    {
        abort_if($location->timetable_id !== $timetable->id, 404);
        $this->authorize('view', $location);

        $locationWithDetails = $this->locationRepository->findWithDetails($timetable, $location->id) ?? $location;
        $evaluation = $locationWithDetails->evaluateStatusAt(Carbon::now('Asia/Tokyo'));

        return view('locations.show', [
            'timetable' => $timetable,
            'location' => $locationWithDetails,
            'evaluation' => $evaluation,
        ]);
    }

    /**
     * 拠点の編集画面
     */
    public function edit(Timetable $timetable, Location $location): View
    {
        abort_if($location->timetable_id !== $timetable->id, 404);
        $this->authorize('update', $location);

        $types = LocationType::active()->ordered()->get();

        return view('locations.edit', [
            'timetable' => $timetable,
            'location' => $location,
            'types' => $types,
        ]);
    }

    /**
     * 拠点の更新処理
     */
    public function update(UpdateLocationRequest $request, Timetable $timetable, Location $location): RedirectResponse|JsonResponse
    {
        abort_if($location->timetable_id !== $timetable->id, 404);
        $this->authorize('update', $location);

        $this->locationRepository->update($location, $request->validated());

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
        $this->locationRepository->delete($location);

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
        if ($request && $request->filled('timetable_id')) {
            $found = Timetable::find($request->integer('timetable_id'));
            if ($found) {
                return $found;
            }
        }

        return Timetable::where('status', 'active')->orderBy('target_date', 'asc')->first()
            ?? Timetable::orderBy('target_date', 'asc')->first()
            ?? Timetable::factory()->create(['name' => '既定運行計画', 'target_date' => now()->toDateString()]);
    }
}
