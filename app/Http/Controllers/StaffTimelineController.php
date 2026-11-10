<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Services\TimetableGridService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffTimelineController extends Controller
{
    public function __construct(
        protected TimetableGridService $gridService
    ) {}

    /**
     * スタッフ別リソースタイムライン（ガントチャート）の表示
     */
    public function index(Request $request, Timetable $timetable): View
    {
        $this->authorize('view', $timetable);

        return view('timetables.staff_timeline', $this->gridService->getStaffTimelineViewData($timetable));
    }
}
