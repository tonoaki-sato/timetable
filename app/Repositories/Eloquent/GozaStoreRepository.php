<?php

namespace App\Repositories\Eloquent;

use App\Models\GozaStore;
use App\Models\Timetable;
use App\Repositories\Contracts\GozaStoreRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class GozaStoreRepository implements GozaStoreRepositoryInterface
{
    /**
     * タイムテーブルに紐づく出店者一覧を取得
     */
    public function getForTimetable(Timetable $timetable): Collection
    {
        return GozaStore::with('booth')
            ->where('timetable_id', $timetable->id)
            ->orderBy('store_name', 'asc')
            ->get();
    }

    /**
     * 出店者一覧をページネーション取得
     */
    public function paginateForTimetable(Timetable $timetable, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = GozaStore::with('booth')
            ->where('timetable_id', $timetable->id);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = '%'.trim($filters['search']).'%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('store_name', 'like', $search)
                    ->orWhere('representative_name', 'like', $search)
                    ->orWhere('emergency_contact', 'like', $search)
                    ->orWhere('product_items', 'like', $search);
            });
        }

        return $query->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * 出店者を登録
     */
    public function create(Timetable $timetable, array $data): GozaStore
    {
        $data['timetable_id'] = $timetable->id;

        return GozaStore::create($data);
    }

    /**
     * 出店者を更新
     */
    public function update(GozaStore $store, array $data): GozaStore
    {
        $store->update($data);

        return $store->fresh('booth');
    }

    /**
     * 出店者を削除
     */
    public function delete(GozaStore $store): bool
    {
        return (bool) $store->delete();
    }
}
