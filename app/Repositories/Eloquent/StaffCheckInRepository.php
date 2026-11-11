<?php

namespace App\Repositories\Eloquent;

use App\Models\StaffCheckIn;
use App\Repositories\Contracts\StaffCheckInRepositoryInterface;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Override;

class StaffCheckInRepository implements StaffCheckInRepositoryInterface
{
    #[Override]
    public function findByTimetableAndStaff(int $timetableId, int $staffId): ?StaffCheckIn
    {
        return StaffCheckIn::where('timetable_id', $timetableId)
            ->where('staff_id', $staffId)
            ->first();
    }

    #[Override]
    public function firstOrCreateForTimetableAndStaff(
        int $timetableId,
        int $staffId,
        CarbonInterface $checkedInAt
    ): StaffCheckIn {
        $existing = $this->findByTimetableAndStaff($timetableId, $staffId);
        if ($existing) {
            return $existing;
        }

        try {
            return StaffCheckIn::create([
                'timetable_id' => $timetableId,
                'staff_id' => $staffId,
                'checked_in_at' => $checkedInAt,
            ]);
        } catch (QueryException $e) {
            $concurrent = $this->findByTimetableAndStaff($timetableId, $staffId);
            if ($concurrent) {
                return $concurrent;
            }

            throw $e;
        }
    }

    #[Override]
    public function getStaffIdsCheckedInForTimetable(int $timetableId): array
    {
        return StaffCheckIn::where('timetable_id', $timetableId)
            ->pluck('staff_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    #[Override]
    public function getMapByTimetable(int $timetableId, array $staffIds = []): array
    {
        $query = StaffCheckIn::where('timetable_id', $timetableId);

        if (! empty($staffIds)) {
            $query->whereIn('staff_id', $staffIds);
        }

        return $query->get()->keyBy('staff_id')->all();
    }
}
