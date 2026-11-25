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

        $this->cloneConeMaps($source, $newTimetable, $now);

        return [$layoutItemsCount, $placementsCount];
    }

    /**
     * カラーコーン・コーンバー・バリケード・吹き出し配置を ID マッピングを維持して複製
     */
    protected function cloneConeMaps(Timetable $source, Timetable $newTimetable, string $now): void
    {
        $coneIdMap = [];
        foreach ($this->cloneRepository->getConeMapCones($source->id) as $sourceCone) {
            $newCone = $this->cloneRepository->createConeMapCone([
                'timetable_id' => $newTimetable->id,
                'site_base_map_id' => $sourceCone->site_base_map_id,
                'pos_x' => $sourceCone->pos_x,
                'pos_y' => $sourceCone->pos_y,
                'color' => $sourceCone->color,
                'label' => $sourceCone->label,
                'group_key' => $sourceCone->group_key,
                'notes' => $sourceCone->notes,
            ]);
            $coneIdMap[$sourceCone->id] = $newCone->id;
        }

        $barRows = [];
        foreach ($this->cloneRepository->getConeMapBars($source->id) as $sourceBar) {
            $newStartId = $coneIdMap[$sourceBar->start_cone_id] ?? null;
            $newEndId = $coneIdMap[$sourceBar->end_cone_id] ?? null;
            if ($newStartId === null || $newEndId === null) {
                continue;
            }
            $barRows[] = [
                'timetable_id' => $newTimetable->id,
                'site_base_map_id' => $sourceBar->site_base_map_id,
                'start_cone_id' => $newStartId,
                'end_cone_id' => $newEndId,
                'bar_style' => $sourceBar->bar_style,
                'length_cm' => $sourceBar->length_cm,
                'diameter_cm' => $sourceBar->diameter_cm,
                'group_key' => $sourceBar->group_key,
                'notes' => $sourceBar->notes,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        $this->cloneRepository->insertConeMapBars($barRows);

        $barricadeIdMap = [];
        foreach ($this->cloneRepository->getConeMapBarricades($source->id) as $sourceBarricade) {
            $newBarricade = $this->cloneRepository->createConeMapBarricade([
                'timetable_id' => $newTimetable->id,
                'site_base_map_id' => $sourceBarricade->site_base_map_id,
                'barricade_type' => $sourceBarricade->barricade_type,
                'pos_x' => $sourceBarricade->pos_x,
                'pos_y' => $sourceBarricade->pos_y,
                'rotation' => $sourceBarricade->rotation,
                'label' => $sourceBarricade->label,
                'notes' => $sourceBarricade->notes,
            ]);
            $barricadeIdMap[$sourceBarricade->id] = $newBarricade->id;
        }

        $calloutRows = [];
        foreach ($this->cloneRepository->getConeMapCallouts($source->id) as $sourceCallout) {
            $calloutRows[] = [
                'timetable_id' => $newTimetable->id,
                'site_base_map_id' => $sourceCallout->site_base_map_id,
                'target_cone_id' => $sourceCallout->target_cone_id !== null
                    ? ($coneIdMap[$sourceCallout->target_cone_id] ?? null)
                    : null,
                'target_barricade_id' => $sourceCallout->target_barricade_id !== null
                    ? ($barricadeIdMap[$sourceCallout->target_barricade_id] ?? null)
                    : null,
                'anchor_x' => $sourceCallout->anchor_x,
                'anchor_y' => $sourceCallout->anchor_y,
                'box_x' => $sourceCallout->box_x,
                'box_y' => $sourceCallout->box_y,
                'box_width' => $sourceCallout->box_width,
                'box_height' => $sourceCallout->box_height,
                'title' => $sourceCallout->title,
                'body' => $sourceCallout->body,
                'color_theme' => $sourceCallout->color_theme,
                'font_size' => $sourceCallout->font_size,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        $this->cloneRepository->insertConeMapCallouts($calloutRows);
    }
}
