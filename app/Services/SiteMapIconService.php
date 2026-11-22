<?php

namespace App\Services;

use App\Models\SiteMapIconDefinition;
use App\Models\Timetable;
use App\Repositories\Contracts\SiteMapRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SiteMapIconService
{
    public function __construct(
        protected SiteMapRepositoryInterface $repository,
    ) {}

    /**
     * PNG画像からカスタムアイコン定義を新規生成・保存する
     *
     * @param  array<string, mixed>  $data
     */
    public function storeIconFromPng(Timetable $timetable, array $data, UploadedFile $file): SiteMapIconDefinition
    {
        $baseMap = $this->repository->findBaseMapForTimetableOrFail($timetable);
        $scaleRatio = (float) $baseMap->scale_ratio;

        $relativePath = $this->storeCustomIconPng($file);

        $widthM = (float) ($data['width_meters'] ?? 2.0);
        $heightM = (float) ($data['height_meters'] ?? 2.0);
        $defaultWidthPt = round($widthM * $scaleRatio, 2);
        $defaultHeightPt = round($heightM * $scaleRatio, 2);

        $iconKey = ! empty($data['icon_key']) ? (string) $data['icon_key'] : ('custom_'.Str::lower(Str::random(10)));

        try {
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
                'icon_asset_path' => $relativePath,
            ]);
        } catch (\Throwable $e) {
            $this->deleteCustomIconAssetIfOwned($relativePath);
            throw $e;
        }
    }

    /**
     * カスタムアイコン定義を更新する (画像差し替え対応)
     *
     * @param  array<string, mixed>  $data
     */
    public function updateIcon(
        Timetable $timetable,
        SiteMapIconDefinition $iconDefinition,
        array $data,
        ?UploadedFile $file = null
    ): SiteMapIconDefinition {
        if ($iconDefinition->is_preset) {
            throw new \DomainException('プリセット初期定義アイコンは編集できません。');
        }

        $baseMap = $this->repository->findBaseMapForTimetableOrFail($timetable);
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

        $newRelativePath = null;
        $oldPath = $iconDefinition->icon_asset_path;
        if ($file !== null) {
            $newRelativePath = $this->storeCustomIconPng($file);
            $updatePayload['icon_asset_path'] = $newRelativePath;
        }

        try {
            $updated = $this->repository->updateIconDefinition($iconDefinition, $updatePayload);
        } catch (\Throwable $e) {
            if ($newRelativePath !== null) {
                $this->deleteCustomIconAssetIfOwned($newRelativePath);
            }
            throw $e;
        }

        if ($newRelativePath !== null && $oldPath !== null && $oldPath !== $newRelativePath) {
            $this->deleteCustomIconAssetIfOwned($oldPath);
        }

        return $updated;
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

        $assetPath = $iconDefinition->icon_asset_path;
        $deletedPlacements = 0;

        DB::transaction(function () use ($iconDefinition, $usageCount, &$deletedPlacements): void {
            if ($usageCount > 0) {
                $deletedPlacements = $this->repository->deletePlacementsByShapeType($iconDefinition->icon_key);
            }

            $this->repository->deleteIconDefinition($iconDefinition);
        });

        $this->deleteCustomIconAssetIfOwned($assetPath);

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
     * アップロードされたカスタムアイコンPNG画像を public/maps/icons/custom に保存し相対パスを返却
     */
    private function storeCustomIconPng(UploadedFile $file): string
    {
        $targetDir = public_path('maps/icons/custom');
        File::ensureDirectoryExists($targetDir, 0777, true);

        $filename = 'custom_'.time().'_'.Str::lower(Str::random(8)).'.png';
        $file->move($targetDir, $filename);
        @chmod($targetDir.'/'.$filename, 0666);

        return 'maps/icons/custom/'.$filename;
    }

    /**
     * カスタムアイコン画像のみ安全に物理削除（プリセット画像は保護）
     */
    private function deleteCustomIconAssetIfOwned(?string $assetPath): void
    {
        if ($assetPath && Str::startsWith($assetPath, 'maps/icons/custom/')) {
            File::delete(public_path($assetPath));
        }
    }
}
