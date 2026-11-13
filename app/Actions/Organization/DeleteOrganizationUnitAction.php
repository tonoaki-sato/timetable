<?php

namespace App\Actions\Organization;

use App\Models\OrganizationUnit;
use App\Repositories\Contracts\OrganizationUnitRepositoryInterface;

class DeleteOrganizationUnitAction
{
    public function __construct(
        protected OrganizationUnitRepositoryInterface $repository
    ) {}

    /**
     * 組織ユニットの削除
     */
    public function execute(OrganizationUnit $unit): bool
    {
        return $this->repository->delete($unit);
    }
}
