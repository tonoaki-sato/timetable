<?php

namespace App\Services;

use App\DTOs\PhaseContext;
use App\Enums\AnnualPhase;
use App\Models\AnnualSchedule;
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
                displayName: $timetable->name,
                subStatus: '本番運行セッション進行中',
                isMainEventDay: true,
                activeTimetable: $timetable,
                nextTask: null,
                pendingTaskCount: 0,
                actionUrl: route('timetable.index')
            );
        }

        // 2. 平時（年間準備フェーズ）の各要素をフラットに取得
        $targetYear = $now->year;
        $phase = AnnualPhase::fromMonth($now->month);
        $nextTask = $this->getNextUpcomingTask($targetYear, $now->toDateString());
        $subStatus = $this->formatUpcomingTaskSubStatus($nextTask, $now);
        $pendingCount = $this->countPendingTasksInPhase($phase, $targetYear);

        return new PhaseContext(
            phase: $phase,
            displayName: $phase->label(),
            subStatus: $subStatus,
            isMainEventDay: false,
            activeTimetable: null,
            nextTask: $nextTask,
            pendingTaskCount: $pendingCount,
            actionUrl: route('annual-schedules.index', ['year' => $targetYear])
        );
    }

    /**
     * 本日開催の運行タイムテーブルを取得
     */
    private function getTodaysTimetable(Carbon $now): ?Timetable
    {
        return Timetable::whereDate('target_date', $now->toDateString())->first();
    }

    /**
     * 該当年または直近の未完了年間タスクを取得
     */
    private function getNextUpcomingTask(int $targetYear, string $todayStr): ?AnnualSchedule
    {
        return AnnualSchedule::where('target_year', $targetYear)
            ->where('start_date', '>=', $todayStr)
            ->where('status', '!=', 'completed')
            ->orderBy('start_date', 'asc')
            ->first();
    }

    /**
     * 直近タスク情報と現在日時の差分からサブステータス文字列を構築
     */
    private function formatUpcomingTaskSubStatus(?AnnualSchedule $nextTask, Carbon $now): string
    {
        if ($nextTask === null || $nextTask->start_date === null) {
            return '直近の年間行事予定なし';
        }

        $dateStr = $nextTask->start_date->format('n/j');
        $daysDiff = (int) $now->startOfDay()->diffInDays($nextTask->start_date->startOfDay(), false);

        if ($daysDiff === 0) {
            return "本日: {$nextTask->title}";
        }

        if ($daysDiff > 0) {
            return "直近: {$dateStr} {$nextTask->title} (あと{$daysDiff}日)";
        }

        return "直近: {$dateStr} {$nextTask->title}";
    }

    /**
     * 該当フェーズの対象月における未完了タスク数を集計
     */
    private function countPendingTasksInPhase(AnnualPhase $phase, int $targetYear): int
    {
        $phaseMonths = $phase->months();

        return AnnualSchedule::where('target_year', $targetYear)
            ->where(function ($query) use ($phaseMonths) {
                foreach ($phaseMonths as $m) {
                    $query->orWhereMonth('start_date', $m);
                }
            })
            ->where('status', '!=', 'completed')
            ->count();
    }
}
