<?php

namespace App\Services;

use App\Models\SiteBaseMap;
use App\Models\SiteMapIconDefinition;
use App\Models\SiteMapPlacement;
use Illuminate\Database\Eloquent\Collection;

class SiteMapService
{
    /**
     * 白地図のアクティブな配置一覧を取得する
     *
     * @return Collection<int, SiteMapPlacement>
     */
    public function getPlacements(SiteBaseMap $siteBaseMap): Collection
    {
        return $siteBaseMap->placements()
            ->with(['layoutItem.vendor'])
            ->get();
    }

    /**
     * 新規配置を作成する (ドラッグ＆ドロップ配置)
     *
     * @param  array<string, mixed>  $data
     */
    public function createPlacement(SiteBaseMap $siteBaseMap, array $data): SiteMapPlacement
    {
        $shapeType = $data['shape_type'] ?? 'general_tent';

        // アイコン定義から既定寸法を取得
        $iconDef = SiteMapIconDefinition::where('icon_key', $shapeType)->first();
        $defaultWidth = $iconDef ? (float) $iconDef->default_width : 78.00;
        $defaultHeight = $iconDef ? (float) $iconDef->default_height : 78.00;

        $placement = new SiteMapPlacement([
            'site_base_map_id' => $siteBaseMap->id,
            'layout_item_id' => $data['layout_item_id'] ?? null,
            'shape_type' => $shapeType,
            'display_text' => $data['display_text'] ?? ($iconDef?->name ?? 'テキスト'),
            'color_theme' => $data['color_theme'] ?? 'red',
            'pos_x' => (float) ($data['pos_x'] ?? 0.0),
            'pos_y' => (float) ($data['pos_y'] ?? 0.0),
            'width' => (float) ($data['width'] ?? $defaultWidth),
            'height' => (float) ($data['height'] ?? $defaultHeight),
            'rotation' => (int) ($data['rotation'] ?? 0),
            'is_rotation_locked' => (bool) ($data['is_rotation_locked'] ?? false),
            'notes' => $data['notes'] ?? null,
        ]);

        $placement->save();

        return $placement->load(['layoutItem.vendor']);
    }

    /**
     * 配置の位置・寸法・回転を更新する
     *
     * @param  array<string, mixed>  $data
     */
    public function updatePlacementGeometry(SiteMapPlacement $placement, array $data): SiteMapPlacement
    {
        if (isset($data['pos_x'])) {
            $placement->pos_x = (float) $data['pos_x'];
        }
        if (isset($data['pos_y'])) {
            $placement->pos_y = (float) $data['pos_y'];
        }
        if (isset($data['width'])) {
            $placement->width = (float) $data['width'];
        }
        if (isset($data['height'])) {
            $placement->height = (float) $data['height'];
        }
        if (isset($data['rotation'])) {
            $placement->rotation = (int) $data['rotation'];
        }
        if (isset($data['is_rotation_locked'])) {
            $placement->is_rotation_locked = (bool) $data['is_rotation_locked'];
        }

        $placement->save();

        return $placement->load(['layoutItem.vendor']);
    }

    /**
     * 配置を削除する
     */
    public function deletePlacement(SiteMapPlacement $placement): bool
    {
        return (bool) $placement->delete();
    }
}
