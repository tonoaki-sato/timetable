<?php

namespace App\Http\Controllers;

use App\Actions\Timetable\ResolveActiveTimetableRedirectAction;
use App\Models\Equipment;
use App\Models\Timetable;
use App\Services\EquipmentLogisticsTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EquipmentLogisticsTimelineController extends Controller
{
    public function __construct(
        protected EquipmentLogisticsTimelineService $timelineService,
    ) {}

    /**
     * 搬出入タイムラインへのリダイレクト（アクティブまたは直近の運行日へ）
     */
    public function redirect(Request $request, ResolveActiveTimetableRedirectAction $redirectAction): RedirectResponse
    {
        return $redirectAction->execute($request, 'timetables.logistics-timeline');
    }

    /**
     * 指定タイムテーブルにおける搬出入タイムライン（ガントチャート）の表示
     */
    public function index(Timetable $timetable): View
    {
        $this->authorize('viewAny', Equipment::class);

        return view(
            'timetables.logistics_timeline',
            $this->timelineService->getLogisticsTimelineViewData($timetable)
        );
    }
}
