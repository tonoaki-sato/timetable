<?php

namespace App\Repositories\Eloquent;

use App\Models\LayoutArea;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Repositories\Contracts\LayoutAreaRepositoryInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Override;

class LayoutAreaRepository implements LayoutAreaRepositoryInterface
{
    #[Override]
    public function getAllWithCountsAndLocation(): Collection
    {
        return LayoutArea::query()
            ->with(['location.type'])
            ->withCount(['items'])
            ->addSelect([
                'layout_maps_count' => LayoutItem::query()
                    ->selectRaw('COUNT(DISTINCT layout_map_id)')
                    ->whereColumn('layout_items.layout_area_id', 'layout_areas.id'),
            ])
            ->ordered()
            ->get();
    }

    #[Override]
    public function getActiveOrdered(): Collection
    {
        return LayoutArea::active()->ordered()->get();
    }

    #[Override]
    public function getForLayoutMap(LayoutMap $map, bool $withScopedItems = false): Collection
    {
        $query = LayoutArea::query()
            ->where(function (Builder $q) use ($map): void {
                $q->where('is_active', true)
                    ->orWhereExists(function ($sub) use ($map): void {
                        $sub->selectRaw('1')
                            ->from('layout_items')
                            ->whereColumn('layout_items.layout_area_id', 'layout_areas.id')
                            ->where('layout_items.layout_map_id', $map->id);
                    });
            })
            ->ordered();

        if ($withScopedItems) {
            $query->with([
                'location',
                'items' => function ($q) use ($map): void {
                    $q->where('layout_map_id', $map->id)
                        ->with('vendor')
                        ->orderBy('display_order', 'asc')
                        ->orderBy('code', 'asc');
                },
            ]);
        }

        return $query->get();
    }

    #[Override]
    public function findById(int $id): ?LayoutArea
    {
        return LayoutArea::find($id);
    }

    #[Override]
    public function getMaxDisplayOrder(): int
    {
        return (int) (LayoutArea::max('display_order') ?? 0);
    }

    #[Override]
    public function create(array $attributes): LayoutArea
    {
        return LayoutArea::create($attributes)->fresh(['location.type']);
    }

    #[Override]
    public function update(LayoutArea $layoutArea, array $attributes): LayoutArea
    {
        $layoutArea->update($attributes);

        return $layoutArea->fresh(['location.type']);
    }

    #[Override]
    public function delete(LayoutArea $layoutArea): bool
    {
        return (bool) $layoutArea->delete();
    }

    #[Override]
    public function countItems(LayoutArea $layoutArea): int
    {
        return $layoutArea->items()->count();
    }

    #[Override]
    public function reorder(array $layoutAreaIds): void
    {
        $cleanIds = array_values(array_unique(array_map('intval', $layoutAreaIds)));
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
            LayoutArea::whereIn('id', $cleanIds)->update([
                'display_order' => DB::raw($caseSql),
            ]);
        });
    }
}
