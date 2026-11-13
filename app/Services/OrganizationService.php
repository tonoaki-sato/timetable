<?php

namespace App\Services;

use App\Actions\Organization\CreateOrganizationUnitAction;
use App\Actions\Organization\DeleteOrganizationUnitAction;
use App\Actions\Organization\UpdateOrganizationUnitAction;
use App\DTOs\Organization\OrganizationUnitData;
use App\Models\OrganizationUnit;
use App\Models\Staff;
use App\Repositories\Contracts\OrganizationUnitRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class OrganizationService
{
    public function __construct(
        protected OrganizationUnitRepositoryInterface $repository,
        protected StaffRepositoryInterface $staffRepository,
        protected CreateOrganizationUnitAction $createAction,
        protected UpdateOrganizationUnitAction $updateAction,
        protected DeleteOrganizationUnitAction $deleteAction,
    ) {}

    /**
     * 組織管理メイン画面用のデータを取得
     *
     * @return array{
     *     units: Collection<int, OrganizationUnit>,
     *     treeUnits: Collection<int, OrganizationUnit>,
     *     parentOptions: Collection<int, object>,
     *     availableStaff: Collection<int, Staff>
     * }
     */
    public function getIndexViewData(): array
    {
        $treeUnits = $this->repository->getTreeWithStaff();
        $flattenedUnits = $this->flattenTree($treeUnits);

        $parentOptions = $flattenedUnits->map(function (OrganizationUnit $unit) {
            $depth = (int) ($unit->depth ?? 0);
            $prefix = str_repeat('　', $depth).($depth > 0 ? '└─ ' : '');

            return (object) [
                'id' => $unit->id,
                'name' => $prefix.$unit->name,
                'raw_name' => $unit->name,
                'parent_id' => $unit->parent_id,
            ];
        });

        return [
            'units' => $flattenedUnits,
            'treeUnits' => $treeUnits,
            'parentOptions' => $parentOptions,
            'availableStaff' => Staff::active()->orderBy('kana')->orderBy('name')->get(['id', 'name', 'kana']),
        ];
    }

    /**
     * 印刷専用画面用のデータを取得
     *
     * @return array{
     *     treeUnits: Collection<int, OrganizationUnit>,
     *     units: Collection<int, OrganizationUnit>
     * }
     */
    public function getPrintViewData(): array
    {
        $treeUnits = $this->repository->getTreeWithStaff();

        return [
            'treeUnits' => $treeUnits,
            'units' => $this->flattenTree($treeUnits),
        ];
    }

    /**
     * ツリー構造を深さ優先探索（DFS）でフラット化し、各ユニットに depth 属性を付与
     *
     * @param  Collection<int, OrganizationUnit>  $tree
     * @return Collection<int, OrganizationUnit>
     */
    public function flattenTree(Collection $tree, int $depth = 0): Collection
    {
        $flattened = new Collection;

        foreach ($tree as $unit) {
            $unit->setAttribute('depth', $depth);
            $flattened->push($unit);

            /** @var Collection<int, OrganizationUnit>|null $children */
            $children = $unit->children;
            if ($children && $children->isNotEmpty()) {
                $flattened = $flattened->concat($this->flattenTree($children, $depth + 1));
            }
        }

        return $flattened;
    }

    /**
     * モーダル表示用の詳細データを取得
     *
     * @return array{
     *     unit: OrganizationUnit,
     *     parent_name: ?string,
     *     staff: array<int, array{id: int, name: string, role: ?string, display_order: int}>
     * }
     */
    public function getUnitDetails(OrganizationUnit $unit): array
    {
        $unit->load(['parent', 'staff']);

        $staffList = $unit->staff->map(fn (Staff $staff) => [
            'id' => $staff->id,
            'name' => $staff->name,
            'role' => $staff->pivot->role ?? null,
            'display_order' => (int) ($staff->pivot->display_order ?? 0),
        ])->values()->all();

        return [
            'unit' => $unit,
            'parent_name' => $unit->parent?->name,
            'staff' => $staffList,
        ];
    }

    /**
     * 組織ユニットの作成
     */
    public function createUnit(OrganizationUnitData $data): OrganizationUnit
    {
        return $this->createAction->execute($data);
    }

    /**
     * 組織ユニットの更新
     */
    public function updateUnit(OrganizationUnit $unit, OrganizationUnitData $data): OrganizationUnit
    {
        return $this->updateAction->execute($unit, $data);
    }

    /**
     * 組織ユニットの削除
     */
    public function deleteUnit(OrganizationUnit $unit): bool
    {
        return $this->deleteAction->execute($unit);
    }

    /**
     * 組織の表示順序を一括更新
     *
     * @param  array<int, int>  $orderedIds
     */
    public function reorderUnits(array $orderedIds): void
    {
        $this->repository->updateDisplayOrders($orderedIds);
    }
}
