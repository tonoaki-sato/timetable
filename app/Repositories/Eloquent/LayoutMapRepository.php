<?php

namespace App\Repositories\Eloquent;

use App\Models\LayoutBooth;
use App\Models\LayoutFacility;
use App\Models\LayoutMap;
use App\Models\Timetable;
use App\Repositories\Contracts\LayoutMapRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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
            'booths.gozaStore',
            'booths.area',
            'facilities.area',
        ])->find($id);
    }

    /**
     * コマ割り一覧をページネーション取得
     */
    public function paginateBooths(LayoutMap $map, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = LayoutBooth::with(['area', 'gozaStore'])
            ->where('layout_map_id', $map->id);

        if (! empty($filters['area_id'])) {
            $query->where('layout_area_id', $filters['area_id']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.trim($filters['search']).'%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('booth_code', 'like', $search)
                    ->orWhere('name', 'like', $search)
                    ->orWhere('adjacent_landmark', 'like', $search)
                    ->orWhereHas('gozaStore', function (Builder $sq) use ($search) {
                        $sq->where('store_name', 'like', $search)
                            ->orWhere('product_items', 'like', $search);
                    });
            });
        }

        return $query->orderBy('display_order', 'asc')
            ->orderBy('booth_code', 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * コマ割り情報を更新
     */
    public function updateBooth(LayoutBooth $booth, array $data): LayoutBooth
    {
        $booth->update($data);

        return $booth->fresh(['area', 'gozaStore']);
    }

    /**
     * 現場設備一覧を取得
     */
    public function getFacilities(LayoutMap $map, array $filters = []): Collection
    {
        $query = LayoutFacility::with('area')
            ->where('layout_map_id', $map->id);

        if (! empty($filters['type'])) {
            $query->where('facility_type', $filters['type']);
        }

        return $query->orderBy('display_order', 'asc')->get();
    }

    /**
     * 現場設備情報を更新
     */
    public function updateFacility(LayoutFacility $facility, array $data): LayoutFacility
    {
        $facility->update($data);

        return $facility->fresh('area');
    }

    /**
     * 現場設備を新規登録
     */
    public function storeFacility(LayoutMap $map, array $data): LayoutFacility
    {
        $data['layout_map_id'] = $map->id;
        if (! isset($data['display_order'])) {
            $maxOrder = LayoutFacility::where('layout_map_id', $map->id)->max('display_order') ?? 0;
            $data['display_order'] = $maxOrder + 1;
        }

        return LayoutFacility::create($data)->fresh('area');
    }

    /**
     * 現場設備を削除
     */
    public function deleteFacility(LayoutFacility $facility): void
    {
        $facility->delete();
    }
}
