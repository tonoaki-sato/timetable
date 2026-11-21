<?php

namespace App\Repositories\Contracts;

use App\Models\LayoutArea;
use App\Models\LayoutMap;
use Illuminate\Database\Eloquent\Collection;

interface LayoutAreaRepositoryInterface
{
    /**
     * 全会場エリアを配置件数・利用運行数・関連拠点付きで表示順に取得
     *
     * @return Collection<int, LayoutArea>
     */
    public function getAllWithCountsAndLocation(): Collection;

    /**
     * 有効な会場エリア一覧を表示順に取得
     *
     * @return Collection<int, LayoutArea>
     */
    public function getActiveOrdered(): Collection;

    /**
     * 指定配置図面で利用対象となる会場エリア一覧を取得
     * （有効な全エリア＋当該図面で使用中の無効エリア）
     *
     * @return Collection<int, LayoutArea>
     */
    public function getForLayoutMap(LayoutMap $map, bool $withScopedItems = false): Collection;

    /**
     * IDによる会場エリア取得
     */
    public function findById(int $id): ?LayoutArea;

    /**
     * 最大表示順を取得
     */
    public function getMaxDisplayOrder(): int;

    /**
     * 会場エリアを新規作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): LayoutArea;

    /**
     * 会場エリアを更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(LayoutArea $layoutArea, array $attributes): LayoutArea;

    /**
     * 会場エリアを削除
     */
    public function delete(LayoutArea $layoutArea): bool;

    /**
     * 会場エリアを参照している配置アイテム件数を取得
     */
    public function countItems(LayoutArea $layoutArea): int;

    /**
     * 会場エリアの表示順を一括更新
     *
     * @param  array<int, int>  $layoutAreaIds
     */
    public function reorder(array $layoutAreaIds): void;
}
