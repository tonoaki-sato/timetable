<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Services\TimetableService;
use App\Services\TimetableTaskService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableAssignmentWorkspaceController extends Controller
{
    public function __construct(
        protected TimetableTaskService $taskService,
        protected TimetableService $timetableService
    ) {}

    /**
     * 人員事前配置ワークスペースへのリダイレクト（アクティブまたは直近の運行日へ）
     */
    public function redirect(Request $request): RedirectResponse
    {
        if ($request->filled('timetable_id')) {
            $timetable = $this->timetableService->findOrFail($request->integer('timetable_id'));

            return redirect()->route('timetables.assignments', $timetable);
        }

        $currentTimetable = $this->timetableService->findFirstActiveOrEarliest();

        if ($currentTimetable) {
            return redirect()->route('timetables.assignments', $currentTimetable);
        }

        return redirect()->route('timetables.index');
    }

    /**
     * 人員アサイン専用ワークスペース画面の表示
     */
    public function index(Request $request, Timetable $timetable): View
    {
        $this->authorize('view', $timetable);

        $requestedTaskId = $request->filled('task_id') ? $request->integer('task_id') : null;

        return view(
            'timetables.assignments.index',
            $this->taskService->getAssignmentWorkspaceViewData($timetable, $requestedTaskId)
        );
    }
}
