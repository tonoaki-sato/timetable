<?php

namespace App\Services;

use App\DTOs\GlobalDashboardDTO;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use App\Repositories\Contracts\LocationRepositoryInterface;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Carbon\Carbon;

class GlobalDashboardService
{
    protected StaffRepositoryInterface $staffRepository;

    protected EquipmentRepositoryInterface $equipmentRepository;

    protected LocationRepositoryInterface $locationRepository;

    public function __construct(
        protected ScheduleRepositoryInterface $scheduleRepository,
        protected TimetableRepositoryInterface $timetableRepository,
        protected PhaseService $phaseService,
        ?StaffRepositoryInterface $staffRepository = null,
        ?EquipmentRepositoryInterface $equipmentRepository = null,
        ?LocationRepositoryInterface $locationRepository = null,
    ) {
        $this->staffRepository = $staffRepository ?? app(StaffRepositoryInterface::class);
        $this->equipmentRepository = $equipmentRepository ?? app(EquipmentRepositoryInterface::class);
        $this->locationRepository = $locationRepository ?? app(LocationRepositoryInterface::class);
    }

    /**
     * 本部総合統括ダッシュボード表示用集約データを取得
     */
    public function getOverview(?Carbon $now = null): GlobalDashboardDTO
    {
        $now = $now ?? Carbon::now('Asia/Tokyo');

        // 1. 年間スケジュール進捗集計
        $statusSummary = $this->scheduleRepository->getStatusSummary();
        $totalSchedules = $statusSummary['total'] ?? 0;
        $completedSchedules = $statusSummary['completed'] ?? 0;
        $pendingSchedules = $totalSchedules - $completedSchedules;
        $completionRate = $totalSchedules > 0 ? (int) round(($completedSchedules / $totalSchedules) * 100) : 0;

        // 2. スタッフ体制集計
        $staffTotalCount = $this->staffRepository->getActiveCount();
        $staffLeaderCount = $this->staffRepository->getActiveLeaderCount();

        // 3. 資機材・拠点アセット集計
        $equipmentTotalCount = $this->equipmentRepository->getTotalCount();
        $locationTotalCount = $this->locationRepository->getTotalCount();

        // 4. 運行日程（タイムテーブル）集計 & カウントダウン
        $timetables = $this->timetableRepository->getAll()->sortBy('target_date')->values();
        $timetablesCount = $timetables->count();
        $activeTimetablesCount = $timetables->where('status', 'active')->count();

        $nextTimetable = $timetables->first(function ($t): bool {
            return $t->target_date !== null && ($t->target_date->isToday() || $t->target_date->isFuture());
        }) ?? $timetables->first();

        $daysUntilMainEvent = null;
        $nextEventName = null;
        if ($nextTimetable && $nextTimetable->target_date) {
            $today = $now->copy()->startOfDay();
            $target = $nextTimetable->target_date->copy()->startOfDay();
            $diffDays = (int) $today->diffInDays($target, false);
            $daysUntilMainEvent = max(0, $diffDays);
            $nextEventName = $nextTimetable->name;
        }

        // 5. 直近の会議・打合せ（最大3件）
        $upcomingMeetings = $this->scheduleRepository->getUpcomingMeetings(3);

        // 6. 直近の許認可・申請・協議（最大3件）
        $upcomingPermits = $this->scheduleRepository->getUpcomingPermits(3);

        // 7. 現在の年間フェーズ
        $phaseContext = $this->phaseService->getCurrentContext($now);

        return new GlobalDashboardDTO(
            scheduleTotalCount: $totalSchedules,
            scheduleCompletedCount: $completedSchedules,
            schedulePendingCount: $pendingSchedules,
            scheduleCompletionRate: $completionRate,
            staffTotalCount: $staffTotalCount,
            staffLeaderCount: $staffLeaderCount,
            equipmentTotalCount: $equipmentTotalCount,
            locationTotalCount: $locationTotalCount,
            timetablesCount: $timetablesCount,
            activeTimetablesCount: $activeTimetablesCount,
            upcomingMeetings: $upcomingMeetings,
            upcomingPermits: $upcomingPermits,
            timetables: $timetables,
            daysUntilMainEvent: $daysUntilMainEvent,
            nextEventName: $nextEventName,
            phaseContext: $phaseContext,
        );
    }
}
