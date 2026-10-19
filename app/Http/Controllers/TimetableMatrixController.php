<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use App\Models\TimetableTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TimetableMatrixController extends Controller
{
    /**
     * タイムテーブル全体運行マトリクスビューの表示
     */
    public function index(Request $request): View
    {
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
        $dynamicLanes = collect();
        $totalRequired = 0;
        $totalAssigned = 0;
        $satisfiedTasksCount = 0;
        $tasksCount = 0;

        if ($selectedTimetable !== null) {
            $dynamicLanes = $selectedTimetable->lanes()->with('leadStaff')->get();
            if ($dynamicLanes->isEmpty()) {
                $selectedTimetable->createDefaultLanes();
                $dynamicLanes = $selectedTimetable->lanes()->with('leadStaff')->get();
            }

            $tasks = TimetableTask::with(['assignments.staff', 'laneRelation'])
                ->where('timetable_id', $selectedTimetable->id)
                ->orderBy('start_time')
                ->get();

            $tasksCount = $tasks->count();
            $satisfiedTasksCount = $tasks->filter(fn (TimetableTask $t) => $t->is_satisfied)->count();
            $totalRequired = $tasks->sum('required_people_count');
            $totalAssigned = $tasks->sum(fn (TimetableTask $t) => $t->assigned_count);

            foreach ($dynamicLanes as $lane) {
                $tasksByLane[$lane->id] = $tasks->filter(function (TimetableTask $t) use ($lane) {
                    if ($t->lane_id === $lane->id) {
                        return true;
                    }
                    if ($t->lane === $lane->name) {
                        return true;
                    }

                    // 旧名称（〜班）との互換マッチング
                    return $t->lane === ($lane->name.'班')
                        || ($lane->name === '巡回' && $t->lane === '誘導・警備班')
                        || ($lane->name === '本陣' && in_array($t->lane, ['救護班', '本部連絡班'], true));
                })->values();
            }
        }

        // タイムラインの時間帯（07:00 〜 19:00 の1時間刻み）
        $timeSlots = [];
        $startHour = 7;
        $endHour = 19;
        if ($selectedTimetable !== null) {
            $ttStartHour = (int) substr($selectedTimetable->start_time, 0, 2);
            $ttEndHour = (int) ceil(((int) substr($selectedTimetable->end_time, 0, 2)) + ((int) substr($selectedTimetable->end_time, 3, 2) > 0 ? 1 : 0));
            $startHour = max(6, min(7, $ttStartHour));
            $endHour = min(23, max(19, $ttEndHour));
        }

        for ($h = $startHour; $h <= $endHour; $h++) {
            $timeSlots[] = sprintf('%02d:00', $h);
        }

        $nowTime = Carbon::now('Asia/Tokyo')->format('H:i');

        return view('timetables.matrix', [
            'timetables' => $timetables,
            'selectedTimetable' => $selectedTimetable,
            'lanes' => $dynamicLanes,
            'dynamicLanes' => $dynamicLanes,
            'tasksByLane' => $tasksByLane,
            'timeSlots' => $timeSlots,
            'startHour' => $startHour,
            'endHour' => $endHour,
            'nowTime' => $nowTime,
            'tasksCount' => $tasksCount,
            'satisfiedTasksCount' => $satisfiedTasksCount,
            'totalRequired' => $totalRequired,
            'totalAssigned' => $totalAssigned,
        ]);
    }
}
