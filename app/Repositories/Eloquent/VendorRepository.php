<?php

namespace App\Repositories\Eloquent;

use App\Models\Vendor;
use App\Repositories\Contracts\VendorRepositoryInterface;
use App\Support\TableQueryOptions;
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
        return Vendor::with(['items.area'])
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * 出展・出店者一覧をページネーション取得
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Vendor::with(['items.area']);

        if (! empty($filters['search'])) {
            $search = '%'.trim($filters['search']).'%';
            $query->where(function (Builder $q) use ($search) {
                $q->where('name', 'like', $search)
                    ->orWhere('contact_person', 'like', $search)
                    ->orWhere('contact', 'like', $search)
                    ->orWhereHas('items', function (Builder $bq) use ($search) {
                        $bq->where('name', 'like', $search)
                            ->orWhere('code', 'like', $search)
                            ->orWhere('notes', 'like', $search);
                    });
            });
        }

        $resolved = TableQueryOptions::resolveSort(
            isset($filters['sort']) ? (string) $filters['sort'] : null,
            isset($filters['direction']) ? (string) $filters['direction'] : null,
            ['id', 'name', 'contact_person', 'contact'],
            'id',
            'asc',
        );

        return $query->orderBy($resolved['sort'], $resolved['direction'])
            ->orderBy('id', 'asc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * グローバル検索用のクイック検索
     */
    public function searchQuick(string $keyword, int $limit = 5): Collection
    {
        return Vendor::query()
            ->search($keyword)
            ->orderBy('name', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * IDで出展・出店者を取得
     */
    public function findById(int $id): ?Vendor
    {
        return Vendor::with(['items.area'])->find($id);
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

        return $vendor->fresh(['items.area']);
    }

    /**
     * 出展・出店者を削除
     */
    public function delete(Vendor $vendor): bool
    {
        return (bool) $vendor->delete();
    }
}
