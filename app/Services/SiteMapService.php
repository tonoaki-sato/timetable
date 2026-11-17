<?php

namespace App\Services;

use App\Models\LayoutItem;
use App\Models\SiteBaseMap;
use App\Models\SiteMapIconDefinition;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Repositories\Contracts\SiteMapRepositoryInterface;
use Database\Seeders\SiteMapStandardPlacementsSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class SiteMapService
{
    public function __construct(
        protected SiteMapRepositoryInterface $repository
    ) {}

    /**
     * エディタ画面用の初期データを一括取得する
     *
     * @return array{
     *   siteBaseMap: SiteBaseMap,
     *   iconDefinitions: Collection<int, SiteMapIconDefinition>,
     *   placements: Collection<int, SiteMapPlacement>,
     *   layoutItems: Collection<int, LayoutItem>
     * }
     */
    public function getEditorData(Timetable $timetable): array
    {
        $siteBaseMap = $this->repository->getOrCreateActiveBaseMap($timetable);

        return [
            'siteBaseMap' => $siteBaseMap,
            'iconDefinitions' => $this->repository->getAllIconDefinitions(),
            'placements' => $this->repository->getPlacementsByBaseMap($siteBaseMap),
            'layoutItems' => $this->repository->getLayoutItemsForTimetable($timetable),
        ];
    }

    /**
     * 運行情報に紐づくアクティブな白地図を取得する
     */
    public function getActiveBaseMap(Timetable $timetable): SiteBaseMap
    {
        return $this->repository->findActiveBaseMapOrFail($timetable);
    }

    /**
     * 白地図のアクティブな配置一覧を取得する
     *
     * @return Collection<int, SiteMapPlacement>
     */
    public function getPlacements(SiteBaseMap $siteBaseMap): Collection
    {
        return $this->repository->getPlacementsByBaseMap($siteBaseMap);
    }

    /**
     * 新規配置を作成する (ドラッグ＆ドロップ配置・複製)
     *
     * @param  array<string, mixed>  $data
     */
    public function createPlacement(SiteBaseMap $siteBaseMap, array $data): SiteMapPlacement
    {
        $shapeType = $data['shape_type'] ?? 'booth_standard';
        $iconDef = $this->repository->findIconDefinitionByKey($shapeType);

        $defaultWidth = $iconDef ? (float) $iconDef->default_width : 13.86;
        $defaultHeight = $iconDef ? (float) $iconDef->default_height : 13.86;
        $defaultColor = $iconDef ? ($iconDef->default_color ?: 'red') : 'red';
        $defaultText = $this->resolveDefaultDisplayText($shapeType, $data['display_text'] ?? null);

        $zIndex = match ($shapeType) {
            'free_area' => 5,
            'area_note' => 8,
            'water_supply' => 30,
            default => 10,
        };

        $placement = new SiteMapPlacement([
            'site_base_map_id' => $siteBaseMap->id,
            'layout_item_id' => $data['layout_item_id'] ?? null,
            'shape_type' => $shapeType,
            'display_text' => $defaultText,
            'font_size' => isset($data['font_size']) ? (float) $data['font_size'] : null,
            'color_theme' => $data['color_theme'] ?? $defaultColor,
            'pos_x' => (float) ($data['pos_x'] ?? 0.0),
            'pos_y' => (float) ($data['pos_y'] ?? 0.0),
            'width' => (float) ($data['width'] ?? $defaultWidth),
            'height' => (float) ($data['height'] ?? $defaultHeight),
            'rotation' => (int) ($data['rotation'] ?? 0),
            'is_rotation_locked' => (bool) ($data['is_rotation_locked'] ?? false),
            'show_hygiene_circle' => (bool) ($data['show_hygiene_circle'] ?? true),
            'arc_start_angle' => (int) ($data['arc_start_angle'] ?? 0),
            'arc_end_angle' => (int) ($data['arc_end_angle'] ?? 360),
            'z_index' => (int) ($data['z_index'] ?? $zIndex),
            'notes' => $data['notes'] ?? null,
        ]);

        return $this->repository->savePlacement($placement);
    }

    /**
     * 配置の位置・寸法・回転・属性を更新する
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
        if (array_key_exists('display_text', $data)) {
            $placement->display_text = (string) ($data['display_text'] ?? '');
        }
        if (array_key_exists('font_size', $data)) {
            $placement->font_size = $data['font_size'] !== null ? (float) $data['font_size'] : null;
        }
        if (isset($data['color_theme'])) {
            $placement->color_theme = (string) $data['color_theme'];
        }
        if (array_key_exists('layout_item_id', $data)) {
            $placement->layout_item_id = ! empty($data['layout_item_id']) ? (int) $data['layout_item_id'] : null;
        }
        if (isset($data['show_hygiene_circle'])) {
            $placement->show_hygiene_circle = (bool) $data['show_hygiene_circle'];
        }
        if (isset($data['arc_start_angle'])) {
            $placement->arc_start_angle = (int) $data['arc_start_angle'];
        }
        if (isset($data['arc_end_angle'])) {
            $placement->arc_end_angle = (int) $data['arc_end_angle'];
        }
        if (isset($data['z_index'])) {
            $placement->z_index = (int) $data['z_index'];
        }
        if (array_key_exists('notes', $data)) {
            $placement->notes = $data['notes'];
        }

        return $this->repository->savePlacement($placement);
    }

    /**
     * 複数配置ノードを一括更新する (一括整列・複数移動・一括カラー変更用)
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return Collection<int, SiteMapPlacement>
     */
    public function bulkUpdatePlacements(SiteBaseMap $siteBaseMap, array $items): Collection
    {
        $ids = collect($items)->pluck('id')->filter()->unique()->values()->all();
        $existingMap = $siteBaseMap->placements()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($items, $existingMap): void {
            foreach ($items as $itemData) {
                $id = (int) ($itemData['id'] ?? 0);
                /** @var SiteMapPlacement|null $placement */
                $placement = $existingMap->get($id);
                if (! $placement) {
                    continue;
                }
                $this->updatePlacementGeometry($placement, $itemData);
            }
        });

        return $this->repository->getPlacementsByBaseMap($siteBaseMap);
    }

    /**
     * 配置を削除する
     */
    public function deletePlacement(SiteMapPlacement $placement): bool
    {
        return $this->repository->deletePlacement($placement);
    }

    /**
     * 白地図上の配置を添付PDFの見本配置状態へ復元する
     *
     * @return Collection<int, SiteMapPlacement>
     */
    public function resetToStandardPreset(SiteBaseMap $siteBaseMap): Collection
    {
        SiteMapStandardPlacementsSeeder::seedForBaseMap($siteBaseMap);

        return $this->repository->getPlacementsByBaseMap($siteBaseMap);
    }

    /**
     * シンボル種別に応じたコンパクトな初期テキストを決定
     */
    protected function resolveDefaultDisplayText(string $shapeType, ?string $givenText): string
    {
        if ($givenText !== null && $givenText !== '') {
            return $givenText;
        }

        return match ($shapeType) {
            'booth_standard' => 'A00',
            'tent_2x15_horiz', 'tent_2x15_vert' => 'テント',
            'tent_3m' => '3M',
            'tent_1x15_vert' => '本陣',
            'kitchen_car' => 'ｷｯﾁﾝｶｰ',
            'free_area' => 'フリー',
            'water_supply' => '水',
            'area_note' => '注記テキスト',
            default => '',
        };
    }
}
