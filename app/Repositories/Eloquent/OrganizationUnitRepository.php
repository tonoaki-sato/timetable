<?php

namespace App\Repositories\Eloquent;

use App\Models\OrganizationUnit;
use App\Repositories\Contracts\OrganizationUnitRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class OrganizationUnitRepository implements OrganizationUnitRepositoryInterface
{
    /**
     * IDによる組織ユニットの取得
     */
    public function findById(int $id): ?OrganizationUnit
    {
        return OrganizationUnit::find($id);
    }

    /**
     * スタッフリレーション付きでIDによる組織ユニットの取得
     */
    public function findWithStaff(int $id): ?OrganizationUnit
    {
        return OrganizationUnit::with(['staff', 'parent', 'children'])->find($id);
    }

    /**
     * 全組織ユニットを担当スタッフ込み・表示順昇順で取得
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getAllOrderedWithStaff(): Collection
    {
        return OrganizationUnit::with(['staff', 'parent'])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * 最上位（ルート）組織ユニットを子組織および担当スタッフ込みで取得
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getTreeWithStaff(): Collection
    {
        return OrganizationUnit::rootOnly()
            ->with(['staff', 'children.staff', 'children.children.staff'])
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * 組織ユニットの作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): OrganizationUnit
    {
        if (! isset($attributes['display_order'])) {
            $maxOrder = (int) OrganizationUnit::max('display_order');
            $attributes['display_order'] = $maxOrder + 10;
        }

        return OrganizationUnit::create($attributes);
    }

    /**
     * 組織ユニットの更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(OrganizationUnit $organizationUnit, array $attributes): bool
    {
        return $organizationUnit->update($attributes);
    }

    /**
     * 組織ユニットの削除
     */
    public function delete(OrganizationUnit $organizationUnit): bool
    {
        return (bool) $organizationUnit->delete();
    }

    /**
     * 組織ユニットの表示順序を一括更新
     *
     * @param  array<int, int>  $orderedIds
     */
    public function updateDisplayOrders(array $orderedIds): void
    {
        DB::transaction(function () use ($orderedIds) {
            foreach ($orderedIds as $index => $id) {
                OrganizationUnit::where('id', $id)->update([
                    'display_order' => ($index + 1) * 10,
                ]);
            }
        });
    }

    /**
     * 親組織候補一覧を取得（自身のIDを除外可能）
     *
     * @return Collection<int, OrganizationUnit>
     */
    public function getParentOptions(?int $excludeId = null): Collection
    {
        $query = OrganizationUnit::query()->orderBy('display_order')->orderBy('id');

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->get(['id', 'name', 'parent_id']);
    }
}
