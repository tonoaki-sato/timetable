<?php

namespace App\Repositories\Eloquent;

use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Timetable;
use App\Repositories\Contracts\LayoutMapRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class LayoutMapRepository implements LayoutMapRepositoryInterface
{
    /**
     * タイムテーブルに紐づく現場配置図面を取得（なければ作成）
     */
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
    public function findWithDetails(int $id): ?LayoutMap
    {
        return LayoutMap::with([
            'timetable',
            'areas.location',
            'items.vendor',
            'items.area',
        ])->find($id);
    }

    /**
     * 配置アイテム一覧をページネーション取得
     */
    public function paginateItems(LayoutMap $map, array $filters = [], int $perPage = 50): LengthAwarePaginator
    {
        $query = LayoutItem::with(['area', 'vendor'])
            ->where('layout_map_id', $map->id);

        if (! empty($filters['item_type'])) {
            $query->where('item_type', $filters['item_type']);
        }

        if (! empty($filters['area_id'])) {
            $query->where('layout_area_id', $filters['area_id']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.trim($filters['search']).'%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('code', 'like', $search)
                    ->orWhere('name', 'like', $search)
                    ->orWhere('location_description', 'like', $search)
                    ->orWhere('notes', 'like', $search)
                    ->orWhereHas('vendor', function (Builder $sq) use ($search) {
                        $sq->where('name', 'like', $search)
                            ->orWhere('contact_person', 'like', $search);
                    });
            });
        }

        return $query->orderBy('layout_area_id', 'asc')
            ->orderBy('display_order', 'asc')
            ->orderBy('code', 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * 配置アイテムを新規登録
     */
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
    public function updateItem(LayoutItem $item, array $data): LayoutItem
    {
        $item->update($data);

        return $item->fresh(['area', 'vendor']);
    }

    /**
     * 配置アイテムを削除
     */
    public function deleteItem(LayoutItem $item): void
    {
        $item->delete();
    }
}
