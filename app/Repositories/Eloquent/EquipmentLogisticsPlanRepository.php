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
            ->with(['equipment.lane', 'fromLocationModel', 'toLocationModel', 'witnessStaff'])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function getEquipmentsFlowData(?string $type = null, ?int $laneId = null, ?Timetable $timetable = null): Collection
    {
        $query = Equipment::with([
            'lane',
            'plans' => function ($q) use ($timetable) {
                if ($timetable) {
                    $q->where('timetable_id', $timetable->id);
                }
                $q->with([
                    'equipment',
                    'fromLocationModel.schedules',
                    'toLocationModel.schedules',
                    'witnessStaff',
                ])->orderBy('display_order')->orderBy('id');
            },
        ])->ordered();

        if ($type) {
            $query->where('type', $type);
        }

        if ($laneId) {
            $query->where('lane_id', $laneId);
        }

        return $query->get();
    }

    #[Override]
    public function getPhaseChecklistData(?string $dayPhase = null, ?int $laneId = null, ?string $type = null, ?Timetable $timetable = null): Collection
    {
        $query = EquipmentLogisticsPlan::with([
            'equipment.lane',
            'fromLocationModel.schedules',
            'toLocationModel.schedules',
            'witnessStaff',
        ])
            ->orderBy('target_date', 'asc')
            ->orderBy('start_time', 'asc')
            ->orderBy('display_order', 'asc');

        if ($timetable) {
            $query->where('timetable_id', $timetable->id);
        }

        if ($dayPhase) {
            $query->where('day_phase', $dayPhase);
        }

        if ($laneId) {
            $query->whereHas('equipment', fn ($q) => $q->where('lane_id', $laneId));
        }

        if ($type) {
            $query->whereHas('equipment', fn ($q) => $q->where('type', $type));
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
        return $plan->delete();
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

