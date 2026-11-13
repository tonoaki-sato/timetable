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
     * 組織別所属スタッフ一覧PDF用のデータを取得
     *
     * @return array{
     *     units: Collection<int, OrganizationUnit>,
     *     columnWidths: array{no: string, name: string, staff: string, description: string},
     *     title: string,
     *     generatedAt: string
     * }
     */
    public function getStaffListPdfViewData(): array
    {
        $treeUnits = $this->repository->getTreeWithStaff();
        $flattenedUnits = $this->flattenTree($treeUnits);

        // 詳細テキストの改行除去および整形
        $flattenedUnits->each(function (OrganizationUnit $unit) {
            $desc = $unit->short_description ?: $unit->description;
            if ($desc !== null && $desc !== '') {
                $cleanedDesc = trim((string) preg_replace('/\s+/u', ' ', $desc));
                $unit->setAttribute('cleaned_description', $cleanedDesc);
            } else {
                $unit->setAttribute('cleaned_description', null);
            }
        });

        $columnWidths = $this->calculateDynamicColumnWidths($flattenedUnits);

        return [
            'units' => $flattenedUnits,
            'columnWidths' => $columnWidths,
            'title' => '保土ケ谷宿場まつり実行委員会 組織別所属スタッフ一覧',
            'generatedAt' => now()->format('Y年m月d日 H:i'),
        ];
    }

    /**
     * 組織一覧データから各列の最適な幅（%）を動的に計算
     *
     * @param  Collection<int, OrganizationUnit>  $units
     * @return array{no: string, name: string, staff: string, description: string}
     */
    public function calculateDynamicColumnWidths(Collection $units): array
    {
        $noWidth = 4.5;

        if ($units->isEmpty()) {
            return [
                'no' => '4.5%',
                'name' => '18.5%',
                'staff' => '36.0%',
                'description' => '41.0%',
            ];
        }

        // 名前列の最大必要幅を文字幅・インデントに基づき計算してテキスト幅に寄せる (約17%〜20%)
        $maxNameWidth = (float) ($units->max(fn (OrganizationUnit $u) => mb_strwidth(str_repeat(' ', (int) ($u->depth ?? 0)).$u->name)) ?: 10);
        $nameWidth = max(17.0, min(20.0, round($maxNameWidth * 0.8, 1)));

        // 残りの幅を所属スタッフと詳細で文字量に応じて配分
        $remainingWidth = 100.0 - $noWidth - $nameWidth; // 約77.0%

        $maxStaff = (float) ($units->max(fn (OrganizationUnit $u) => mb_strwidth($u->staffNamesDisplay ?: '')) ?: 10);
        $maxDesc = (float) ($units->max(fn (OrganizationUnit $u) => mb_strwidth($u->cleaned_description ?: '')) ?: 10);

        $weightStaff = max(20.0, $maxStaff);
        $weightDesc = max(20.0, $maxDesc);
        $totalWeight = $weightStaff + $weightDesc;

        $staffWidth = round(($weightStaff / $totalWeight) * $remainingWidth, 1);
        $descWidth = round($remainingWidth - $staffWidth, 1);

        return [
            'no' => $noWidth.'%',
            'name' => $nameWidth.'%',
            'staff' => $staffWidth.'%',
            'description' => $descWidth.'%',
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
