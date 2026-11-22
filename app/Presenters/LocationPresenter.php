<?php

namespace App\Presenters;

use App\Models\Location;

class LocationPresenter
{
    public function __construct(
        protected Location $location
    ) {}

    /**
     * 拠点種別ラベルを取得
     */
    public function typeLabel(): string
    {
        return $this->location->type?->label ?? $this->location->type?->name ?? '';
    }
}
