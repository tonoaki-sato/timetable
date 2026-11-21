<?php

namespace App\Repositories\Eloquent;

use App\Models\SiteBaseMap;
use App\Models\SiteMapIconDefinition;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Repositories\Contracts\SiteMapRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Override;

class SiteMapRepository implements SiteMapRepositoryInterface
{
    #[Override]
    public function getAllBaseMapsWithCounts(): Collection
    {
        return SiteBaseMap::query()
            ->withCount(['timetables', 'placements'])
            ->orderByDesc('is_default')
            ->orderByDesc('is_active')
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function getActiveBaseMaps(): Collection
    {
        return SiteBaseMap::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();
    }

    #[Override]
    public function findBaseMapById(int $id): ?SiteBaseMap
    {
        return SiteBaseMap::find($id);
    }

    #[Override]
    public function getOrCreateDefaultBaseMap(): SiteBaseMap
    {
        $siteBaseMap = SiteBaseMap::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (! $siteBaseMap) {
            $siteBaseMap = SiteBaseMap::create([
                'name' => '保土ヶ谷宿場まつり 平面図 1/400 A3',
                'cad_version' => '260927',
                'paper_size' => 'A3',
                'scale_ratio' => 7.0866,
                'width_meters' => 118.80,
                'height_meters' => 168.00,
                'svg_path' => 'maps/shopping_street_base_map_260813.svg',
                'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
                'viewbox' => '0 0 842 1191',
                'is_default' => true,
                'is_active' => true,
            ]);
        }

        return $siteBaseMap;
    }

    #[Override]
    public function resolveBaseMapForTimetable(Timetable $timetable): SiteBaseMap
    {
        if ($timetable->site_base_map_id !== null) {
            $assigned = $timetable->siteBaseMap()->first();
            if ($assigned !== null) {
                return $assigned;
            }
        }

        return $this->getOrCreateDefaultBaseMap();
    }

    #[Override]
    public function findBaseMapForTimetableOrFail(Timetable $timetable): SiteBaseMap
    {
        if ($timetable->site_base_map_id !== null) {
            $assigned = $timetable->siteBaseMap()->first();
            if ($assigned !== null) {
                return $assigned;
            }
        }

        return SiteBaseMap::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->firstOrFail();
    }

    #[Override]
    public function createBaseMap(array $data): SiteBaseMap
    {
        return SiteBaseMap::create($data);
    }

    #[Override]
    public function updateBaseMap(SiteBaseMap $siteBaseMap, array $data): SiteBaseMap
    {
        $siteBaseMap->update($data);

        return $siteBaseMap->fresh();
    }

    #[Override]
    public function deleteBaseMap(SiteBaseMap $siteBaseMap): bool
    {
        return (bool) $siteBaseMap->delete();
    }

    #[Override]
    public function clearDefaultFlagExcept(?int $exceptId = null): void
    {
        SiteBaseMap::query()
            ->when($exceptId !== null, fn ($q) => $q->where('id', '!=', $exceptId))
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    #[Override]
    public function countTimetablesUsingBaseMap(SiteBaseMap $siteBaseMap): int
    {
        return $siteBaseMap->timetables()->count();
    }

    #[Override]
    public function countPlacementsUsingBaseMap(SiteBaseMap $siteBaseMap): int
    {
        return $siteBaseMap->placements()->count();
    }

    #[Override]
    public function getAllIconDefinitions(): Collection
    {
        return SiteMapIconDefinition::all()
            ->sortBy([
                fn (SiteMapIconDefinition $a, SiteMapIconDefinition $b) => $a->getPaletteOrderWeight() <=> $b->getPaletteOrderWeight(),
                fn (SiteMapIconDefinition $a, SiteMapIconDefinition $b) => $a->id <=> $b->id,
            ])
            ->values();
    }

    #[Override]
    public function findIconDefinitionByKey(string $iconKey): ?SiteMapIconDefinition
    {
        return SiteMapIconDefinition::where('icon_key', $iconKey)->first();
    }

    #[Override]
    public function getPlacements(Timetable $timetable, SiteBaseMap $siteBaseMap): Collection
    {
        return SiteMapPlacement::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->with(['layoutItem.vendor'])
            ->orderBy('z_index', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    #[Override]
    public function findPlacementsByIds(Timetable $timetable, SiteBaseMap $siteBaseMap, array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return SiteMapPlacement::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->whereIn('id', $ids)
            ->get();
    }

    #[Override]
    public function savePlacement(SiteMapPlacement $placement, bool $withRelations = true): SiteMapPlacement
    {
        $placement->save();

        if (! $withRelations) {
            return $placement;
        }

        return $placement->load(['layoutItem.vendor']);
    }

    #[Override]
    public function deletePlacement(SiteMapPlacement $placement): bool
    {
        return (bool) $placement->delete();
    }

    #[Override]
    public function clearPlacements(Timetable $timetable, SiteBaseMap $siteBaseMap): bool
    {
        SiteMapPlacement::query()
            ->where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $siteBaseMap->id)
            ->delete();

        return true;
    }

    #[Override]
    public function getLayoutItemsForTimetable(Timetable $timetable): Collection
    {
        return $timetable->layoutItems()
            ->with(['vendor', 'area'])
            ->orderBy('code', 'asc')
            ->get();
    }

    #[Override]
    public function createIconDefinition(array $data): SiteMapIconDefinition
    {
        return SiteMapIconDefinition::create($data);
    }

    #[Override]
    public function updateIconDefinition(SiteMapIconDefinition $iconDefinition, array $data): SiteMapIconDefinition
    {
        $iconDefinition->update($data);

        return $iconDefinition->fresh();
    }

    #[Override]
    public function deleteIconDefinition(SiteMapIconDefinition $iconDefinition): bool
    {
        return (bool) $iconDefinition->delete();
    }

    #[Override]
    public function countPlacementsUsingIcon(string $iconKey): int
    {
        return SiteMapPlacement::where('shape_type', $iconKey)->count();
    }

    #[Override]
    public function deletePlacementsByShapeType(string $shapeType): int
    {
        return SiteMapPlacement::where('shape_type', $shapeType)->delete();
    }
}
