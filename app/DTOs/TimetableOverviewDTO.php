<?php

namespace App\DTOs;

readonly class TimetableOverviewDTO
{
    /**
     * @param  array<int, mixed>  $criticalTasks
     * @param  array<int, mixed>  $pendingLaneRequests
     */
    public function __construct(
        // 運行時間関連
        public string $startFormatted,
        public string $endFormatted,
        public string $durationLabel,
        public string $weekdayLabel,
        public bool $isWeekend,

        // タスク進捗
        public int $totalTasks,
        public int $completedTasks,
        public int $inProgressTasks,
        public int $notStartedTasks,
        public int $satisfiedTasks,
        public int $shortageTasks,
        public int $totalRequiredPeople,
        public int $totalAssignedPeople,
        public int $taskCompletionRate,
        public int $fulfillmentRate,
        public int $criticalTasksCount,
        public array $criticalTasks,

        // 担当班編成
        public int $lanesCount,
        public int $leadConfiguredCount,
        public int $fixedStaffCount,

        // 資材搬出入・物流配備
        public int $uniqueEquipmentCount,
        public int $totalLogisticsPlans,
        public int $completedLogisticsPlans,
        public int $inTransitLogisticsPlans,
        public int $logisticsProgressPercent,
        public int $witnessRequiredPlans,

        // 道路規制・現場要請
        public int $roadPeriodsCount,
        public int $festivalPeriodsCount,
        public int $pendingLaneRequestsCount,
        public array $pendingLaneRequests,
        public bool $hasActiveRoadClosure,
    ) {}
}
