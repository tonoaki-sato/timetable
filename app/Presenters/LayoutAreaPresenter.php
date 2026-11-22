<?php

namespace App\Presenters;

use App\Models\LayoutArea;

class LayoutAreaPresenter
{
    public function __construct(
        protected LayoutArea $layoutArea
    ) {}

    /**
     * 詳細・編集モーダル用ペイロード配列を生成
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     location_id: ?int,
     *     location_name: ?string,
     *     location_type_label: ?string,
     *     notes: ?string,
     *     display_order: int,
     *     is_active: bool,
     *     items_count: int,
     *     layout_maps_count: int,
     *     update_url: string,
     *     destroy_url: string
     * }
     */
    public function toModalPayload(): array
    {
        return [
            'id' => (int) $this->layoutArea->id,
            'name' => (string) $this->layoutArea->name,
            'location_id' => $this->layoutArea->location_id !== null ? (int) $this->layoutArea->location_id : null,
            'location_name' => $this->layoutArea->location?->name,
            'location_type_label' => $this->layoutArea->location?->type?->label,
            'notes' => $this->layoutArea->notes,
            'display_order' => (int) $this->layoutArea->display_order,
            'is_active' => (bool) $this->layoutArea->is_active,
            'items_count' => (int) ($this->layoutArea->items_count ?? $this->layoutArea->items()->count()),
            'layout_maps_count' => (int) ($this->layoutArea->layout_maps_count ?? $this->layoutArea->items()->distinct()->count('layout_map_id')),
            'update_url' => route('layout-areas.update', $this->layoutArea),
            'destroy_url' => route('layout-areas.destroy', $this->layoutArea),
        ];
    }
}
