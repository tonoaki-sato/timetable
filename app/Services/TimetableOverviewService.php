<?php

namespace App\Services;

use App\DTOs\TimetableOverviewDTO;
use App\Models\LaneRequest;
use App\Models\Timetable;
use Carbon\Carbon;

class TimetableOverviewService
{
    /**
     * 運行計画に紐づく全体集計コンテキストを生成
     */
    public function getOverview(Timetable $timetable, ?Carbon $now = null): TimetableOverviewDTO
    {
        $now = $now ?? Carbon::now('Asia/Tokyo');

        // 1. 運行時間帯と所要時間
        $startStr = (string) $timetable->start_time;
        $endStr = (string) $timetable->end_time;
        $startFormatted = substr($startStr, 0, 5);
        $endFormatted = substr($endStr, 0, 5);

        $durationLabel = '0時間';
        try {
            $startCarbon = Carbon::createFromFormat('H:i:s', strlen($startStr) === 5 ? $startStr.':00' : $startStr);
            $endCarbon = Carbon::createFromFormat('H:i:s', strlen($endStr) === 5 ? $endStr.':00' : $endStr);
            if ($startCarbon && $endCarbon) {
                $diffMinutes = $startCarbon->diffInMinutes($endCarbon);
                $hours = floor($diffMinutes / 60);
                $mins = $diffMinutes % 60;
                $durationLabel = $hours.'時間'.($mins > 0 ? " {$mins}分" : '');
            }
        } catch (\Throwable) {
            $durationLabel = '未定';
        }

        // 曜日判定
        $weekdayNames = ['日', '月', '火', '水', '木', '金', '土'];
        $weekdayLabel = $timetable->target_date ? $weekdayNames[$timetable->target_date->dayOfWeek] : '';
        $isWeekend = in_array($timetable->target_date?->dayOfWeek, [0, 6], true);

        // 2. タスク集計
        $tasks = $timetable->tasks;
        $totalTasks = $tasks->count();
        $completedTasks = $tasks->where('status', 'completed')->count();
        $inProgressTasks = $tasks->where('status', 'in_progress')->count();
        $notStartedTasks = $tasks->where('status', 'not_started')->count();
        $satisfiedTasks = $tasks->filter(fn ($t): bool => (bool) $t->is_satisfied)->count();
        $shortageTasks = $totalTasks - $satisfiedTasks;
        $totalRequiredPeople = (int) $tasks->sum('required_people_count');
        $totalAssignedPeople = (int) $tasks->sum(fn ($t): int => (int) $t->assigned_count);
        $taskCompletionRate = $totalTasks > 0 ? (int) round(($completedTasks / $totalTasks) * 100) : 0;
        $fulfillmentRate = $totalRequiredPeople > 0
            ? (int) min(100, round(($totalAssignedPeople / $totalRequiredPeople) * 100))
            : ($totalTasks > 0 ? 100 : 0);

        $criticalTasksCollection = $tasks->filter(fn ($t): bool => (bool) $t->is_critical)->values();
        $criticalTasksCount = $criticalTasksCollection->count();

        // 3. 担当班編成集計
        $lanes = $timetable->lanes;
        $lanesCount = $lanes->count();
        $leadConfiguredCount = $lanes->filter(fn ($l): bool => $l->lead_staff_id !== null)->count();
        $fixedStaffCount = (int) $lanes->sum(fn ($l): int => $l->relationLoaded('fixedStaff') ? $l->fixedStaff->count() : 0);

        // 4. 資材搬出入・物流配備集計
        $logisticsPlans = $timetable->logisticsPlans;
        $uniqueEquipmentCount = $logisticsPlans->pluck('equipment_id')->unique()->filter()->count();
        $totalLogisticsPlans = $logisticsPlans->count();
        $completedLogisticsPlans = $logisticsPlans->where('status', 'completed')->count();
        $inTransitLogisticsPlans = $logisticsPlans->where('status', 'in_transit')->count();
        $logisticsProgressPercent = $totalLogisticsPlans > 0
            ? (int) round(($completedLogisticsPlans / $totalLogisticsPlans) * 100)
            : 0;
        $witnessRequiredPlans = $logisticsPlans->where('witness_required', true)->count();

        // 5. 道路規制 ＆ 現場要請（当計画に紐づくもの）
        $roadPeriods = $timetable->road_periods ?? [];
        $festivalPeriods = $timetable->festival_periods ?? [];
        $roadPeriodsCount = count($roadPeriods);
        $festivalPeriodsCount = count($festivalPeriods);

        // 現在通行止め中か判定
        $currentTimeStr = $now->format('H:i');
        $hasActiveRoadClosure = false;
        foreach ($roadPeriods as $rp) {
            $start = substr((string) ($rp['start_time'] ?? ''), 0, 5);
            $end = substr((string) ($rp['end_time'] ?? ''), 0, 5);
            if (($rp['status'] ?? '') === 'closed' && $start <= $currentTimeStr && $currentTimeStr <= $end) {
                $hasActiveRoadClosure = true;
                break;
            }
        }

        // 当該運行計画のレーンまたはタスクに紐づく未完了要請
        $laneIds = $lanes->pluck('id')->filter()->all();
        $taskIds = $tasks->pluck('id')->filter()->all();
        $pendingLaneRequests = [];
        if (! empty($laneIds) || ! empty($taskIds)) {
            $pendingLaneRequests = LaneRequest::with(['lane', 'task', 'requestedByStaff'])
                ->where(function ($query) use ($laneIds, $taskIds) {
                    if (! empty($laneIds)) {
                        $query->whereIn('lane_id', $laneIds);
                    }
                    if (! empty($taskIds)) {
                        $query->orWhereIn('timetable_task_id', $taskIds);
                    }
                })
                ->whereIn('status', ['pending', 'approved'])
                ->orderByDesc('id')
                ->limit(6)
                ->get()
                ->all();
        }

        return new TimetableOverviewDTO(
            startFormatted: $startFormatted,
            endFormatted: $endFormatted,
            durationLabel: $durationLabel,
            weekdayLabel: $weekdayLabel,
            isWeekend: $isWeekend,
            totalTasks: $totalTasks,
            completedTasks: $completedTasks,
            inProgressTasks: $inProgressTasks,
            notStartedTasks: $notStartedTasks,
            satisfiedTasks: $satisfiedTasks,
            shortageTasks: $shortageTasks,
            totalRequiredPeople: $totalRequiredPeople,
            totalAssignedPeople: $totalAssignedPeople,
            taskCompletionRate: $taskCompletionRate,
            fulfillmentRate: $fulfillmentRate,
            criticalTasksCount: $criticalTasksCount,
            criticalTasks: $criticalTasksCollection->all(),
            lanesCount: $lanesCount,
            leadConfiguredCount: $leadConfiguredCount,
            fixedStaffCount: $fixedStaffCount,
            uniqueEquipmentCount: $uniqueEquipmentCount,
            totalLogisticsPlans: $totalLogisticsPlans,
            completedLogisticsPlans: $completedLogisticsPlans,
            inTransitLogisticsPlans: $inTransitLogisticsPlans,
            logisticsProgressPercent: $logisticsProgressPercent,
            witnessRequiredPlans: $witnessRequiredPlans,
            roadPeriodsCount: $roadPeriodsCount,
            festivalPeriodsCount: $festivalPeriodsCount,
            pendingLaneRequestsCount: count($pendingLaneRequests),
            pendingLaneRequests: $pendingLaneRequests,
            hasActiveRoadClosure: $hasActiveRoadClosure,
        );
    }
}
