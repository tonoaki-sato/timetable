<?php

namespace App\Actions\Timetable\Cloners;

use App\Enums\LayoutItemStatus;
use App\Models\Timetable;
use App\Models\User;
use App\Repositories\Contracts\TimetableCloneRepositoryInterface;

class TimetableSiteLayoutCloner
{
    public function __construct(
        protected TimetableCloneRepositoryInterface $cloneRepository,
    ) {}

    /**
     * 現場配置計画（LayoutMap / LayoutItem）および白地図配置（SiteMapPlacement）を複製
     *
     * @return array{0: int, 1: int}
     */
    public function cloneSiteLayouts(
        Timetable $source,
        Timetable $newTimetable,
        ?User $creator
    ): array {
        $layoutItemsCount = 0;
        $placementsCount = 0;
        $layoutItemIdMap = [];

        $sourceMaps = $this->cloneRepository->getLayoutMapsWithItems($source->id);

        foreach ($sourceMaps as $sourceMap) {
            $newMap = $this->cloneRepository->createLayoutMap([
                'timetable_id' => $newTimetable->id,
                'name' => $sourceMap->name,
                'version_tag' => $sourceMap->version_tag,
                'notes' => $sourceMap->notes,
                'created_by' => $creator?->id ?? $sourceMap->created_by,
            ]);

            foreach ($sourceMap->items as $sourceItem) {
                $newItem = $this->cloneRepository->createLayoutItem([
                    'layout_map_id' => $newMap->id,
                    'layout_area_id' => $sourceItem->layout_area_id,
                    'item_type' => $sourceItem->item_type,
                    'code' => $sourceItem->code,
                    'name' => $sourceItem->name,
                    'status' => LayoutItemStatus::Tentative,
                    'category' => $sourceItem->category,
                    'booth_type' => $sourceItem->booth_type,
                    'vendor_id' => $sourceItem->vendor_id,
                    'location_description' => $sourceItem->location_description,
                    'notes' => $sourceItem->notes,
                    'display_order' => $sourceItem->display_order,
                ]);

                $layoutItemIdMap[$sourceItem->id] = $newItem->id;
                $layoutItemsCount++;
            }
        }

        if ($source->site_base_map_id !== null) {
            $this->cloneRepository->updateTimetableBaseMap($newTimetable, (int) $source->site_base_map_id);
        }

        $sourcePlacements = $this->cloneRepository->getSiteMapPlacements($source->id);

        $placementRows = [];
        $now = now()->format('Y-m-d H:i:s');

        foreach ($sourcePlacements as $sourcePlacement) {
            $newLayoutItemId = $sourcePlacement->layout_item_id
                ? ($layoutItemIdMap[$sourcePlacement->layout_item_id] ?? null)
                : null;

            $placementRows[] = [
                'timetable_id' => $newTimetable->id,
                'site_base_map_id' => $sourcePlacement->site_base_map_id,
                'layout_item_id' => $newLayoutItemId,
                'shape_type' => $sourcePlacement->shape_type,
                'display_text' => $sourcePlacement->display_text,
                'font_size' => $sourcePlacement->font_size,
                'color_theme' => $sourcePlacement->color_theme,
                'pos_x' => $sourcePlacement->pos_x,
                'pos_y' => $sourcePlacement->pos_y,
                'width' => $sourcePlacement->width,
                'height' => $sourcePlacement->height,
                'rotation' => $sourcePlacement->rotation,
                'is_rotation_locked' => (bool) $sourcePlacement->is_rotation_locked,
                'show_hygiene_circle' => (bool) $sourcePlacement->show_hygiene_circle,
                'arc_start_angle' => $sourcePlacement->arc_start_angle,
                'arc_end_angle' => $sourcePlacement->arc_end_angle,
                'z_index' => $sourcePlacement->z_index,
                'notes' => $sourcePlacement->notes,
                'meta_data' => $sourcePlacement->meta_data !== null
                    ? json_encode($sourcePlacement->meta_data, JSON_UNESCAPED_UNICODE)
                    : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $placementsCount++;
        }

        $this->cloneRepository->insertSiteMapPlacements($placementRows);

        return [$layoutItemsCount, $placementsCount];
    }
}
