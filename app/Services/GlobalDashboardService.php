<?php

namespace App\Services;

use App\DTOs\GlobalDashboardDTO;
use App\Models\Equipment;
use App\Models\Lane;
use App\Models\Location;
use App\Models\Schedule;
use App\Models\Staff;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Carbon\Carbon;

class GlobalDashboardService
{
    public function __construct(
        protected ScheduleRepositoryInterface $scheduleRepository,
        protected TimetableRepositoryInterface $timetableRepository,
        protected PhaseService $phaseService,
    ) {}

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
        $staffTotalCount = Staff::where('is_active', true)->count();
        $staffLeaderCount = Staff::where('is_active', true)
            ->where(function ($q) {
                $q->where('staff_type', 'fixed')
                    ->orWhereIn('id', Lane::whereNotNull('lead_staff_id')->pluck('lead_staff_id'));
            })
            ->distinct()
            ->count('id');

        // 3. 資機材・拠点アセット集計
        $equipmentTotalCount = Equipment::count();
        $locationTotalCount = Location::count();

        // 4. 運行日程（タイムテーブル）集計 & カウントダウン
        $timetables = $this->timetableRepository->getAll()->sortBy('target_date')->values();
        $timetablesCount = $timetables->count();
        $activeTimetablesCount = $timetables->where('status', 'active')->count();

        $nextTimetable = $timetables->first(function ($t) {
            return $t->target_date && ($t->target_date->isToday() || $t->target_date->isFuture());
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
        $upcomingMeetings = Schedule::meetings()
            ->upcoming()
            ->with(['assignee'])
            ->limit(3)
            ->get();

        // 6. 直近の許認可・申請・協議（最大3件）
        $upcomingPermits = Schedule::whereIn('category', ['permit', 'briefing'])
            ->upcoming()
            ->with(['assignee'])
            ->limit(3)
            ->get();

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
