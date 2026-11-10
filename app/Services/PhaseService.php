<?php

namespace App\Services;

use App\DTOs\PhaseContext;
use App\Enums\AnnualPhase;
use App\Models\Schedule;
use App\Models\Timetable;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Carbon\Carbon;

class PhaseService
{
    protected TimetableRepositoryInterface $timetableRepository;

    protected ScheduleRepositoryInterface $scheduleRepository;

    public function __construct(
        ?TimetableRepositoryInterface $timetableRepository = null,
        ?ScheduleRepositoryInterface $scheduleRepository = null
    ) {
        $this->timetableRepository = $timetableRepository ?? app(TimetableRepositoryInterface::class);
        $this->scheduleRepository = $scheduleRepository ?? app(ScheduleRepositoryInterface::class);
    }

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
        return $this->timetableRepository->findForDatePrioritizingActive($now->toDateString());
    }

    /**
     * 直近の未完了タスク・予定を取得
     */
    private function getNextUpcomingTask(string $todayStr): ?Schedule
    {
        return $this->scheduleRepository->findNextUpcomingTask($todayStr);
    }

    /**
     * 該当フェーズの対象月における未完了タスク数を集計
     */
    private function countPendingTasksInPhase(AnnualPhase $phase, Carbon $now): int
    {
        return $this->scheduleRepository->countPendingInMonthsAndYear($phase->months(), $now->year);
    }
}
