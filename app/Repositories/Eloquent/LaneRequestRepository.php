<?php

namespace App\Repositories\Eloquent;

use App\Models\LaneRequest;
use App\Repositories\Contracts\LaneRequestRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Override;

class LaneRequestRepository implements LaneRequestRepositoryInterface
{
    #[Override]
    public function getPendingForLanesOrTasks(array $laneIds, array $taskIds, int $limit = 6): Collection
    {
        if (empty($laneIds) && empty($taskIds)) {
            return new Collection;
        }

        return LaneRequest::with(['lane', 'task', 'requestedByStaff'])
            ->where(function ($query) use ($laneIds, $taskIds): void {
                if (! empty($laneIds)) {
                    $query->whereIn('lane_id', $laneIds);
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
        return LaneRequest::where('timetable_id', $timetableId)->delete();
    }
}
