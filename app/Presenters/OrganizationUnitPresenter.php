<?php

namespace App\Presenters;

use App\Models\OrganizationUnit;
use App\Models\Staff;
use Illuminate\Support\Collection;

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
        return $this->resolveStaffCollection()->pluck('name')->implode(', ');
    }

    /**
     * 担当スタッフ名のテキスト属性付き表示（一般は括弧省略、幹事・幹事会・外部は括弧表示）
     */
    public function staffNamesWithAttributeDisplay(): string
    {
        return $this->resolveStaffCollection()
            ->map(fn (Staff $staff): string => $staff->presenter()->nameWithAttribute())
            ->implode(', ');
    }

    /**
     * 担当スタッフ名のPDF表示用文字列（幹事: ★、幹事会: ☆、一般・外部: マークなし）
     */
    public function staffNamesForPdfDisplay(): string
    {
        return $this->resolveStaffCollection()
            ->map(fn (Staff $staff): string => $staff->presenter()->pdfDisplayName())
            ->implode(', ');
    }

    /**
     * ロード済みリレーションを優先して所属スタッフコレクションを解決
     *
     * @return Collection<int, Staff>
     */
    private function resolveStaffCollection(): Collection
    {
        if ($this->unit->relationLoaded('staff')) {
            return $this->unit->staff;
        }

        if ($this->unit->relationLoaded('staffAssignments')) {
            return $this->unit->staffAssignments
                ->map(fn ($assignment): ?Staff => $assignment->staff)
                ->filter()
                ->values();
        }

        return $this->unit->staff()->get();
    }
}
