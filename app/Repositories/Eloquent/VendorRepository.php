<?php

namespace App\Repositories\Eloquent;

use App\Models\Timetable;
use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VendorRepository implements VendorRepositoryInterface
{
    /**
     * タイムテーブルに紐づく出店者一覧を取得
     */
    public function getForTimetable(Timetable $timetable): Collection
    {
        return Vendor::with(['booths.area'])
            ->where('timetable_id', $timetable->id)
            ->orderBy('name', 'asc')
            ->get();
    }

    /**
     * 出店者一覧をページネーション取得
     */
    public function paginateForTimetable(Timetable $timetable, array $filters = [], int $perPage = 25): LengthAwarePaginator
    {
        $query = Vendor::with(['booths.area'])
            ->where('timetable_id', $timetable->id);

        if (! empty($filters['category'])) {
            $query->whereHas('booths', function (Builder $q) use ($filters) {
                $q->where('category', $filters['category']);
            });
        }

        if (! empty($filters['search'])) {
            $search = '%'.trim($filters['search']).'%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('representative_name', 'like', $search)
                    ->orWhere('emergency_contact', 'like', $search)
                    ->orWhereHas('booths', function (Builder $bq) use ($search) {
                        $bq->where('booth_code', 'like', $search)
                            ->orWhere('product_items', 'like', $search)
                            ->orWhere('adjacent_landmark', 'like', $search);
                    });
            });
        }

        return $query->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * 出店者を登録
     */
    public function create(Timetable $timetable, array $data): Vendor
    {
        $data['timetable_id'] = $timetable->id;

        return Vendor::create($data);
    }

    /**
     * 出店者を更新
     */
    public function update(Vendor $vendor, array $data): Vendor
    {
        $vendor->update($data);

        return $vendor->fresh(['booths.area']);
    }

    /**
     * 出店者を削除
     */
    public function delete(Vendor $vendor): bool
    {
        return (bool) $vendor->delete();
    }
}
