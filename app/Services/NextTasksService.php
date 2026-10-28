<?php

namespace App\Services;

use App\DTOs\NextTasksContext;
use App\Models\Timetable;
use App\Models\TimetableTask;
use Carbon\Carbon;

class NextTasksService
{
    public const int DEFAULT_LIMIT = 6;

    /**
     * 現在（または指定日時）における次に控えるタスク状況を一括取得
     */
    public function getCurrentContext(?Carbon $now = null, int $limit = self::DEFAULT_LIMIT): NextTasksContext
    {
        $now = $now ?? Carbon::now('Asia/Tokyo');
        $todayStr = $now->toDateString();
        $currentTime = $now->format('H:i:s');

        $timetable = Timetable::with([
            'tasks.laneRelation.leadStaff',
            'tasks.assignments',
        ])
            ->whereDate('target_date', $todayStr)
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();

        if ($timetable === null) {
            return new NextTasksContext(
                isMainEventDay: false,
                activeTimetable: null,
                tasks: collect(),
                totalNextCount: 0,
                nextStartTimeLabel: null,
                remainingTimeLabel: null,
                actionUrl: route('timetables.index'),
            );
        }

        $allNextTasks = $timetable->tasks
            ->filter(function (TimetableTask $task) use ($currentTime): bool {
                return $task->status === 'not_started' && $task->start_time > $currentTime;
            })
            ->sort(function (TimetableTask $a, TimetableTask $b): int {
                if ($a->start_time !== $b->start_time) {
                    return $a->start_time <=> $b->start_time;
                }

                if ($a->is_critical !== $b->is_critical) {
                    return $b->is_critical <=> $a->is_critical;
                }

                if ($a->end_time !== $b->end_time) {
                    return $a->end_time <=> $b->end_time;
                }

                return $a->id <=> $b->id;
            })
            ->values();

        $totalNextCount = $allNextTasks->count();
        $tasks = $limit > 0 ? $allNextTasks->take($limit)->values() : $allNextTasks;

        $nextStartTimeLabel = null;
        $remainingTimeLabel = null;

        /** @var TimetableTask|null $firstTask */
        $firstTask = $allNextTasks->first();
        if ($firstTask !== null && ! empty($firstTask->start_time)) {
            $nextStartTimeLabel = substr((string) $firstTask->start_time, 0, 5);
            $remainingTimeLabel = $this->formatRemainingTime($now, (string) $firstTask->start_time);
        }

        return new NextTasksContext(
            isMainEventDay: true,
            activeTimetable: $timetable,
            tasks: $tasks,
            totalNextCount: $totalNextCount,
            nextStartTimeLabel: $nextStartTimeLabel,
            remainingTimeLabel: $remainingTimeLabel,
            actionUrl: route('timetable.index', ['timetable_id' => $timetable->id]),
        );
    }

    /**
     * 現在時刻から次のタスク開始時刻までの残り時間ラベルを算出
     */
    private function formatRemainingTime(Carbon $now, string $startTime): string
    {
        $parts = explode(':', $startTime);
        $hour = (int) ($parts[0] ?? 0);
        $minute = (int) ($parts[1] ?? 0);
        $second = (int) ($parts[2] ?? 0);

        $targetTime = $now->copy()->setTime($hour, $minute, $second);
        $diffSeconds = max(1, (int) $now->diffInSeconds($targetTime, false));
        $diffMinutes = max(1, (int) ceil($diffSeconds / 60));

        if ($diffMinutes < 60) {
            return "あと {$diffMinutes}分後 開始";
        }

        $hours = intdiv($diffMinutes, 60);
        $mins = $diffMinutes % 60;

        if ($mins > 0) {
            return "あと {$hours}時間{$mins}分後 開始";
        }

        return "あと {$hours}時間後 開始";
    }
}

