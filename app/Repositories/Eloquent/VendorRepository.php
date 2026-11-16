<?php

namespace App\Repositories\Eloquent;

use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class VendorRepository implements VendorRepositoryInterface
{
    /**
     * 全出展・出店者一覧を取得
     */
    public function getAll(): Collection
    {
        return Vendor::with(['booths.area'])
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * 出展・出店者一覧をページネーション取得
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Vendor::with(['booths.area']);

        if (! empty($filters['search'])) {
            $search = '%'.trim($filters['search']).'%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('contact_person', 'like', $search)
                    ->orWhere('contact', 'like', $search)
                    ->orWhereHas('booths', function (Builder $bq) use ($search) {
                        $bq->where('name', 'like', $search)
                            ->orWhere('booth_code', 'like', $search)
                            ->orWhere('product_items', 'like', $search);
                    });
            });
        }

        return $query->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * IDで出展・出店者を取得
     */
    public function findById(int $id): ?Vendor
    {
        return Vendor::with(['booths.area'])->find($id);
    }

    /**
     * 出展・出店者を登録
     */
    public function create(array $data): Vendor
    {
        return Vendor::create($data);
    }

    /**
     * 出展・出店者を更新
     */
    public function update(Vendor $vendor, array $data): Vendor
    {
        $vendor->update($data);

        return $vendor->fresh(['booths.area']);
    }

    /**
     * 出展・出店者を削除
     */
    public function delete(Vendor $vendor): bool
    {
        return (bool) $vendor->delete();
    }
}
