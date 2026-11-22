<?php

namespace App\Services\SiteBaseMap;

use App\Repositories\Contracts\SiteMapRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class SiteBaseMapAssetStorage
{
    public function __construct(
        protected SiteMapRepositoryInterface $repository,
        protected SvgBaseMapSanitizer $sanitizer,
    ) {}

    /**
     * アップロードされたSVGをサニタイズし、viewBoxを抽出して public/maps/custom に保存
     *
     * @return array{path: string, viewbox: string}
     */
    public function storeSanitizedSvg(UploadedFile $file): array
    {
        $rawContent = (string) File::get($file->getRealPath());
        $result = $this->sanitizer->sanitizeAndExtract($rawContent);

        $targetDir = public_path('maps/custom');
        File::ensureDirectoryExists($targetDir, 0777, true);

        $filename = 'base_map_'.time().'_'.Str::lower(Str::random(8)).'.svg';
        $fullPath = $targetDir.'/'.$filename;

        File::put($fullPath, $result['xml']);
        @chmod($fullPath, 0666);

        return [
            'path' => 'maps/custom/'.$filename,
            'viewbox' => $result['viewbox'],
        ];
    }

    /**
     * アップロードされたPDF原本ファイルを public/maps/custom に保存
     */
    public function storePdf(UploadedFile $file): string
    {
        $targetDir = public_path('maps/custom');
        File::ensureDirectoryExists($targetDir, 0777, true);

        $filename = 'base_map_'.time().'_'.Str::lower(Str::random(8)).'.pdf';
        $file->move($targetDir, $filename);
        @chmod($targetDir.'/'.$filename, 0666);

        return 'maps/custom/'.$filename;
    }

    /**
     * カスタムアップロードファイルのみ安全に物理削除（プリセット資産および他レコード参照中は保護）
     */
    public function deleteCustomAssetIfUnused(string $relativePath): void
    {
        if (! Str::startsWith($relativePath, 'maps/custom/')) {
            return;
        }

        if (! $this->repository->isAssetPathUsed($relativePath)) {
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
    public function scanMapAssetPaths(string $extension): array
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
