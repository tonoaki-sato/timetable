<?php

namespace App\Presenters;

use App\Models\SiteBaseMap;

class SiteBaseMapPresenter
{
    public function __construct(
        protected SiteBaseMap $siteBaseMap
    ) {}

    /**
     * 詳細・編集モーダル用ペイロード配列を生成
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     cad_version: string,
     *     paper_size: string,
     *     scale_ratio: float,
     *     width_meters: float,
     *     height_meters: float,
     *     svg_path: string,
     *     svg_url: string,
     *     pdf_path: ?string,
     *     pdf_url: ?string,
     *     viewbox: string,
     *     is_default: bool,
     *     is_active: bool,
     *     is_preset_asset: bool,
     *     notes: ?string,
     *     timetables_count: int,
     *     placements_count: int,
     *     update_url: string,
     *     destroy_url: string
     * }
     */
    public function toModalPayload(): array
    {
        return [
            'id' => $this->siteBaseMap->id,
            'name' => (string) $this->siteBaseMap->name,
            'cad_version' => (string) $this->siteBaseMap->cad_version,
            'paper_size' => (string) $this->siteBaseMap->paper_size,
            'scale_ratio' => (float) $this->siteBaseMap->scale_ratio,
            'width_meters' => (float) $this->siteBaseMap->width_meters,
            'height_meters' => (float) $this->siteBaseMap->height_meters,
            'svg_path' => (string) $this->siteBaseMap->svg_path,
            'svg_url' => asset((string) $this->siteBaseMap->svg_path),
            'pdf_path' => $this->siteBaseMap->pdf_path ? (string) $this->siteBaseMap->pdf_path : null,
            'pdf_url' => $this->siteBaseMap->pdf_path ? asset((string) $this->siteBaseMap->pdf_path) : null,
            'viewbox' => (string) $this->siteBaseMap->viewbox,
            'is_default' => (bool) $this->siteBaseMap->is_default,
            'is_active' => (bool) $this->siteBaseMap->is_active,
            'is_preset_asset' => $this->siteBaseMap->isPresetAsset(),
            'notes' => $this->siteBaseMap->notes,
            'timetables_count' => (int) ($this->siteBaseMap->timetables_count ?? $this->siteBaseMap->timetables()->count()),
            'placements_count' => (int) ($this->siteBaseMap->placements_count ?? $this->siteBaseMap->placements()->count()),
            'update_url' => route('site-base-maps.update', $this->siteBaseMap),
            'destroy_url' => route('site-base-maps.destroy', $this->siteBaseMap),
        ];
    }
}
