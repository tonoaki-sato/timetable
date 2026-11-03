<?php

namespace App\Services;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class TimetableGridService
{
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
    public function generateTimeSlots(?Timetable $timetable): array
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

            $roadData = $timetable !== null
                ? $timetable->getRoadStatusAt($timeStr)
                : ['status' => 'open', 'label' => '-'];

            $festivalData = $timetable !== null
                ? $timetable->getFestivalStatusAt($timeStr)
                : ['status' => 'inactive', 'label' => '-'];

            $slots[] = [
                'time' => $timeStr,
                'label' => $timeStr,
                'road' => $roadData,
                'festival' => $festivalData,
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
    public function buildMatrixGrid(array $timeSlots, Collection $lanes, array $tasksByLane): array
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

    /**
     * スタッフ × 時間スロットのグリッドセル（横結合 colspan）を算出
     *
     * @param  array<int, array<string, mixed>>  $timeSlots
     * @param  Collection<int, Staff>  $activeStaff
     * @param  array<int, Collection<int, array{task: TimetableTask, assignment: TaskStaffAssignment}>>  $assignmentsByStaff
     * @return array<int, array<int, array{type: string, colspan?: int, task?: TimetableTask, assignment?: TaskStaffAssignment}>>
     */
    public function buildTimelineGrid(array $timeSlots, Collection $activeStaff, array $assignmentsByStaff): array
    {
        $grid = [];
        $totalSlots = count($timeSlots);

        foreach ($activeStaff as $staff) {
            $staffGrid = [];
            $coveredUntil = -1;
            $staffAssignments = $assignmentsByStaff[$staff->id] ?? collect();

            foreach ($timeSlots as $sIdx => $slot) {
                if ($coveredUntil >= $sIdx) {
                    $staffGrid[$sIdx] = ['type' => 'skip'];

                    continue;
                }

                $slotCarbon = Carbon::createFromFormat('H:i', $slot['time']);
                $slotEndCarbon = $slotCarbon->copy()->addMinutes(30);

                // このスロットの時間帯に開始または進行中のタスクを検索
                $match = $staffAssignments->first(function ($item) use ($slotCarbon, $slotEndCarbon) {
                    $t = $item['task'];
                    $tStart = Carbon::createFromFormat('H:i', substr($t->start_time, 0, 5));

                    return $tStart->gte($slotCarbon) && $tStart->lt($slotEndCarbon);
                });

                if (! $match) {
                    $match = $staffAssignments->first(function ($item) use ($slotCarbon) {
                        $t = $item['task'];
                        $tStart = Carbon::createFromFormat('H:i', substr($t->start_time, 0, 5));
                        $tEnd = Carbon::createFromFormat('H:i', substr($t->end_time, 0, 5));

                        return $tStart->lte($slotCarbon) && $tEnd->gt($slotCarbon);
                    });
                }

                if ($match) {
                    $task = $match['task'];
                    $tEnd = Carbon::createFromFormat('H:i', substr($task->end_time, 0, 5));
                    $diffMinutes = max(30, $slotCarbon->diffInMinutes($tEnd, false));
                    $span = (int) ceil($diffMinutes / 30);
                    $span = min($span, $totalSlots - $sIdx);
                    $span = max(1, $span);

                    $coveredUntil = $sIdx + $span - 1;

                    $staffGrid[$sIdx] = [
                        'type' => 'task',
                        'colspan' => $span,
                        'task' => $task,
                        'assignment' => $match['assignment'],
                    ];
                } else {
                    $staffGrid[$sIdx] = [
                        'type' => 'empty',
                        'colspan' => 1,
                    ];
                }
            }

            $grid[$staff->id] = $staffGrid;
        }

        return $grid;
    }
}
