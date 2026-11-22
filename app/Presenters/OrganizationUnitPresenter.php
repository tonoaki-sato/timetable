<?php

namespace App\Presenters;

use App\Models\OrganizationUnit;

class OrganizationUnitPresenter
{
    public function __construct(
        protected OrganizationUnit $unit,
    ) {}

    /**
     * 担当スタッフ名のカンマ区切り文字列（一覧・組織図表示用）
     */
    public function staffNamesDisplay(): string
    {
        if ($this->unit->relationLoaded('staff')) {
            return $this->unit->staff->pluck('name')->implode(', ');
        }

        if ($this->unit->relationLoaded('staffAssignments')) {
            return $this->unit->staffAssignments
                ->map(fn ($assignment): ?string => $assignment->staff?->name)
                ->filter()
                ->implode(', ');
        }

        return $this->unit->staff()->pluck('name')->implode(', ');
    }
}
