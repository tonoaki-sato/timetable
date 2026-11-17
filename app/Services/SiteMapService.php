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
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

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
     * PNG画像からカスタムアイコン定義を新規生成・保存する
     *
     * @param  array<string, mixed>  $data
     */
    public function storeIconFromPng(Timetable $timetable, array $data, UploadedFile $file): SiteMapIconDefinition
    {
        $baseMap = $this->getActiveBaseMap($timetable);
        $scaleRatio = (float) $baseMap->scale_ratio;

        $targetDir = public_path('maps/icons/custom');
        File::ensureDirectoryExists($targetDir, 0777, true);

        $filename = 'custom_'.time().'_'.Str::lower(Str::random(8)).'.png';
        $file->move($targetDir, $filename);
        @chmod($targetDir.'/'.$filename, 0666);

        $widthM = (float) ($data['width_meters'] ?? 2.0);
        $heightM = (float) ($data['height_meters'] ?? 2.0);
        $defaultWidthPt = round($widthM * $scaleRatio, 2);
        $defaultHeightPt = round($heightM * $scaleRatio, 2);

        $iconKey = ! empty($data['icon_key']) ? (string) $data['icon_key'] : ('custom_'.Str::lower(Str::random(10)));

        return $this->repository->createIconDefinition([
            'icon_key' => $iconKey,
            'name' => (string) $data['name'],
            'category' => (string) ($data['category'] ?? 'other'),
            'render_style' => 'image',
            'default_width' => $defaultWidthPt,
            'default_height' => $defaultHeightPt,
            'is_resizable' => (bool) ($data['is_resizable'] ?? false),
            'is_rotatable' => (bool) ($data['is_rotatable'] ?? true),
            'rotation_step' => (int) ($data['rotation_step'] ?? 45),
            'supported_colors' => ['red', 'green', 'black', 'cyan', 'orange', 'blue'],
            'default_color' => (string) ($data['default_color'] ?? 'red'),
            'has_hygiene_circle' => (bool) ($data['has_hygiene_circle'] ?? false),
            'hygiene_radius' => 138.00,
            'is_hygiene_target' => (bool) ($data['is_hygiene_target'] ?? false),
            'is_preset' => false,
            'icon_asset_path' => 'maps/icons/custom/'.$filename,
        ]);
    }

    /**
     * カスタムアイコン定義を更新する (画像差し替え対応)
     *
     * @param  array<string, mixed>  $data
     */
    public function updateIcon(Timetable $timetable, SiteMapIconDefinition $iconDefinition, array $data, ?UploadedFile $file = null): SiteMapIconDefinition
    {
        if ($iconDefinition->is_preset) {
            throw new \DomainException('プリセット初期定義アイコンは編集できません。');
        }

        $baseMap = $this->getActiveBaseMap($timetable);
        $scaleRatio = (float) $baseMap->scale_ratio;

        $updatePayload = [
            'name' => (string) ($data['name'] ?? $iconDefinition->name),
            'category' => (string) ($data['category'] ?? $iconDefinition->category),
        ];

        if (isset($data['width_meters'])) {
            $updatePayload['default_width'] = round(((float) $data['width_meters']) * $scaleRatio, 2);
        }
        if (isset($data['height_meters'])) {
            $updatePayload['default_height'] = round(((float) $data['height_meters']) * $scaleRatio, 2);
        }
        if (isset($data['is_resizable'])) {
            $updatePayload['is_resizable'] = (bool) $data['is_resizable'];
        }
        if (isset($data['is_rotatable'])) {
            $updatePayload['is_rotatable'] = (bool) $data['is_rotatable'];
        }
        if (isset($data['rotation_step'])) {
            $updatePayload['rotation_step'] = (int) $data['rotation_step'];
        }
        if (isset($data['has_hygiene_circle'])) {
            $updatePayload['has_hygiene_circle'] = (bool) $data['has_hygiene_circle'];
        }
        if (isset($data['is_hygiene_target'])) {
            $updatePayload['is_hygiene_target'] = (bool) $data['is_hygiene_target'];
        }
        if (isset($data['default_color'])) {
            $updatePayload['default_color'] = (string) $data['default_color'];
        }

        if ($file !== null) {
            $targetDir = public_path('maps/icons/custom');
            File::ensureDirectoryExists($targetDir, 0777, true);

            $newFilename = 'custom_'.time().'_'.Str::lower(Str::random(8)).'.png';
            $file->move($targetDir, $newFilename);
            @chmod($targetDir.'/'.$newFilename, 0666);

            // 旧カスタム画像の削除 (プリセット画像は保護)
            $oldPath = $iconDefinition->icon_asset_path;
            if ($oldPath && Str::startsWith($oldPath, 'maps/icons/custom/')) {
                File::delete(public_path($oldPath));
            }

            $updatePayload['icon_asset_path'] = 'maps/icons/custom/'.$newFilename;
        }

        return $this->repository->updateIconDefinition($iconDefinition, $updatePayload);
    }

    /**
     * カスタムアイコン定義を削除する (関連配置の連動削除対応)
     *
     * @return array{deleted: bool, placements_deleted: int}
     */
    public function deleteIcon(SiteMapIconDefinition $iconDefinition, bool $force = false): array
    {
        if ($iconDefinition->is_preset) {
            throw new \DomainException('プリセット初期定義アイコンは削除できません。');
        }

        $usageCount = $this->repository->countPlacementsUsingIcon($iconDefinition->icon_key);
        if ($usageCount > 0 && ! $force) {
            throw new \DomainException("このアイコンは白地図上で {$usageCount} 件使用されています。削除するには一括削除フラグを指定してください。");
        }

        $deletedPlacements = 0;
        DB::transaction(function () use ($iconDefinition, $usageCount, &$deletedPlacements): void {
            if ($usageCount > 0) {
                $deletedPlacements = $this->repository->deletePlacementsByShapeType($iconDefinition->icon_key);
            }

            // 物理ファイルの削除
            $assetPath = $iconDefinition->icon_asset_path;
            if ($assetPath && Str::startsWith($assetPath, 'maps/icons/custom/')) {
                File::delete(public_path($assetPath));
            }

            $this->repository->deleteIconDefinition($iconDefinition);
        });

        return [
            'deleted' => true,
            'placements_deleted' => $deletedPlacements,
        ];
    }

    /**
     * 指定アイコンが白地図上で使用されている件数を取得
     */
    public function getIconUsageCount(string $iconKey): int
    {
        return $this->repository->countPlacementsUsingIcon($iconKey);
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
            'booth_with_tent', 'booth_standard' => 'x00',
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
