<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Illuminate\Http\Response;

class PdfService
{
    /**
     * 日本語フォントのローカルファイルパスを取得
     */
    public function getJapaneseFontPath(): string
    {
        $storageFont = storage_path('fonts/DroidSansFallbackFull.ttf');
        if (file_exists($storageFont)) {
            return $storageFont;
        }

        $resourceFont = resource_path('fonts/DroidSansFallbackFull.ttf');
        if (file_exists($resourceFont)) {
            return $resourceFont;
        }

        return '/usr/share/fonts/google-droid-sans-fonts/DroidSansFallbackFull.ttf';
    }

    /**
     * BladeビューからPDFインスタンスを準備
     *
     * @param  array<string, mixed>  $data
     * @param  'portrait'|'landscape'  $orientation
     */
    protected function makePdf(string $viewPath, array $data, string $orientation = 'portrait'): DomPdfWrapper
    {
        $data['japaneseFontPath'] = $this->getJapaneseFontPath();
        $fontDir = storage_path('fonts');
        if (! is_dir($fontDir)) {
            mkdir($fontDir, 0777, true);
        }

        /** @var DomPdfWrapper $pdf */
        $pdf = Pdf::loadView($viewPath, $data);
        $pdf->setPaper('a4', $orientation);
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'enable_font_subsetting' => true,
            'chroot' => base_path(),
            'font_dir' => $fontDir,
            'font_cache' => $fontDir,
            'temp_dir' => sys_get_temp_dir(),
        ], true);

        return $pdf;
    }

    /**
     * PDFダウンロード用HTTPレスポンスを生成
     *
     * @param  array<string, mixed>  $data
     * @param  'portrait'|'landscape'  $orientation
     */
    public function download(
        string $viewPath,
        array $data,
        string $filename,
        string $orientation = 'portrait'
    ): Response {
        $pdf = $this->makePdf($viewPath, $data, $orientation);

        return $pdf->download($filename);
    }

    /**
     * ブラウザインライン表示用HTTPレスポンスを生成
     *
     * @param  array<string, mixed>  $data
     * @param  'portrait'|'landscape'  $orientation
     */
    public function stream(
        string $viewPath,
        array $data,
        string $filename,
        string $orientation = 'portrait'
    ): Response {
        $pdf = $this->makePdf($viewPath, $data, $orientation);

        return $pdf->stream($filename);
    }
}
