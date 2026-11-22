<?php

namespace App\Http\Controllers;

use App\Actions\Timetable\ResolveActiveTimetableRedirectAction;
use App\Models\Timetable;
use App\Services\TimetableTaskAssignmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableAssignmentWorkspaceController extends Controller
{
    public function __construct(
        protected TimetableTaskAssignmentService $taskAssignmentService,
    ) {}

    /**
     * 人員事前配置ワークスペースへのリダイレクト（アクティブまたは直近の運行日へ）
     */
    public function redirect(Request $request, ResolveActiveTimetableRedirectAction $redirectAction): RedirectResponse
    {
        return $redirectAction->execute($request, 'timetables.assignments');
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
            $this->taskAssignmentService->getAssignmentWorkspaceViewData($timetable, $requestedTaskId)
        );
    }
}
