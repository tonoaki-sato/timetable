<?php

namespace App\Repositories\Eloquent;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Timetable;
use App\Repositories\Contracts\EquipmentLogisticsPlanRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Override;

class EquipmentLogisticsPlanRepository implements EquipmentLogisticsPlanRepositoryInterface
{
    #[Override]
    public function getForTimetable(Timetable $timetable): Collection
    {
        return $timetable->logisticsPlans()
            ->with(['equipment', 'fromLocationModel', 'toLocationModel', 'witnessStaff'])
            ->ordered()
            ->get();
    }

    #[Override]
    public function getEquipmentsFlowData(?string $type = null, ?Timetable $timetable = null, ?array $equipmentIds = null): Collection
    {
        if ($equipmentIds === []) {
            return new Collection;
        }

        $query = Equipment::with([
            'plans' => function ($q) use ($timetable): void {
                if ($timetable) {
                    $q->where('timetable_id', $timetable->id);
                }
                $q->with([
                    'equipment',
                    'fromLocationModel.schedules',
                    'toLocationModel.schedules',
                    'witnessStaff',
                ])
                    ->ordered();
            },
        ])->ordered();

        if ($type) {
            $query->where('type', $type);
        }

        if ($equipmentIds !== null) {
            $query->whereIn('id', $equipmentIds);
        }

        return $query->get();
    }

    #[Override]
    public function getByToLocationIdsWithEquipment(array $locationIds, ?Timetable $timetable = null): Collection
    {
        if (empty($locationIds)) {
            return new Collection;
        }

        $query = EquipmentLogisticsPlan::with(['equipment'])
            ->whereIn('to_location_id', $locationIds)
            ->ordered();

        if ($timetable) {
            $query->where('timetable_id', $timetable->id);
        }

        return $query->get();
    }

    #[Override]
    public function create(array $attributes): EquipmentLogisticsPlan
    {
        return EquipmentLogisticsPlan::create($attributes);
    }

    #[Override]
    public function update(EquipmentLogisticsPlan $plan, array $attributes): bool
    {
        return $plan->update($attributes);
    }

    #[Override]
    public function delete(EquipmentLogisticsPlan $plan): bool
    {
        return (bool) $plan->delete();
    }

    #[Override]
    public function clearForEquipment(Equipment $equipment, Timetable $timetable): int
    {
        $query = $equipment->plans()->where('timetable_id', $timetable->id);
        $count = $query->count();
        $query->delete();

        return $count;
    }
}
