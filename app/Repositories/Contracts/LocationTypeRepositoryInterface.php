<?php

namespace App\Repositories\Contracts;

use App\Models\LocationType;
use Illuminate\Database\Eloquent\Collection;

interface LocationTypeRepositoryInterface
{
    /**
     * 拠点件数カウント付きで全拠点タイプを表示順で取得
     *
     * @return Collection<int, LocationType>
     */
    public function getAllWithLocationsCountOrdered(): Collection;

    /**
     * 有効な拠点タイプ一覧を表示順で取得
     *
     * @return Collection<int, LocationType>
     */
    public function getActiveOrdered(): Collection;

    /**
     * 指定拠点タイプに紐づく拠点が存在するか判定
     */
    public function hasLocations(LocationType $locationType): bool;

    /**
     * 新規拠点タイプを作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): LocationType;

    /**
     * 拠点タイプを更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(LocationType $locationType, array $attributes): bool;

    /**
     * 拠点タイプを削除
     */
    public function delete(LocationType $locationType): bool;
}
