<?php

namespace App\DTOs;

use App\Models\Schedule;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Collection;

/**
 * 本部総合統括ダッシュボード（/dashboard）表示用 集約DTO
 */
readonly class GlobalDashboardDTO
{
    /**
     * @param  Collection<int, Schedule>  $upcomingMeetings
     * @param  Collection<int, Schedule>  $upcomingPermits
     * @param  Collection<int, Timetable>  $timetables
     */
    public function __construct(
        public int $scheduleTotalCount,
        public int $scheduleCompletedCount,
        public int $schedulePendingCount,
        public int $scheduleCompletionRate,
        public int $staffTotalCount,
        public int $staffLeaderCount,
        public int $equipmentTotalCount,
        public int $locationTotalCount,
        public int $timetablesCount,
        public int $activeTimetablesCount,
        public Collection $upcomingMeetings,
        public Collection $upcomingPermits,
        public Collection $timetables,
        public ?int $daysUntilMainEvent,
        public ?string $nextEventName,
        public PhaseContext $phaseContext,
    ) {}
}
