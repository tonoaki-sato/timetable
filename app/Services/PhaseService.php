<?php

namespace App\Services;

use App\DTOs\PhaseContext;
use App\Enums\AnnualPhase;
use App\Models\Schedule;
use App\Models\Timetable;
use Carbon\Carbon;

class PhaseService
{
    /**
     * 現在（または指定日時）のフェーズ状況を一括取得
     */
    public function getCurrentContext(?Carbon $now = null): PhaseContext
    {
        $now = $now ?? Carbon::now('Asia/Tokyo');

        // 1. 本番運行日（実行フェーズ）の判定
        $timetable = $this->getTodaysTimetable($now);
        if ($timetable !== null) {
            return new PhaseContext(
                phase: AnnualPhase::Execution,
                isMainEventDay: true,
                activeTimetable: $timetable,
                nextTask: null,
                pendingTaskCount: 0,
                actionUrl: route('timetable.index', ['timetable_id' => $timetable->id])
            );
        }

        // 2. 平時（準備・計画フェーズ）の各データを取得
        $phase = AnnualPhase::fromMonth($now->month);
        $nextTask = $this->getNextUpcomingTask($now->toDateString());
        $pendingCount = $this->countPendingTasksInPhase($phase, $now);

        return new PhaseContext(
            phase: $phase,
            isMainEventDay: false,
            activeTimetable: null,
            nextTask: $nextTask,
            pendingTaskCount: $pendingCount,
            actionUrl: null
        );
    }

    /**
     * 本日開催の運行タイムテーブルを取得
     */
    private function getTodaysTimetable(Carbon $now): ?Timetable
    {
        return Timetable::whereDate('target_date', $now->toDateString())
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderBy('id')
            ->first();
    }

    /**
     * 直近の未完了タスク・予定を取得
     */
    private function getNextUpcomingTask(string $todayStr): ?Schedule
    {
        return Schedule::where('start_date', '>=', $todayStr)
            ->where('status', '!=', 'completed')
            ->orderBy('start_date', 'asc')
            ->first();
    }

    /**
     * 該当フェーズの対象月における未完了タスク数を集計
     */
    private function countPendingTasksInPhase(AnnualPhase $phase, Carbon $now): int
    {
        $phaseMonths = $phase->months();

        return Schedule::where(function ($query) use ($phaseMonths) {
            foreach ($phaseMonths as $m) {
                $query->orWhereMonth('start_date', $m);
            }
        })
            ->whereYear('start_date', $now->year)
            ->where('status', '!=', 'completed')
            ->count();
    }
}
