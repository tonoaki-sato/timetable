<?php

namespace App\Repositories\Eloquent;

use App\Models\LaneRequest;
use App\Repositories\Contracts\LaneRequestRepositoryInterface;
use App\Support\NavigationCache;
use Illuminate\Database\Eloquent\Collection;
use Override;

class LaneRequestRepository implements LaneRequestRepositoryInterface
{
    #[Override]
    public function getPendingForLanesOrTasks(array $timetableLaneIds, array $taskIds, int $limit = 6): Collection
    {
        if (empty($timetableLaneIds) && empty($taskIds)) {
            return new Collection;
        }

        return LaneRequest::with(['timetableLane.lane', 'task', 'requestedByStaff'])
            ->where(function ($query) use ($timetableLaneIds, $taskIds): void {
                if (! empty($timetableLaneIds)) {
                    $query->whereIn('timetable_lane_id', $timetableLaneIds);
                }
                if (! empty($taskIds)) {
                    $query->orWhereIn('timetable_task_id', $taskIds);
                }
            })
            ->whereIn('status', ['pending', 'approved'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    #[Override]
    public function create(array $attributes): LaneRequest
    {
        return LaneRequest::create($attributes);
    }

    #[Override]
    public function update(LaneRequest $laneRequest, array $attributes): bool
    {
        return $laneRequest->update($attributes);
    }

    #[Override]
    public function deleteByTimetableId(int $timetableId): int
    {
        $deleted = LaneRequest::where('timetable_id', $timetableId)->delete();
        app(NavigationCache::class)->forgetPendingLaneRequests();

        return $deleted;
    }

    #[Override]
    public function countPendingGlobal(): int
    {
        return LaneRequest::whereIn('status', ['pending', 'approved'])->count();
    }

    #[Override]
    public function getRecentPendingGlobal(int $limit = 8): Collection
    {
        return LaneRequest::with(['timetable', 'timetableLane.lane', 'task', 'requestedByStaff'])
            ->whereIn('status', ['pending', 'approved'])
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }
}
