<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfWrapper;
use Dompdf\Dompdf;
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

        $html = view($viewPath, $data)->render();
        // 4バイトUTF-8絵文字・異体字セレクタ等はDompdf/php-font-libでサロゲートペア化され文字化けの原因となるため安全に除去
        $html = preg_replace('/[\x{10000}-\x{10FFFF}\x{FE00}-\x{FE0F}]/u', '', $html) ?? $html;

        /** @var DomPdfWrapper $pdf */
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('a4', $orientation);
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'enable_font_subsetting' => true,
            'defaultFont' => 'JapaneseFont',
            'chroot' => base_path(),
            'font_dir' => $fontDir,
            'font_cache' => $fontDir,
            'temp_dir' => sys_get_temp_dir(),
        ], true);

        return $pdf;
    }

    /**
     * レンダリング済みDompdfインスタンスを取得（コールバックによるレイアウト計測用途）
     *
     * @param  array<string, mixed>  $data
     * @param  'portrait'|'landscape'  $orientation
     * @param  array<int, array{event: string, f: callable}>  $callbacks
     */
    public function renderDompdf(
        string $viewPath,
        array $data,
        string $orientation = 'portrait',
        array $callbacks = []
    ): Dompdf {
        $pdf = $this->makePdf($viewPath, $data, $orientation);
        $dompdf = $pdf->getDomPDF();
        if ($callbacks !== []) {
            $dompdf->setCallbacks($callbacks);
        }
        $dompdf->render();

        return $dompdf;
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
