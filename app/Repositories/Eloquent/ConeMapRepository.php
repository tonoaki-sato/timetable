<?php

namespace App\Repositories\Eloquent;

use App\Models\ConeMapBar;
use App\Models\ConeMapBarricade;
use App\Models\ConeMapCallout;
use App\Models\ConeMapCone;
use App\Models\Equipment;
use App\Models\SiteBaseMap;
use App\Models\Timetable;
use App\Repositories\Contracts\ConeMapRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Override;

class ConeMapRepository implements ConeMapRepositoryInterface
{
    #[Override]
    public function getCones(Timetable $timetable, SiteBaseMap $siteBaseMap): Collection
    {
        return ConeMapCone::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function findConesByIds(Timetable $timetable, SiteBaseMap $siteBaseMap, array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return ConeMapCone::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->whereIn('id', $ids)
            ->get();
    }

    #[Override]
    public function findConeById(Timetable $timetable, SiteBaseMap $siteBaseMap, int $id): ?ConeMapCone
    {
        return ConeMapCone::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->find($id);
    }

    #[Override]
    public function saveCone(ConeMapCone $cone): ConeMapCone
    {
        $cone->save();

        return $cone;
    }

    #[Override]
    public function deleteCone(ConeMapCone $cone): bool
    {
        return (bool) $cone->delete();
    }

    #[Override]
    public function getBars(Timetable $timetable, SiteBaseMap $siteBaseMap): Collection
    {
        return ConeMapBar::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function getBarsForCone(ConeMapCone $cone): Collection
    {
        return ConeMapBar::query()
            ->where(function ($query) use ($cone): void {
                $query->where('start_cone_id', $cone->id)
                    ->orWhere('end_cone_id', $cone->id);
            })
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function saveBar(ConeMapBar $bar): ConeMapBar
    {
        $bar->save();

        return $bar;
    }

    #[Override]
    public function deleteBar(ConeMapBar $bar): bool
    {
        return (bool) $bar->delete();
    }

    #[Override]
    public function deleteBarsForCone(ConeMapCone $cone): int
    {
        return ConeMapBar::query()
            ->where('start_cone_id', $cone->id)
            ->orWhere('end_cone_id', $cone->id)
            ->delete();
    }

    #[Override]
    public function getBarricades(Timetable $timetable, SiteBaseMap $siteBaseMap): Collection
    {
        return ConeMapBarricade::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function findBarricadesByIds(Timetable $timetable, SiteBaseMap $siteBaseMap, array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return ConeMapBarricade::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->whereIn('id', $ids)
            ->get();
    }

    #[Override]
    public function findBarricadeById(Timetable $timetable, SiteBaseMap $siteBaseMap, int $id): ?ConeMapBarricade
    {
        return ConeMapBarricade::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->find($id);
    }

    #[Override]
    public function saveBarricade(ConeMapBarricade $barricade): ConeMapBarricade
    {
        $barricade->save();

        return $barricade;
    }

    #[Override]
    public function deleteBarricade(ConeMapBarricade $barricade): bool
    {
        return (bool) $barricade->delete();
    }

    #[Override]
    public function getCallouts(Timetable $timetable, SiteBaseMap $siteBaseMap): Collection
    {
        return ConeMapCallout::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function saveCallout(ConeMapCallout $callout): ConeMapCallout
    {
        $callout->save();

        return $callout;
    }

    #[Override]
    public function deleteCallout(ConeMapCallout $callout): bool
    {
        return (bool) $callout->delete();
    }

    #[Override]
    public function syncCalloutAnchorsForCone(ConeMapCone $cone): void
    {
        ConeMapCallout::query()
            ->where('target_cone_id', $cone->id)
            ->update([
                'anchor_x' => $cone->pos_x,
                'anchor_y' => $cone->pos_y,
            ]);
    }

    #[Override]
    public function syncCalloutAnchorsForBarricade(ConeMapBarricade $barricade): void
    {
        ConeMapCallout::query()
            ->where('target_barricade_id', $barricade->id)
            ->update([
                'anchor_x' => $barricade->pos_x,
                'anchor_y' => $barricade->pos_y,
            ]);
    }

    #[Override]
    public function clearAll(Timetable $timetable, SiteBaseMap $siteBaseMap): void
    {
        ConeMapCallout::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->delete();

        ConeMapBar::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->delete();

        ConeMapBarricade::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->delete();

        ConeMapCone::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->delete();
    }

    #[Override]
    public function countItemsUsingBaseMap(SiteBaseMap $siteBaseMap): int
    {
        return $siteBaseMap->coneMapCones()->count()
            + $siteBaseMap->coneMapBars()->count()
            + $siteBaseMap->coneMapBarricades()->count()
            + $siteBaseMap->coneMapCallouts()->count();
    }

    #[Override]
    public function getInventoryEquipments(): Collection
    {
        return Equipment::query()->ordered()->get();
    }
}
