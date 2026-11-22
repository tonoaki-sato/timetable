<?php

namespace App\Presenters;

use App\Models\LayoutItem;
use App\Models\Vendor;

class VendorPresenter
{
    public function __construct(
        protected Vendor $vendor
    ) {}

    /**
     * 詳細モーダル表示用のブース情報配列を取得
     *
     * @return array<int, array<string, mixed>>
     */
    public function boothsDetailList(): array
    {
        return $this->vendor->items->map(function (LayoutItem $booth): array {
            $categoryLabel = $booth->boothCategory()?->label() ?? '';
            $categoryBadge = $booth->boothCategory()?->badgeClasses() ?? 'bg-slate-100 text-slate-700 border-slate-300';
            $typeLabel = $booth->boothType()?->label() ?? '';

            return [
                'id' => $booth->id,
                'code' => $booth->code,
                'name' => $booth->presenter()->displayName(),
                'area' => $booth->area?->name ?? '通り',
                'category_label' => $categoryLabel,
                'category_badge' => $categoryBadge,
                'type_label' => $typeLabel,
                'status_label' => $booth->status?->label() ?? '確定',
                'status_icon' => $booth->status?->icon() ?? '🟢',
                'status_badge' => $booth->status?->badgeClasses() ?? 'bg-emerald-50 text-emerald-700 border-emerald-300',
                'notes' => $booth->notes ?? '',
            ];
        })->values()->all();
    }
}
