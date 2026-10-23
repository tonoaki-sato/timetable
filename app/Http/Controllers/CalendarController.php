<?php

namespace App\Http\Controllers;

use App\Models\AnnualSchedule;
use App\Services\CalendarService;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(
        protected CalendarService $calendarService
    ) {}

    /**
     * 総合イベントカレンダー画面の表示
     */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', AnnualSchedule::class);

        $now = new DateTimeImmutable;
        $year = (int) $request->input('year', $now->format('Y'));
        $month = (int) $request->input('month', $now->format('n'));

        $category = $request->input('category');
        $status = $request->input('status');
        $search = $request->input('search');
        $staffId = $request->filled('staff_id') ? (int) $request->input('staff_id') : null;

        $viewData = $this->calendarService->getMonthCalendarViewData(
            year: $year,
            month: $month,
            category: $category,
            status: $status,
            search: $search,
            staffId: $staffId
        );

        $viewData['selectedYear'] = $year;
        $viewData['selectedMonth'] = $month;

        return view('calendar.index', $viewData);
    }
}
