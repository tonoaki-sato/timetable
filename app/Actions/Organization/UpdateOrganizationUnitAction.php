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

            $this->repository->syncStaff($unit, $data->staffAssignments);

            return $unit->fresh(['staff', 'parent']);
        });
    }
}
