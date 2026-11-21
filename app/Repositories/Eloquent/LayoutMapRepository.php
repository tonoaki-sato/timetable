<?php

namespace App\Repositories\Eloquent;

use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Timetable;
use App\Repositories\Contracts\LayoutMapRepositoryInterface;
use App\Support\TableQueryOptions;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Override;

class LayoutMapRepository implements LayoutMapRepositoryInterface
{
    /**
     * タイムテーブルに紐づく現場配置図面を取得（なければ作成）
     */
    #[Override]
    public function getOrCreateForTimetable(Timetable $timetable): LayoutMap
    {
        $map = LayoutMap::where('timetable_id', $timetable->id)->first();
        if ($map) {
            return $map;
        }

        return LayoutMap::create([
            'timetable_id' => $timetable->id,
            'name' => 'メイン通り商店街配置図',
            'version_tag' => '初版',
            'notes' => '保土ヶ谷宿場まつり 現場配置計画',
            'created_by' => $timetable->created_by,
        ]);
    }

    /**
     * 図面詳細を取得
     */
    #[Override]
    public function findWithDetails(int $id): ?LayoutMap
    {
        return LayoutMap::with(['timetable'])->find($id);
    }

    #[Override]
    public function getSummaryAggregates(int $mapId): array
    {
        $categoryRows = LayoutItem::where('layout_map_id', $mapId)
            ->selectRaw('item_type, category, COUNT(*) as aggregate_count')
            ->groupBy('item_type', 'category')
            ->get();

        $areaTypeRows = LayoutItem::where('layout_map_id', $mapId)
            ->selectRaw('layout_area_id, booth_type, COUNT(*) as aggregate_count')
            ->groupBy('layout_area_id', 'booth_type')
            ->get();

        $totalBooths = 0;
        $totalFacilities = 0;
        $totalItems = 0;
        $boothCategories = [];
        $facilityCategories = [];

        foreach ($categoryRows as $row) {
            $typeValue = $row->item_type instanceof \BackedEnum ? $row->item_type->value : (string) $row->item_type;
            $cnt = (int) $row->aggregate_count;
            $cat = $row->category !== null ? (string) $row->category : '';
            $totalItems += $cnt;

            if ($typeValue === 'booth') {
                $totalBooths += $cnt;
                if ($cat !== '') {
                    $boothCategories[$cat] = ($boothCategories[$cat] ?? 0) + $cnt;
                }
            } elseif ($typeValue === 'facility') {
                $totalFacilities += $cnt;
                if ($cat !== '') {
                    $facilityCategories[$cat] = ($facilityCategories[$cat] ?? 0) + $cnt;
                }
            }
        }

        $areaTotals = [];
        $areaBoothTypes = [];

        foreach ($areaTypeRows as $row) {
            if ($row->layout_area_id === null) {
                continue;
            }
            $areaId = (int) $row->layout_area_id;
            $cnt = (int) $row->aggregate_count;
            $areaTotals[$areaId] = ($areaTotals[$areaId] ?? 0) + $cnt;

            if ($row->booth_type !== null && $row->booth_type !== '') {
                $bt = (string) $row->booth_type;
                $areaBoothTypes[$areaId][$bt] = ($areaBoothTypes[$areaId][$bt] ?? 0) + $cnt;
            }
        }

        return [
            'total_booths' => $totalBooths,
            'total_facilities' => $totalFacilities,
            'total_items' => $totalItems,
            'booth_categories' => $boothCategories,
            'facility_categories' => $facilityCategories,
            'area_totals' => $areaTotals,
            'area_booth_types' => $areaBoothTypes,
        ];
    }

    /**
     * 配置アイテム一覧をページネーション取得
     */
    #[Override]
    public function paginateItems(LayoutMap $map, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = LayoutItem::with(['area', 'vendor'])
            ->leftJoin('layout_areas', 'layout_areas.id', '=', 'layout_items.layout_area_id')
            ->where('layout_items.layout_map_id', $map->id)
            ->select('layout_items.*');

        if (! empty($filters['item_type'])) {
            $query->where('layout_items.item_type', $filters['item_type']);
        }

        if (! empty($filters['area_id'])) {
            $query->where('layout_items.layout_area_id', $filters['area_id']);
        }

        if (! empty($filters['category'])) {
            $query->where('layout_items.category', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.trim((string) $filters['search']).'%';
            $query->where(function (Builder $q) use ($search): void {
                $q->where('layout_items.code', 'like', $search)
                    ->orWhere('layout_items.name', 'like', $search)
                    ->orWhere('layout_items.location_description', 'like', $search)
                    ->orWhere('layout_items.notes', 'like', $search)
                    ->orWhereHas('vendor', function (Builder $sq) use ($search): void {
                        $sq->where('name', 'like', $search)
                            ->orWhere('contact_person', 'like', $search);
                    });
            });
        }

        $resolved = TableQueryOptions::resolveSort(
            isset($filters['sort']) ? (string) $filters['sort'] : null,
            isset($filters['direction']) ? (string) $filters['direction'] : null,
            ['display_order', 'code', 'layout_area_id', 'name', 'category', 'booth_type'],
            'display_order',
            'asc',
        );

        if ($resolved['sort'] === 'display_order' && $resolved['direction'] === 'asc') {
            $query->orderByRaw('COALESCE(layout_areas.display_order, 999999) ASC')
                ->orderBy('layout_items.display_order', 'asc')
                ->orderBy('layout_items.code', 'asc');
        } elseif ($resolved['sort'] === 'layout_area_id') {
            $dir = $resolved['direction'] === 'desc' ? 'DESC' : 'ASC';
            $nullOrder = $resolved['direction'] === 'desc' ? -1 : 999999;
            $query->orderByRaw("COALESCE(layout_areas.display_order, {$nullOrder}) {$dir}")
                ->orderBy('layout_items.display_order', 'asc')
                ->orderBy('layout_items.code', 'asc');
        } else {
            $query->orderBy('layout_items.'.$resolved['sort'], $resolved['direction'])
                ->orderBy('layout_items.code', 'asc');
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * 配置アイテムを新規登録
     */
    #[Override]
    public function storeItem(LayoutMap $map, array $data): LayoutItem
    {
        $data['layout_map_id'] = $map->id;
        if (! isset($data['display_order'])) {
            $maxOrder = LayoutItem::where('layout_map_id', $map->id)->max('display_order') ?? 0;
            $data['display_order'] = $maxOrder + 1;
        }

        return LayoutItem::create($data)->fresh(['area', 'vendor']);
    }

    /**
     * 配置アイテムを更新
     */
    #[Override]
    public function updateItem(LayoutItem $item, array $data): LayoutItem
    {
        $item->update($data);

        return $item->fresh(['area', 'vendor']);
    }

    /**
     * 配置アイテムを削除
     */
    #[Override]
    public function deleteItem(LayoutItem $item): void
    {
        $item->delete();
    }
}
