<?php

namespace App\Services;

use App\Models\SiteBaseMap;
use App\Repositories\Contracts\ConeMapRepositoryInterface;
use App\Repositories\Contracts\SiteMapRepositoryInterface;
use App\Services\SiteBaseMap\SiteBaseMapAssetStorage;
use DomainException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

class SiteBaseMapService
{
    public function __construct(
        protected SiteMapRepositoryInterface $repository,
        protected SiteBaseMapAssetStorage $assetStorage,
        protected ConeMapRepositoryInterface $coneMapRepository,
    ) {}

    /**
     * 白地図マスター一覧画面用データを取得
     *
     * @return array{
     *     baseMaps: Collection<int, SiteBaseMap>,
     *     existingSvgPaths: array<int, string>,
     *     existingPdfPaths: array<int, string>
     * }
     */
    public function getIndexViewData(): array
    {
        return [
            'baseMaps' => $this->repository->getAllBaseMapsWithCounts(),
            'existingSvgPaths' => $this->assetStorage->scanMapAssetPaths('svg'),
            'existingPdfPaths' => $this->assetStorage->scanMapAssetPaths('pdf'),
        ];
    }

    /**
     * 白地図マスターを新規登録
     *
     * @param  array<string, mixed>  $data
     */
    public function createBaseMap(array $data, ?UploadedFile $svgFile = null, ?UploadedFile $pdfFile = null): SiteBaseMap
    {
        $createdCustomFiles = [];

        try {
            $svgPath = ! empty($data['svg_path']) ? (string) $data['svg_path'] : null;
            $viewbox = isset($data['viewbox']) && trim((string) $data['viewbox']) !== ''
                ? trim((string) $data['viewbox'])
                : null;

            if ($svgFile !== null) {
                $svgResult = $this->assetStorage->storeSanitizedSvg($svgFile);
                $svgPath = $svgResult['path'];
                $createdCustomFiles[] = $svgPath;
                $viewbox ??= $svgResult['viewbox'];
            }

            if ($svgPath === null || $svgPath === '') {
                throw new DomainException('SVGファイルまたはSVGファイルパスを指定してください。');
            }

            $viewbox ??= '0 0 842 1191';

            $pdfPath = ! empty($data['pdf_path']) ? (string) $data['pdf_path'] : null;
            if ($pdfFile !== null) {
                $pdfPath = $this->assetStorage->storePdf($pdfFile);
                $createdCustomFiles[] = $pdfPath;
            }

            return DB::transaction(function () use ($data, $svgPath, $pdfPath, $viewbox): SiteBaseMap {
                $hasAnyDefault = $this->repository->hasDefaultBaseMap();
                $isDefault = (bool) ($data['is_default'] ?? false) || ! $hasAnyDefault;
                $isActive = $isDefault ? true : (bool) ($data['is_active'] ?? true);

                if ($isDefault) {
                    $this->repository->clearDefaultFlagExcept();
                }

                return $this->repository->createBaseMap([
                    'name' => (string) $data['name'],
                    'cad_version' => (string) $data['cad_version'],
                    'paper_size' => (string) $data['paper_size'],
                    'scale_ratio' => (float) $data['scale_ratio'],
                    'width_meters' => (float) $data['width_meters'],
                    'height_meters' => (float) $data['height_meters'],
                    'svg_path' => $svgPath,
                    'pdf_path' => $pdfPath,
                    'viewbox' => $viewbox,
                    'is_default' => $isDefault,
                    'is_active' => $isActive,
                    'notes' => $data['notes'] ?? null,
                ]);
            });
        } catch (Throwable $e) {
            foreach ($createdCustomFiles as $relativePath) {
                $this->assetStorage->deleteCustomAssetIfUnused($relativePath);
            }
            throw $e;
        }
    }

    /**
     * 白地図マスターを更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBaseMap(
        SiteBaseMap $siteBaseMap,
        array $data,
        ?UploadedFile $svgFile = null,
        ?UploadedFile $pdfFile = null
    ): SiteBaseMap {
        $createdCustomFiles = [];
        $oldFilesToDelete = [];

        try {
            $svgPath = ! empty($data['svg_path']) ? (string) $data['svg_path'] : (string) $siteBaseMap->svg_path;
            $explicitViewbox = isset($data['viewbox']) && trim((string) $data['viewbox']) !== ''
                ? trim((string) $data['viewbox'])
                : null;
            $viewbox = $explicitViewbox ?? (string) $siteBaseMap->viewbox;

            if ($svgFile !== null) {
                $svgResult = $this->assetStorage->storeSanitizedSvg($svgFile);
                if ($svgResult['path'] !== $siteBaseMap->svg_path) {
                    $oldFilesToDelete[] = (string) $siteBaseMap->svg_path;
                }
                $svgPath = $svgResult['path'];
                $createdCustomFiles[] = $svgPath;
                if ($explicitViewbox === null) {
                    $viewbox = $svgResult['viewbox'];
                }
            } elseif (! empty($data['svg_path']) && $data['svg_path'] !== $siteBaseMap->svg_path) {
                $oldFilesToDelete[] = (string) $siteBaseMap->svg_path;
            }

            $pdfPath = $siteBaseMap->pdf_path;
            if ($pdfFile !== null) {
                $newPdfPath = $this->assetStorage->storePdf($pdfFile);
                if ($siteBaseMap->pdf_path && $newPdfPath !== $siteBaseMap->pdf_path) {
                    $oldFilesToDelete[] = (string) $siteBaseMap->pdf_path;
                }
                $pdfPath = $newPdfPath;
                $createdCustomFiles[] = $newPdfPath;
            } elseif (array_key_exists('pdf_path', $data)) {
                $requestedPdfPath = ! empty($data['pdf_path']) ? (string) $data['pdf_path'] : null;
                if ($siteBaseMap->pdf_path && $requestedPdfPath !== $siteBaseMap->pdf_path) {
                    $oldFilesToDelete[] = (string) $siteBaseMap->pdf_path;
                }
                $pdfPath = $requestedPdfPath;
            }

            $updated = DB::transaction(function () use ($siteBaseMap, $data, $svgPath, $pdfPath, $viewbox): SiteBaseMap {
                $wantDefault = array_key_exists('is_default', $data)
                    ? (bool) $data['is_default']
                    : (bool) $siteBaseMap->is_default;
                $wantActive = array_key_exists('is_active', $data)
                    ? (bool) $data['is_active']
                    : (bool) $siteBaseMap->is_active;

                if ($wantDefault) {
                    $wantActive = true;
                    $this->repository->clearDefaultFlagExcept($siteBaseMap->id);
                } elseif ($siteBaseMap->is_default && (! $wantDefault || ! $wantActive)) {
                    $fallback = $this->repository->findFallbackActiveBaseMap($siteBaseMap->id);

                    if (! $fallback) {
                        throw new DomainException('他に有効な白地図が存在しないため、デフォルト白地図を無効化または解除できません。');
                    }

                    $this->repository->updateBaseMap($fallback, ['is_default' => true]);
                    $wantDefault = false;
                }

                return $this->repository->updateBaseMap($siteBaseMap, [
                    'name' => (string) ($data['name'] ?? $siteBaseMap->name),
                    'cad_version' => (string) ($data['cad_version'] ?? $siteBaseMap->cad_version),
                    'paper_size' => (string) ($data['paper_size'] ?? $siteBaseMap->paper_size),
                    'scale_ratio' => isset($data['scale_ratio']) ? (float) $data['scale_ratio'] : (float) $siteBaseMap->scale_ratio,
                    'width_meters' => isset($data['width_meters']) ? (float) $data['width_meters'] : (float) $siteBaseMap->width_meters,
                    'height_meters' => isset($data['height_meters']) ? (float) $data['height_meters'] : (float) $siteBaseMap->height_meters,
                    'svg_path' => $svgPath,
                    'pdf_path' => $pdfPath,
                    'viewbox' => $viewbox,
                    'is_default' => $wantDefault,
                    'is_active' => $wantActive,
                    'notes' => array_key_exists('notes', $data) ? $data['notes'] : $siteBaseMap->notes,
                ]);
            });

            foreach ($oldFilesToDelete as $oldPath) {
                $this->assetStorage->deleteCustomAssetIfUnused($oldPath);
            }

            return $updated;
        } catch (Throwable $e) {
            foreach ($createdCustomFiles as $relativePath) {
                $this->assetStorage->deleteCustomAssetIfUnused($relativePath);
            }
            throw $e;
        }
    }

    /**
     * 白地図マスターを削除
     */
    public function deleteBaseMap(SiteBaseMap $siteBaseMap): void
    {
        if ($siteBaseMap->is_default) {
            throw new DomainException('デフォルト白地図は削除できません。先に別の白地図をデフォルトに設定してください。');
        }

        if (
            $this->repository->countTimetablesUsingBaseMap($siteBaseMap) > 0
            || $this->repository->countPlacementsUsingBaseMap($siteBaseMap) > 0
            || $this->coneMapRepository->countItemsUsingBaseMap($siteBaseMap) > 0
        ) {
            throw new DomainException('この白地図は運行日程または配置データで使用中のため削除できません。');
        }

        $svgPath = (string) $siteBaseMap->svg_path;
        $pdfPath = $siteBaseMap->pdf_path ? (string) $siteBaseMap->pdf_path : null;

        $this->repository->deleteBaseMap($siteBaseMap);

        $this->assetStorage->deleteCustomAssetIfUnused($svgPath);
        if ($pdfPath !== null) {
            $this->assetStorage->deleteCustomAssetIfUnused($pdfPath);
        }
    }
}
