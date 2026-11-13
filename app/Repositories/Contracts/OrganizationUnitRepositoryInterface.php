<?php

namespace App\Repositories\Contracts;

use App\Models\OrganizationUnit;
use Illuminate\Database\Eloquent\Collection;

interface OrganizationUnitRepositoryInterface
{
    /**
     * IDによる組織ユニットの取得
     */
    public function findById(int $id): ?OrganizationUnit;

    /**
     * スタッフリレーション付きでIDによる組織ユニットの取得
     */
    public function findWithStaff(int $id): ?OrganizationUnit;

    /**
     * 全組織ユニットを担当スタッフ込み・表示順昇順で取得
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getAllOrderedWithStaff(): Collection;

    /**
     * 最上位（ルート）組織ユニットを子組織および担当スタッフ込みで取得
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getTreeWithStaff(): Collection;

    /**
     * 組織ユニットの作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): OrganizationUnit;

    /**
     * 組織ユニットの更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(OrganizationUnit $organizationUnit, array $attributes): bool;

    /**
     * 組織ユニットの削除
     */
    public function delete(OrganizationUnit $organizationUnit): bool;

    /**
     * 組織ユニットの表示順序を一括更新
     *
     * @param  array<int, int>  $orderedIds
     */
    public function updateDisplayOrders(array $orderedIds): void;

    /**
     * 親組織候補一覧を取得（自身のIDおよびその子孫を除外可能）
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getParentOptions(?int $excludeId = null): Collection;
}
