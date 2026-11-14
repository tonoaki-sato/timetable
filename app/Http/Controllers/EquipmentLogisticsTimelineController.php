<?php

namespace App\Http\Controllers;

use App\Models\Equipment;
use App\Models\Timetable;
use App\Services\EquipmentLogisticsService;
use App\Services\TimetableService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentLogisticsTimelineController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsService $logisticsService,
        protected TimetableService $timetableService
    ) {}

    /**
     * 搬出入タイムラインへのリダイレクト（アクティブまたは直近の運行日へ）
     */
    public function redirect(Request $request): RedirectResponse
    {
        if ($request->filled('timetable_id')) {
            $timetable = $this->timetableService->findOrFail($request->integer('timetable_id'));

            return redirect()->route('timetables.logistics-timeline', $timetable);
        }

        $currentTimetable = $this->timetableService->findFirstActiveOrEarliest();

        if ($currentTimetable) {
            return redirect()->route('timetables.logistics-timeline', $currentTimetable);
        }

        return redirect()->route('timetables.index');
    }

    /**
     * 指定タイムテーブルにおける搬出入タイムライン（ガントチャート）の表示
     */
    public function index(Timetable $timetable): View
    {
        $this->authorize('viewAny', Equipment::class);

        return view(
            'timetables.logistics_timeline',
            $this->logisticsService->getLogisticsTimelineViewData($timetable)
        );
    }
}
