<?php

namespace App\Http\Controllers;

use App\Actions\Timetable\ResolveActiveTimetableRedirectAction;
use App\Models\Timetable;
use App\Services\LaneTimelineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaneTimelineController extends Controller
{
    public function __construct(
        protected LaneTimelineService $laneTimelineService,
    ) {}

    /**
     * 班別タイムライン代表ルートからのリダイレクト
     */
    public function redirect(Request $request, ResolveActiveTimetableRedirectAction $redirectAction): RedirectResponse
    {
        return $redirectAction->execute($request, 'timetables.lane-timeline');
    }

    /**
     * 班別タイムライン（担当班別タスクガントチャート）の表示
     */
    public function index(Timetable $timetable): View
    {
        $this->authorize('view', $timetable);

        return view('timetables.lane_timeline', $this->laneTimelineService->getLaneTimelineViewData($timetable));
    }
}
