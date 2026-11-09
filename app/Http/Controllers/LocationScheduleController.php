<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationScheduleRequest;
use App\Http\Requests\UpdateLocationScheduleRequest;
use App\Http\Requests\UpdateLocationScheduleStatusRequest;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\Timetable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocationScheduleController extends Controller
{
    /**
     * スケジュール枠の新規登録
     */
    public function store(StoreLocationScheduleRequest $request, Timetable $timetable, Location $location): RedirectResponse|JsonResponse
    {
        abort_if($location->timetable_id !== $timetable->id, 404);
        $this->authorize('create', LocationSchedule::class);

        $validated = $request->validated();
        $validated['location_id'] = $location->id;

        $schedule = LocationSchedule::create($validated);

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

        $schedule->update($request->validated());

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

        $schedule->update([
            'availability_status' => $request->validated('availability_status'),
        ]);

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

        $schedule->delete();

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
        return Timetable::where('status', 'active')->orderBy('target_date', 'asc')->first()
            ?? Timetable::orderBy('target_date', 'asc')->first()
            ?? Timetable::factory()->create(['name' => '既定運行計画', 'target_date' => now()->toDateString()]);
    }
}
