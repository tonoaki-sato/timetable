<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Services\TimetableGridService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableMatrixController extends Controller
{
    public function __construct(
        protected TimetableGridService $gridService
    ) {}

    /**
     * タイムテーブル（当日進行表・帳票グリッド）の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Timetable::class);

        $selectedTimetableId = $request->filled('timetable_id') ? $request->integer('timetable_id') : null;

        return view('timetables.matrix', $this->gridService->getMatrixViewData($selectedTimetableId));
    }
}
