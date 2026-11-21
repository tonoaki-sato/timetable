<?php

namespace App\Services;

use App\Models\SiteBaseMap;
use App\Repositories\Contracts\SiteMapRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SiteBaseMapService
{
    public function __construct(
        protected SiteMapRepositoryInterface $repository,
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
            'existingSvgPaths' => $this->scanMapAssetPaths('svg'),
            'existingPdfPaths' => $this->scanMapAssetPaths('pdf'),
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
                $svgResult = $this->sanitizeAndStoreSvg($svgFile);
                $svgPath = $svgResult['path'];
                $createdCustomFiles[] = $svgPath;
                $viewbox ??= $svgResult['viewbox'];
            }

            if ($svgPath === null || $svgPath === '') {
                throw new \DomainException('SVGファイルまたはSVGファイルパスを指定してください。');
            }

            $viewbox ??= '0 0 842 1191';

            $pdfPath = ! empty($data['pdf_path']) ? (string) $data['pdf_path'] : null;
            if ($pdfFile !== null) {
                $pdfPath = $this->storeUploadedPdf($pdfFile);
                $createdCustomFiles[] = $pdfPath;
            }

            return DB::transaction(function () use ($data, $svgPath, $pdfPath, $viewbox): SiteBaseMap {
                $hasAnyDefault = SiteBaseMap::where('is_default', true)->exists();
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
        } catch (\Throwable $e) {
            foreach ($createdCustomFiles as $relativePath) {
                $this->deleteCustomAssetIfOwned($relativePath);
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
                $svgResult = $this->sanitizeAndStoreSvg($svgFile);
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
                $newPdfPath = $this->storeUploadedPdf($pdfFile);
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
                    $fallback = SiteBaseMap::query()
                        ->where('id', '!=', $siteBaseMap->id)
                        ->where('is_active', true)
                        ->orderBy('id')
                        ->first();

                    if (! $fallback) {
                        throw new \DomainException('他に有効な白地図が存在しないため、デフォルト白地図を無効化または解除できません。');
                    }

                    $fallback->update(['is_default' => true]);
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
                $this->deleteCustomAssetIfOwned($oldPath);
            }

            return $updated;
        } catch (\Throwable $e) {
            foreach ($createdCustomFiles as $relativePath) {
                $this->deleteCustomAssetIfOwned($relativePath);
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
            throw new \DomainException('デフォルト白地図は削除できません。先に別の白地図をデフォルトに設定してください。');
        }

        if ($this->repository->countTimetablesUsingBaseMap($siteBaseMap) > 0 || $this->repository->countPlacementsUsingBaseMap($siteBaseMap) > 0) {
            throw new \DomainException('この白地図は運行日程または配置データで使用中のため削除できません。');
        }

        $svgPath = (string) $siteBaseMap->svg_path;
        $pdfPath = $siteBaseMap->pdf_path ? (string) $siteBaseMap->pdf_path : null;

        $this->repository->deleteBaseMap($siteBaseMap);

        $this->deleteCustomAssetIfOwned($svgPath);
        if ($pdfPath !== null) {
            $this->deleteCustomAssetIfOwned($pdfPath);
        }
    }

    /**
     * アップロードされたSVGをサニタイズし、viewBoxを抽出して public/maps/custom に保存
     *
     * @return array{path: string, viewbox: string}
     */
    public function sanitizeAndStoreSvg(UploadedFile $file): array
    {
        $rawContent = (string) File::get($file->getRealPath());
        if (trim($rawContent) === '') {
            throw new \DomainException('SVGファイルの内容が空です。');
        }

        $dom = new \DOMDocument('1.0', 'UTF-8');
        $prevUseInternalErrors = libxml_use_internal_errors(true);
        $loaded = $dom->loadXML($rawContent, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($prevUseInternalErrors);

        $root = $dom->documentElement;
        if (! $loaded || ! $root instanceof \DOMElement || strtolower((string) ($root->localName ?: $root->nodeName)) !== 'svg') {
            throw new \DomainException('有効なSVGファイルではありません。');
        }

        $this->sanitizeSvgDom($dom);

        $viewbox = $this->extractViewboxFromSvgRoot($root);

        $targetDir = public_path('maps/custom');
        File::ensureDirectoryExists($targetDir, 0777, true);

        $filename = 'base_map_'.time().'_'.Str::lower(Str::random(8)).'.svg';
        $fullPath = $targetDir.'/'.$filename;
        $sanitizedXml = $dom->saveXML();
        if ($sanitizedXml === false) {
            throw new \DomainException('SVGファイルのサニタイズ保存に失敗しました。');
        }

        File::put($fullPath, $sanitizedXml);
        @chmod($fullPath, 0666);

        return [
            'path' => 'maps/custom/'.$filename,
            'viewbox' => $viewbox,
        ];
    }

    /**
     * SVG DOM ツリーから危険な要素・イベントハンドラ・スクリプトURIを除去
     */
    protected function sanitizeSvgDom(\DOMDocument $dom): void
    {
        $forbiddenTags = ['script', 'foreignobject', 'iframe', 'object', 'embed'];
        $elementsToRemove = [];

        /** @var \DOMNodeList<\DOMElement> $allElements */
        $allElements = $dom->getElementsByTagName('*');
        foreach ($allElements as $element) {
            $tagName = strtolower((string) ($element->localName ?: $element->nodeName));
            if (in_array($tagName, $forbiddenTags, true)) {
                $elementsToRemove[] = $element;

                continue;
            }

            $attrsToRemove = [];
            if ($element->hasAttributes()) {
                foreach ($element->attributes as $attr) {
                    $attrName = strtolower((string) $attr->nodeName);
                    $attrLocal = strtolower((string) ($attr->localName ?: $attr->nodeName));
                    $attrVal = trim((string) $attr->nodeValue);

                    if (str_starts_with($attrName, 'on')) {
                        $attrsToRemove[] = $attr;

                        continue;
                    }

                    if (in_array($attrLocal, ['href', 'src', 'action', 'formaction'], true)
                        && preg_match('/^\s*(?:javascript|vbscript|data\s*:\s*text\/html)\s*:/i', $attrVal) === 1
                    ) {
                        $attrsToRemove[] = $attr;

                        continue;
                    }

                    if ($attrLocal === 'style'
                        && preg_match('/(?:javascript\s*:|expression\s*\()/i', $attrVal) === 1
                    ) {
                        $attrsToRemove[] = $attr;
                    }
                }
            }

            foreach ($attrsToRemove as $attr) {
                $element->removeAttributeNode($attr);
            }
        }

        foreach ($elementsToRemove as $element) {
            $element->parentNode?->removeChild($element);
        }
    }

    /**
     * ルート `<svg>` 要素から viewBox を抽出（未指定時は width/height から生成）
     */
    protected function extractViewboxFromSvgRoot(\DOMElement $root): string
    {
        $rawViewBox = '';
        if ($root->hasAttribute('viewBox')) {
            $rawViewBox = $root->getAttribute('viewBox');
        } elseif ($root->hasAttribute('viewbox')) {
            $rawViewBox = $root->getAttribute('viewbox');
        }

        $trimmed = trim($rawViewBox);
        if ($trimmed !== '' && preg_match('/^(-?\d+(?:\.\d+)?)[\s,]+(-?\d+(?:\.\d+)?)[\s,]+(\d+(?:\.\d+)?)[\s,]+(\d+(?:\.\d+)?)$/', $trimmed, $m) === 1) {
            return "{$m[1]} {$m[2]} {$m[3]} {$m[4]}";
        }

        $w = $this->parseNumericLength($root->getAttribute('width'));
        $h = $this->parseNumericLength($root->getAttribute('height'));
        if ($w !== null && $h !== null && $w > 0 && $h > 0) {
            return "0 0 {$w} {$h}";
        }

        return '0 0 842 1191';
    }

    /**
     * SVG の長さ属性から正の数値を取得（%指定は除外）
     */
    protected function parseNumericLength(string $value): ?string
    {
        $trimmed = trim($value);
        if ($trimmed === '' || str_contains($trimmed, '%')) {
            return null;
        }

        if (preg_match('/^(\d+(?:\.\d+)?)(?:px|pt|mm|cm|in|pc|em|rem)?$/i', $trimmed, $m) === 1) {
            return $m[1];
        }

        return null;
    }

    /**
     * アップロードされたPDF原本ファイルを public/maps/custom に保存
     */
    protected function storeUploadedPdf(UploadedFile $file): string
    {
        $targetDir = public_path('maps/custom');
        File::ensureDirectoryExists($targetDir, 0777, true);

        $filename = 'base_map_'.time().'_'.Str::lower(Str::random(8)).'.pdf';
        $file->move($targetDir, $filename);
        @chmod($targetDir.'/'.$filename, 0666);

        return 'maps/custom/'.$filename;
    }

    /**
     * カスタムアップロードファイルのみ安全に物理削除（プリセット資産は保護）
     */
    protected function deleteCustomAssetIfOwned(string $relativePath): void
    {
        if (! Str::startsWith($relativePath, 'maps/custom/')) {
            return;
        }

        $usedElsewhere = SiteBaseMap::query()
            ->where('svg_path', $relativePath)
            ->orWhere('pdf_path', $relativePath)
            ->exists();

        if (! $usedElsewhere) {
            $fullPath = public_path($relativePath);
            if (File::exists($fullPath)) {
                File::delete($fullPath);
            }
        }
    }

    /**
     * public/maps 配下の既存SVG/PDFファイル一覧を取得（iconsディレクトリ除外）
     *
     * @return array<int, string>
     */
    protected function scanMapAssetPaths(string $extension): array
    {
        $mapsRoot = public_path('maps');
        if (! File::isDirectory($mapsRoot)) {
            return [];
        }

        $paths = [];
        foreach (File::allFiles($mapsRoot) as $file) {
            $relativeToMaps = str_replace('\\', '/', $file->getRelativePathname());
            if (Str::startsWith($relativeToMaps, 'icons/')) {
                continue;
            }
            if (strtolower($file->getExtension()) !== strtolower($extension)) {
                continue;
            }
            $paths[] = 'maps/'.$relativeToMaps;
        }

        sort($paths);

        return $paths;
    }
}
