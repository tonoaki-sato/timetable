<?php

namespace App\Repositories\Eloquent;

use App\DTOs\EquipmentFilterDTO;
use App\Models\Equipment;
use App\Models\Timetable;
use App\Repositories\Contracts\EquipmentRepositoryInterface;
use App\Support\TableQueryOptions;
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
                'plans' => function ($q) use ($timetable): void {
                    if ($timetable) {
                        $q->where('timetable_id', $timetable->id);
                    }
                },
            ])
            ->when($filter->type, fn ($q) => $q->where('type', $filter->type))
            ->when($filter->checkoutRule, fn ($q) => $q->where('checkout_rule', $filter->checkoutRule))
            ->when($filter->keyword, fn ($q) => $q->where(function ($sub) use ($filter): void {
                $sub->where('name', 'like', "%{$filter->keyword}%")
                    ->orWhere('storage_location', 'like', "%{$filter->keyword}%")
                    ->orWhere('description', 'like', "%{$filter->keyword}%");
            }));

        $resolvedSort = TableQueryOptions::resolveSort(
            $filter->sort,
            $filter->direction,
            ['display_order', 'name', 'type', 'checkout_rule', 'quantity', 'storage_location'],
            'display_order',
            'asc',
        );

        if ($resolvedSort['sort'] === 'display_order' && $resolvedSort['direction'] === 'asc') {
            $query->ordered();
        } else {
            $query->orderBy($resolvedSort['sort'], $resolvedSort['direction'])->orderBy('id', 'asc');
        }

        $effectivePerPage = $filter->perPage !== 20
            ? TableQueryOptions::resolvePerPage($filter->perPage, 20, [20, 50, 100])
            : $perPage;

        return $query->paginate($effectivePerPage)->withQueryString();
    }

    #[Override]
    public function getCountsByType(): array
    {
        $counts = Equipment::query()
            ->selectRaw('type, COUNT(*) as aggregate_count')
            ->groupBy('type')
            ->pluck('aggregate_count', 'type');

        return [
            'total' => (int) $counts->sum(),
            'committee' => (int) ($counts['committee'] ?? 0),
            'rental' => (int) ($counts['rental'] ?? 0),
            'personal' => (int) ($counts['personal'] ?? 0),
        ];
    }

    #[Override]
    public function getTotalCount(): int
    {
        return Equipment::count();
    }

    #[Override]
    public function findWithDetails(int $id, ?Timetable $timetable = null): ?Equipment
    {
        return Equipment::find($id);
    }

    #[Override]
    public function loadPlansForTimetable(Equipment $equipment, Timetable $timetable): Equipment
    {
        return $equipment->load([
            'creator',
            'plans' => fn ($q) => $q->where('timetable_id', $timetable->id)->with([
                'equipment',
                'fromLocationModel.schedules',
                'toLocationModel.schedules',
                'witnessStaff',
            ])->ordered(),
        ]);
    }

    #[Override]
    public function getAllOrdered(): Collection
    {
        return Equipment::ordered()->get();
    }

    #[Override]
    public function searchQuick(string $keyword, int $limit = 5): Collection
    {
        return Equipment::query()
            ->where(function ($q) use ($keyword): void {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('storage_location', 'like', "%{$keyword}%")
                    ->orWhere('description', 'like', "%{$keyword}%");
            })
            ->ordered()
            ->limit($limit)
            ->get();
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
        return (bool) $equipment->delete();
    }

    #[Override]
    public function reorder(array $equipmentIds): void
    {
        $cleanIds = array_values(array_unique(array_map('intval', $equipmentIds)));
        if ($cleanIds === []) {
            return;
        }

        $cases = [];
        $order = 10;
        foreach ($cleanIds as $id) {
            $cases[] = "WHEN {$id} THEN {$order}";
            $order += 10;
        }
        $caseSql = 'CASE id '.implode(' ', $cases).' ELSE display_order END';

        DB::transaction(function () use ($cleanIds, $caseSql): void {
            Equipment::whereIn('id', $cleanIds)->update([
                'display_order' => DB::raw($caseSql),
            ]);
        });
    }

    #[Override]
    public function getMaxDisplayOrder(): int
    {
        return (int) (Equipment::max('display_order') ?? 0);
    }
}
