<?php

namespace App\Repositories\Contracts;

use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface VendorRepositoryInterface
{
    /**
     * 全出展・出店者一覧を取得（ID順、ブース数事前ロード）
     */
    public function getAll(): Collection;

    /**
     * 出展・出店者一覧をページネーション取得（検索対応）
     *
     * @param  array{search?: string|null}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * IDで出展・出店者を取得
     */
    public function findById(int $id): ?Vendor;

    /**
     * 出展・出店者を登録
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Vendor;

    /**
     * 出展・出店者を更新
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Vendor $vendor, array $data): Vendor;

    /**
     * 出展・出店者を削除
     */
    public function delete(Vendor $vendor): bool;
}
