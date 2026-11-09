<?php

namespace App\Repositories\Eloquent;

use App\DTOs\EquipmentFilterDTO;
use App\Models\Equipment;
use App\Models\Timetable;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Override;

class EquipmentRepository implements EquipmentRepositoryInterface
{
    #[Override]
    public function getPaginated(EquipmentFilterDTO $filter, ?Timetable $timetable = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Equipment::query()
            ->with([
                'lane',
                'plans' => function ($q) use ($timetable) {
                    if ($timetable) {
                        $q->where('timetable_id', $timetable->id);
                    }
                },
            ])
            ->when($filter->type, fn ($q) => $q->where('type', $filter->type))
            ->when($filter->laneId, fn ($q) => $q->where('lane_id', $filter->laneId))
            ->when($filter->checkoutRule, fn ($q) => $q->where('checkout_rule', $filter->checkoutRule))
            ->when($filter->keyword, fn ($q) => $q->where(function ($sub) use ($filter) {
                $sub->where('name', 'like', "%{$filter->keyword}%")
                    ->orWhere('storage_location', 'like', "%{$filter->keyword}%")
                    ->orWhere('storage_detail', 'like', "%{$filter->keyword}%")
                    ->orWhere('description', 'like', "%{$filter->keyword}%");
            }))
            ->ordered();

        return $query->paginate($perPage)->withQueryString();
    }

    #[Override]
    public function getCountsByType(): array
    {
        return [
            'total' => Equipment::count(),
            'committee' => Equipment::where('type', 'committee')->count(),
            'rental' => Equipment::where('type', 'rental')->count(),
            'personal' => Equipment::where('type', 'personal')->count(),
        ];
    }

    #[Override]
    public function findWithDetails(int $id, ?Timetable $timetable = null): ?Equipment
    {
        return Equipment::with([
            'lane',
            'creator',
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
        ])->find($id);
    }

    #[Override]
    public function getAllOrdered(): Collection
    {
        return Equipment::ordered()->get();
    }

    #[Override]
    public function create(array $attributes): Equipment
    {
        return Equipment::create($attributes);
    }

    #[Override]
    public function update(Equipment $equipment, array $attributes): bool
    {
        return $equipment->update($attributes);
    }

    #[Override]
    public function delete(Equipment $equipment): bool
    {
        return $equipment->delete();
    }

    #[Override]
    public function reorder(array $equipmentIds): void
    {
        DB::transaction(function () use ($equipmentIds): void {
            $order = 10;
            foreach ($equipmentIds as $equipmentId) {
                Equipment::where('id', $equipmentId)->update([
                    'display_order' => $order,
                ]);
                $order += 10;
            }
        });
    }

    #[Override]
    public function getMaxDisplayOrder(): int
    {
        return (int) (Equipment::max('display_order') ?? 0);
    }
}
