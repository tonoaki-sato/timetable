<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLocationScheduleRequest;
use App\Http\Requests\UpdateLocationScheduleRequest;
use App\Models\Location;
use App\Models\LocationSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LocationScheduleController extends Controller
{
    /**
     * スケジュール枠の新規登録
     */
    public function store(StoreLocationScheduleRequest $request, Location $location): RedirectResponse|JsonResponse
    {
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

        return redirect()->route('locations.show', $location)
            ->with('status', '利用可能時間帯を登録しました。');
    }

    /**
     * スケジュール枠の更新
     */
    public function update(UpdateLocationScheduleRequest $request, Location $location, LocationSchedule $schedule): RedirectResponse|JsonResponse
    {
        abort_if($schedule->location_id !== $location->id, 404);

        $schedule->update($request->validated());

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '利用可能時間帯を更新しました。',
                'schedule' => $schedule,
            ]);
        }

        return redirect()->route('locations.show', $location)
            ->with('status', '利用可能時間帯を更新しました。');
    }

    /**
     * 申請ステータスの簡易更新（ワンクリック切り替え用）
     */
    public function updateStatus(Request $request, Location $location, LocationSchedule $schedule): RedirectResponse|JsonResponse
    {
        abort_if($schedule->location_id !== $location->id, 404);

        $validated = $request->validate([
            'application_status' => ['required', Rule::in(array_keys(LocationSchedule::APPLICATION_STATUSES))],
        ]);

        $schedule->update(['application_status' => $validated['application_status']]);

        $label = $schedule->application_status_label;

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "申請ステータスを「{$label}」に更新しました。",
                'schedule' => $schedule,
            ]);
        }

        return redirect()->route('locations.show', $location)
            ->with('status', "申請ステータスを「{$label}」に更新しました。");
    }

    /**
     * スケジュール枠の削除
     */
    public function destroy(Request $request, Location $location, LocationSchedule $schedule): RedirectResponse|JsonResponse
    {
        abort_if($schedule->location_id !== $location->id, 404);

        $schedule->delete();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '利用可能時間帯を削除しました。',
            ]);
        }

        return redirect()->route('locations.show', $location)
            ->with('status', '利用可能時間帯を削除しました。');
    }
}
