<?php

namespace App\Http\Controllers;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class TimetableMatrixController extends Controller
{
    /**
     * タイムテーブル（当日進行表・帳票グリッド）の表示
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
        $lanes = collect();
        $totalRequired = 0;
        $totalAssigned = 0;
        $satisfiedTasksCount = 0;
        $tasksCount = 0;

        if ($selectedTimetable !== null) {
            $lanes = $selectedTimetable->lanes()
                ->with('leadStaff')
                ->active()
                ->ordered()
                ->get();

            if ($lanes->isEmpty()) {
                $selectedTimetable->createDefaultLanes();
                $lanes = $selectedTimetable->lanes()
                    ->with('leadStaff')
                    ->active()
                    ->ordered()
                    ->get();
            }

            $tasks = TimetableTask::with(['assignments.staff', 'laneRelation'])
                ->where('timetable_id', $selectedTimetable->id)
                ->orderBy('start_time')
                ->get();

            $tasksCount = $tasks->count();
            $satisfiedTasksCount = $tasks->filter(fn (TimetableTask $t) => $t->is_satisfied)->count();
            $totalRequired = $tasks->sum('required_people_count');
            $totalAssigned = $tasks->sum(fn (TimetableTask $t) => $t->assigned_count);

            foreach ($lanes as $lane) {
                $tasksByLane[$lane->id] = $tasks->filter(function (TimetableTask $t) use ($lane) {
                    return $t->lane_id === $lane->id;
                })->values();
            }
        }

        // 30分刻みの時間スロット一覧を生成（タスク0件でも運行日作成時点でスケルトンを表示）
        $timeSlots = $this->generateTimeSlots($selectedTimetable);

        // 各スロット × 各レーンのグリッドデータおよび rowspan を計算
        $grid = $this->buildGridData($timeSlots, $lanes, $tasksByLane);

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
            'totalRequired' => $totalRequired,
            'totalAssigned' => $totalAssigned,
            'activeStaff' => $activeStaff,
            'colorOptions' => Lane::COLOR_OPTIONS,
        ]);
    }

    /**
     * 運行タイムテーブルの開始・終了時刻から30分刻みの時間スロット配列を生成
     *
     * @return array<int, array{
     *     time: string,
     *     label: string,
     *     road: array{status: string, label: string},
     *     festival: array{status: string, label: string}
     * }>
     */
    protected function generateTimeSlots(?Timetable $timetable): array
    {
        $startTime = '07:00';
        $endTime = '19:30';

        if ($timetable !== null) {
            if (! empty($timetable->start_time)) {
                $sH = (int) substr($timetable->start_time, 0, 2);
                $sM = (int) substr($timetable->start_time, 3, 2);
                $sM = $sM >= 30 ? 30 : 0;
                $startTime = sprintf('%02d:%02d', $sH, $sM);
            }
            if (! empty($timetable->end_time)) {
                $eH = (int) substr($timetable->end_time, 0, 2);
                $eM = (int) substr($timetable->end_time, 3, 2);
                if ($eM > 30) {
                    $eH++;
                    $eM = 0;
                } elseif ($eM > 0) {
                    $eM = 30;
                }
                $endTime = sprintf('%02d:%02d', $eH, $eM);
            }
        }

        $slots = [];
        $current = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        $count = 0;
        while ($current->lte($end) && $count < 48) {
            $timeStr = $current->format('H:i');

            // 道路規制判定（10:00 〜 17:30 まで「通行止め」）
            $isRoadClosed = ($timeStr >= '10:00' && $timeStr <= '17:30');

            // まつり開催中判定（11:00 〜 16:30 まで「開催中」）
            $isFestivalActive = ($timeStr >= '11:00' && $timeStr <= '16:30');

            $slots[] = [
                'time' => $timeStr,
                'label' => $timeStr,
                'road' => [
                    'status' => $isRoadClosed ? 'closed' : 'open',
                    'label' => $isRoadClosed ? '通行止め' : '-',
                ],
                'festival' => [
                    'status' => $isFestivalActive ? 'active' : 'inactive',
                    'label' => $isFestivalActive ? '開催中' : '-',
                ],
            ];

            $current->addMinutes(30);
            $count++;
        }

        return $slots;
    }

    /**
     * スロット × レーンのセル情報（タスク、空枠、rowspan縦結合）を算出
     *
     * @param  array<int, array<string, mixed>>  $timeSlots
     * @param  Collection<int, Lane>  $lanes
     * @param  array<int, Collection<int, TimetableTask>>  $tasksByLane
     * @return array<int, array<int, array{type: string, rowspan?: int, task?: TimetableTask}>>
     */
    protected function buildGridData(array $timeSlots, Collection $lanes, array $tasksByLane): array
    {
        $grid = [];
        $coveredUntil = [];

        foreach ($lanes as $lane) {
            $coveredUntil[$lane->id] = -1;
        }

        $totalSlots = count($timeSlots);

        foreach ($timeSlots as $sIdx => $slot) {
            $slotTime = $slot['time'];
            $slotCarbon = Carbon::createFromFormat('H:i', $slotTime);
            $slotEndCarbon = $slotCarbon->copy()->addMinutes(30);

            $grid[$sIdx] = [];

            foreach ($lanes as $lane) {
                // すでに上位スロットのタスクによって縦結合でカバーされている場合
                if (($coveredUntil[$lane->id] ?? -1) >= $sIdx) {
                    $grid[$sIdx][$lane->id] = [
                        'type' => 'skip',
                    ];

                    continue;
                }

                $laneTasks = $tasksByLane[$lane->id] ?? collect();

                // このスロットの時間帯に開始する、または該当するタスクを探索
                $task = $laneTasks->first(function (TimetableTask $t) use ($slotCarbon, $slotEndCarbon) {
                    $tStart = Carbon::createFromFormat('H:i', substr($t->start_time, 0, 5));

                    return $tStart->gte($slotCarbon) && $tStart->lt($slotEndCarbon);
                });

                if (! $task) {
                    // スロット時間帯をまたいで進行中のタスク（開始時刻が以前でまだ処理されていない場合）
                    $task = $laneTasks->first(function (TimetableTask $t) use ($slotCarbon) {
                        $tStart = Carbon::createFromFormat('H:i', substr($t->start_time, 0, 5));
                        $tEnd = Carbon::createFromFormat('H:i', substr($t->end_time, 0, 5));

                        return $tStart->lte($slotCarbon) && $tEnd->gt($slotCarbon);
                    });
                }

                if ($task) {
                    $tEnd = Carbon::createFromFormat('H:i', substr($task->end_time, 0, 5));
                    $diffMinutes = max(30, $slotCarbon->diffInMinutes($tEnd, false));
                    $span = (int) ceil($diffMinutes / 30);

                    // 最大スロット数を超えないように調整
                    $span = min($span, $totalSlots - $sIdx);
                    $span = max(1, $span);

                    $coveredUntil[$lane->id] = $sIdx + $span - 1;

                    $grid[$sIdx][$lane->id] = [
                        'type' => 'task',
                        'rowspan' => $span,
                        'task' => $task,
                    ];
                } else {
                    $grid[$sIdx][$lane->id] = [
                        'type' => 'empty',
                        'rowspan' => 1,
                    ];
                }
            }
        }

        return $grid;
    }
}
