<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationScheduleRequest;
use App\Http\Requests\UpdateLocationScheduleRequest;
use App\Http\Requests\UpdateLocationScheduleStatusRequest;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\Timetable;
use App\Services\LocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationScheduleController extends Controller
{
    public function __construct(
        protected LocationService $locationService
    ) {}

    /**
     * スケジュール枠の新規登録
     */
    public function store(StoreLocationScheduleRequest $request, Timetable $timetable, Location $location): RedirectResponse|JsonResponse
    {
        abort_if($location->timetable_id !== $timetable->id, 404);
        $this->authorize('create', LocationSchedule::class);

        $schedule = $this->locationService->createSchedule($location, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '利用可能時間帯を登録しました。',
                'schedule' => $schedule,
            ]);
        }

        return redirect()->route('timetables.locations.show', [$timetable, $location])
            ->with('status', '利用可能時間帯を登録しました。');
    }

    /**
     * スケジュール枠の更新
     */
    public function update(UpdateLocationScheduleRequest $request, Timetable $timetable, Location $location, LocationSchedule $schedule): RedirectResponse|JsonResponse
    {
        abort_if($location->timetable_id !== $timetable->id || $schedule->location_id !== $location->id, 404);
        $this->authorize('update', $schedule);

        $schedule = $this->locationService->updateSchedule($schedule, $request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '利用可能時間帯を更新しました。',
                'schedule' => $schedule,
            ]);
        }

        return redirect()->route('timetables.locations.show', [$timetable, $location])
            ->with('status', '利用可能時間帯を更新しました。');
    }

    /**
     * スケジュール枠のステータス更新
     */
    public function updateStatus(UpdateLocationScheduleStatusRequest $request, Timetable $timetable, Location $location, LocationSchedule $schedule): RedirectResponse|JsonResponse
    {
        abort_if($location->timetable_id !== $timetable->id || $schedule->location_id !== $location->id, 404);
        $this->authorize('update', $schedule);

        $schedule = $this->locationService->updateScheduleStatus(
            $schedule,
            $request->validated('availability_status')
        );

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'ステータスを更新しました。',
                'schedule' => $schedule,
            ]);
        }

        return redirect()->route('timetables.locations.show', [$timetable, $location])
            ->with('status', 'ステータスを更新しました。');
    }

    /**
     * スケジュール枠の削除
     */
    public function destroy(Request $request, Timetable $timetable, Location $location, LocationSchedule $schedule): RedirectResponse|JsonResponse
    {
        abort_if($location->timetable_id !== $timetable->id || $schedule->location_id !== $location->id, 404);
        $this->authorize('delete', $schedule);

        $this->locationService->deleteSchedule($schedule);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '利用可能時間帯を削除しました。',
            ]);
        }

        return redirect()->route('timetables.locations.show', [$timetable, $location])
            ->with('status', '利用可能時間帯を削除しました。');
    }

    // ==========================================
    // 既存互換フォールバックアクション群
    // ==========================================

    public function storeLegacy(StoreLocationScheduleRequest $request, Location $location): RedirectResponse|JsonResponse
    {
        $timetable = $location->timetable ?? $this->resolveFallbackTimetable();

        return $this->store($request, $timetable, $location);
    }

    public function updateLegacy(UpdateLocationScheduleRequest $request, Location $location, LocationSchedule $schedule): RedirectResponse|JsonResponse
    {
        $timetable = $location->timetable ?? $this->resolveFallbackTimetable();

        return $this->update($request, $timetable, $location, $schedule);
    }

    public function updateStatusLegacy(UpdateLocationScheduleStatusRequest $request, Location $location, LocationSchedule $schedule): RedirectResponse|JsonResponse
    {
        $timetable = $location->timetable ?? $this->resolveFallbackTimetable();

        return $this->updateStatus($request, $timetable, $location, $schedule);
    }

    public function destroyLegacy(Request $request, Location $location, LocationSchedule $schedule): RedirectResponse|JsonResponse
    {
        $timetable = $location->timetable ?? $this->resolveFallbackTimetable();

        return $this->destroy($request, $timetable, $location, $schedule);
    }

    protected function resolveFallbackTimetable(): Timetable
    {
        return $this->locationService->resolveFallbackTimetable();
    }
}
