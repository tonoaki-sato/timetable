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
     * 出展・出店者一覧をページネーション取得（検索・ソート対応）
     *
     * @param  array{search?: string|null, sort?: string|null, direction?: string|null}  $filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * グローバル検索用のクイック検索
     *
     * @return Collection<int, Vendor>
     */
    public function searchQuick(string $keyword, int $limit = 5): Collection;

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
