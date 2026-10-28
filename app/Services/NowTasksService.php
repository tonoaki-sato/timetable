<?php

namespace App\Services;

use App\DTOs\NowTasksContext;
use App\Models\Timetable;
use App\Models\TimetableTask;
use Carbon\Carbon;

class NowTasksService
{
    /**
     * 現在（または指定日時）の進行中タスク状況を一括取得
     */
    public function getCurrentContext(?Carbon $now = null): NowTasksContext
    {
        $now = $now ?? Carbon::now('Asia/Tokyo');
        $todayStr = $now->toDateString();
        $currentTime = $now->format('H:i:s');
        $currentTimeLabel = $now->format('H:i');

        $timetable = Timetable::with([
            'tasks.laneRelation.leadStaff',
            'tasks.assignments',
        ])
            ->whereDate('target_date', $todayStr)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        if ($timetable === null) {
            return new NowTasksContext(
                isMainEventDay: false,
                activeTimetable: null,
                tasks: collect(),
                currentTimeLabel: $currentTimeLabel,
                actionUrl: route('timetables.index'),
            );
        }

        $nowTasks = $timetable->tasks
            ->filter(function (TimetableTask $task) use ($currentTime): bool {
                if ($task->status === 'in_progress') {
                    return true;
                }

                if ($task->status === 'not_started') {
                    $start = $task->start_time;
                    $end = $task->end_time;

                    if ($start === $end) {
                        return $start <= $currentTime && $currentTime <= $end;
                    }

                    return $start <= $currentTime && $currentTime < $end;
                }

                return false;
            })
            ->sort(function (TimetableTask $a, TimetableTask $b): int {
                if ($a->is_critical !== $b->is_critical) {
                    return $b->is_critical <=> $a->is_critical;
                }

                $aInProgress = $a->status === 'in_progress' ? 1 : 0;
                $bInProgress = $b->status === 'in_progress' ? 1 : 0;
                if ($aInProgress !== $bInProgress) {
                    return $bInProgress <=> $aInProgress;
                }

                if ($a->start_time !== $b->start_time) {
                    return $a->start_time <=> $b->start_time;
                }

                if ($a->end_time !== $b->end_time) {
                    return $a->end_time <=> $b->end_time;
                }

                return $a->id <=> $b->id;
            })
            ->values();

        return new NowTasksContext(
            isMainEventDay: true,
            activeTimetable: $timetable,
            tasks: $nowTasks,
            currentTimeLabel: $currentTimeLabel,
            actionUrl: route('timetable.index', ['timetable_id' => $timetable->id]),
        );
    }
}
