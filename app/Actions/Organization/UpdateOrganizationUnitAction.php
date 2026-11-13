<?php

namespace App\Actions\Organization;

use App\DTOs\Organization\OrganizationUnitData;
use App\Models\OrganizationUnit;
use App\Repositories\Contracts\OrganizationUnitRepositoryInterface;
use Illuminate\Support\Facades\DB;

class UpdateOrganizationUnitAction
{
    public function __construct(
        protected OrganizationUnitRepositoryInterface $repository
    ) {}

    /**
     * 組織ユニットの更新および担当スタッフの同期
     */
    public function execute(OrganizationUnit $unit, OrganizationUnitData $data): OrganizationUnit
    {
        return DB::transaction(function () use ($unit, $data) {
            $this->repository->update($unit, $data->toModelAttributes());

            $this->syncStaff($unit, $data->staffAssignments);

            return $unit->fresh(['staff', 'parent']);
        });
    }

    /**
     * 担当スタッフの同期（DnD並び順保持）
     *
     * @param  array<int, int|array{staff_id: int, role?: ?string, display_order?: int}>  $staffAssignments
     */
    protected function syncStaff(OrganizationUnit $unit, array $staffAssignments): void
    {
        $syncData = [];

        foreach (array_values($staffAssignments) as $index => $item) {
            $staffId = is_array($item) ? (int) ($item['staff_id'] ?? 0) : (int) $item;
            if ($staffId <= 0) {
                continue;
            }

            $role = is_array($item) && isset($item['role']) && $item['role'] !== '' ? (string) $item['role'] : null;
            $displayOrder = is_array($item) && isset($item['display_order'])
                ? (int) $item['display_order']
                : ($index + 1) * 10;

            $syncData[$staffId] = [
                'role' => $role,
                'display_order' => $displayOrder,
            ];
        }

        $unit->staff()->sync($syncData);
    }
}

