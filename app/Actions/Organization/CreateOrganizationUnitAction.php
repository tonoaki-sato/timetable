<?php

namespace App\Actions\Organization;

use App\DTOs\Organization\OrganizationUnitData;
use App\Models\OrganizationUnit;
use App\Repositories\Contracts\OrganizationUnitRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CreateOrganizationUnitAction
{
    public function __construct(
        protected OrganizationUnitRepositoryInterface $repository
    ) {}

    /**
     * 組織ユニットの作成および担当スタッフの同期
     */
    public function execute(OrganizationUnitData $data): OrganizationUnit
    {
        return DB::transaction(function () use ($data) {
            $unit = $this->repository->create($data->toModelAttributes());

            $this->repository->syncStaff($unit, $data->staffAssignments);

            return $unit->load('staff');
        });
    }
}
