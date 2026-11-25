<?php

namespace App\DTOs\ConeMap;

use App\Models\ConeMapBar;
use App\Models\ConeMapBarricade;
use App\Models\ConeMapCallout;
use App\Models\ConeMapCone;
use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use Illuminate\Database\Eloquent\Collection;

/**
 * カラーコーン・バリケード配置エディタ初期描画データ DTO
 */
readonly class ConeMapEditorDataDTO
{
    /**
     * @param  Collection<int, SiteBaseMap>  $availableBaseMaps
     * @param  Collection<int, ConeMapCone>  $cones
     * @param  Collection<int, ConeMapBar>  $bars
     * @param  Collection<int, ConeMapBarricade>  $barricades
     * @param  Collection<int, ConeMapCallout>  $callouts
     * @param  Collection<int, SiteMapPlacement>  $boothPlacements
     */
    public function __construct(
        public SiteBaseMap $siteBaseMap,
        public Collection $availableBaseMaps,
        public Collection $cones,
        public Collection $bars,
        public Collection $barricades,
        public Collection $callouts,
        public Collection $boothPlacements,
        public ConeMapSummaryDTO $summary,
    ) {}

    /**
     * View 渡し用連想配列へ変換
     *
     * @return array<string, mixed>
     */
    public function toViewData(): array
    {
        return [
            'siteBaseMap' => $this->siteBaseMap,
            'availableBaseMaps' => $this->availableBaseMaps,
            'cones' => $this->cones,
            'bars' => $this->bars,
            'barricades' => $this->barricades,
            'callouts' => $this->callouts,
            'boothPlacements' => $this->boothPlacements,
            'summary' => $this->summary,
        ];
    }
}
