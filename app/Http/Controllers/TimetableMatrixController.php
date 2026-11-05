<?php

namespace App\Http\Controllers;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\TimetableGridService;
use Carbon\Carbon;
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

        $timetables = Timetable::orderBy('target_date')->get();

        $selectedTimetableId = $request->integer('timetable_id');
        $selectedTimetable = null;

        if ($selectedTimetableId > 0) {
            $selectedTimetable = $timetables->firstWhere('id', $selectedTimetableId);
        }

        if ($selectedTimetable === null) {
            $selectedTimetable = $timetables->firstWhere('status', 'active') ?? $timetables->first();
        }

        $tasksByLane = [];
        $lanes = collect();
        $totalRequired = 0;
        $totalAssigned = 0;
        $satisfiedTasksCount = 0;
        $tasksCount = 0;

        if ($selectedTimetable !== null) {
            if ($selectedTimetable->lanes()->doesntExist()) {
                $selectedTimetable->createDefaultLanes();
            }

            $lanes = $selectedTimetable->lanes()
                ->with('leadStaff')
                ->ordered()
                ->get();

            $tasks = TimetableTask::with(['assignments.staff', 'laneRelation'])
                ->where('timetable_id', $selectedTimetable->id)
                ->orderBy('start_time')
                ->get();

            $tasksCount = $tasks->count();
            $satisfiedTasksCount = $tasks->filter(fn (TimetableTask $t) => $t->is_satisfied)->count();
            $shortageTasks = $tasks->filter(fn (TimetableTask $t) => ! $t->is_satisfied)->values();
            $totalRequired = $tasks->sum('required_people_count');
            $totalAssigned = $tasks->sum(fn (TimetableTask $t) => $t->assigned_count);

            foreach ($lanes as $lane) {
                $tasksByLane[$lane->id] = $tasks->filter(function (TimetableTask $t) use ($lane) {
                    return $t->lane_id === $lane->id;
                })->values();
            }
        }

        // 30分刻みの時間スロット一覧を生成（タスク0件でも運行日作成時点でスケルトンを表示）
        $timeSlots = $this->gridService->generateTimeSlots($selectedTimetable);

        // 各スロット × 各レーンのグリッドデータおよび rowspan を計算
        $grid = $this->gridService->buildMatrixGrid($timeSlots, $lanes, $tasksByLane);

        $nowTime = Carbon::now('Asia/Tokyo')->format('H:i');
        $activeStaff = Staff::active()->orderBy('affiliation')->orderBy('name')->get();

        return view('timetables.matrix', [
            'timetables' => $timetables,
            'selectedTimetable' => $selectedTimetable,
            'lanes' => $lanes,
            'tasksByLane' => $tasksByLane,
            'timeSlots' => $timeSlots,
            'grid' => $grid,
            'nowTime' => $nowTime,
            'tasksCount' => $tasksCount,
            'satisfiedTasksCount' => $satisfiedTasksCount,
            'shortageTasks' => $shortageTasks ?? collect(),
            'totalRequired' => $totalRequired,
            'totalAssigned' => $totalAssigned,
            'activeStaff' => $activeStaff,
            'colorOptions' => Lane::COLOR_OPTIONS,
        ]);
    }
}
