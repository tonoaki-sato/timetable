<?php

namespace App\Repositories\Contracts;

use App\DTOs\VendorFilterDTO;
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
     * セレクトボックス用に全出展・出店者の ID・名称のみを名称順で取得
     *
     * @return Collection<int, Vendor>
     */
    public function getAllForSelect(): Collection;

    /**
     * リレーションを事前ロードせずに全出展・出店者を取得（CSV突合等用）
     *
     * @return Collection<int, Vendor>
     */
    public function getAllBasic(): Collection;

    /**
     * 出展・出店者一覧をページネーション取得（検索・ソート対応）
     */
    public function paginate(VendorFilterDTO $filter): LengthAwarePaginator;

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
     * 関連リレーションを再取得せずに出展・出店者を更新（CSV一括取り込み等用）
     *
     * @param  array<string, mixed>  $data
     */
    public function updateWithoutReload(Vendor $vendor, array $data): Vendor;

    /**
     * 出展・出店者を削除
     */
    public function delete(Vendor $vendor): bool;
}
