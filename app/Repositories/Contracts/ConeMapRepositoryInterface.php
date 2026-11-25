<?php

namespace App\Repositories\Contracts;

use App\Models\ConeMapBar;
use App\Models\ConeMapBarricade;
use App\Models\ConeMapCallout;
use App\Models\ConeMapCone;
use App\Models\Equipment;
use App\Models\SiteBaseMap;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Collection;

interface ConeMapRepositoryInterface
{
    /**
     * @return Collection<int, ConeMapCone>
     */
    public function getCones(Timetable $timetable, SiteBaseMap $siteBaseMap): Collection;

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, ConeMapCone>
     */
    public function findConesByIds(Timetable $timetable, SiteBaseMap $siteBaseMap, array $ids): Collection;

    public function findConeById(Timetable $timetable, SiteBaseMap $siteBaseMap, int $id): ?ConeMapCone;

    public function saveCone(ConeMapCone $cone): ConeMapCone;

    public function deleteCone(ConeMapCone $cone): bool;

    /**
     * @return Collection<int, ConeMapBar>
     */
    public function getBars(Timetable $timetable, SiteBaseMap $siteBaseMap): Collection;

    /**
     * 指定コーンに接続されているバー一覧を取得
     *
     * @return Collection<int, ConeMapBar>
     */
    public function getBarsForCone(ConeMapCone $cone): Collection;

    public function saveBar(ConeMapBar $bar): ConeMapBar;

    public function deleteBar(ConeMapBar $bar): bool;

    public function deleteBarsForCone(ConeMapCone $cone): int;

    /**
     * @return Collection<int, ConeMapBarricade>
     */
    public function getBarricades(Timetable $timetable, SiteBaseMap $siteBaseMap): Collection;

    /**
     * @param  array<int, int>  $ids
     * @return Collection<int, ConeMapBarricade>
     */
    public function findBarricadesByIds(Timetable $timetable, SiteBaseMap $siteBaseMap, array $ids): Collection;

    public function findBarricadeById(Timetable $timetable, SiteBaseMap $siteBaseMap, int $id): ?ConeMapBarricade;

    public function saveBarricade(ConeMapBarricade $barricade): ConeMapBarricade;

    public function deleteBarricade(ConeMapBarricade $barricade): bool;

    /**
     * @return Collection<int, ConeMapCallout>
     */
    public function getCallouts(Timetable $timetable, SiteBaseMap $siteBaseMap): Collection;

    public function saveCallout(ConeMapCallout $callout): ConeMapCallout;

    public function deleteCallout(ConeMapCallout $callout): bool;

    /**
     * コーンに紐づく吹き出しのアンカー座標を同期更新
     */
    public function syncCalloutAnchorsForCone(ConeMapCone $cone): void;

    /**
     * バリケードに紐づく吹き出しのアンカー座標を同期更新
     */
    public function syncCalloutAnchorsForBarricade(ConeMapBarricade $barricade): void;

    /**
     * 運行情報・白地図上の全カラーコーン・バリケード関連配置をクリア
     */
    public function clearAll(Timetable $timetable, SiteBaseMap $siteBaseMap): void;

    /**
     * 指定白地図を使用しているカラーコーン・バリケード配置の総件数を取得
     */
    public function countItemsUsingBaseMap(SiteBaseMap $siteBaseMap): int;

    /**
     * 在庫比較用の資機材一覧を取得
     *
     * @return Collection<int, Equipment>
     */
    public function getInventoryEquipments(): Collection;
}
