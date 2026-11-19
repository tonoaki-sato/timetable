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
    public function getOrCreateActiveBaseMap(Timetable $timetable): SiteBaseMap
    {
        $siteBaseMap = $timetable->siteBaseMaps()
            ->where('is_active', true)
            ->first();

        if (! $siteBaseMap) {
            $siteBaseMap = $timetable->siteBaseMaps()->create([
                'name' => '保土ヶ谷宿場まつり 平面図 1/400 A3',
                'cad_version' => '260927',
                'paper_size' => 'A3',
                'scale_ratio' => 7.0866,
                'width_meters' => 118.8,
                'height_meters' => 168.0,
                'svg_path' => 'maps/shopping_street_base_map_260813.svg',
                'pdf_path' => 'maps/shopping_street_base_map_260813.pdf',
                'viewbox' => '0 0 842 1191',
                'is_active' => true,
            ]);
        }

        return $siteBaseMap;
    }

    #[Override]
    public function findActiveBaseMapOrFail(Timetable $timetable): SiteBaseMap
    {
        return $timetable->siteBaseMaps()
            ->where('is_active', true)
            ->firstOrFail();
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
    public function getPlacementsByBaseMap(SiteBaseMap $siteBaseMap): Collection
    {
        return $siteBaseMap->placements()
            ->with(['layoutItem.vendor'])
            ->orderBy('z_index', 'asc')
            ->orderBy('id', 'asc')
            ->get();
    }

    #[Override]
    public function findPlacementsByIds(SiteBaseMap $siteBaseMap, array $ids): Collection
    {
        if ($ids === []) {
            return new Collection;
        }

        return $siteBaseMap->placements()
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
    public function clearPlacementsByBaseMap(SiteBaseMap $siteBaseMap): bool
    {
        $siteBaseMap->placements()->delete();

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
