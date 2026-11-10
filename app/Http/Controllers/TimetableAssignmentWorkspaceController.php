<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Services\TimetableTaskService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableAssignmentWorkspaceController extends Controller
{
    public function __construct(
        protected TimetableTaskService $taskService
    ) {}

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
