<?php

namespace App\Actions\Staff;

use App\Services\PdfService;
use App\Services\StaffService;
use Illuminate\Http\Response;

/**
 * スタッフ一覧 A4縦・2段組PDFダウンロードを実行するアクション
 */
class DownloadStaffListPdfAction
{
    public function __construct(
        protected PdfService $pdfService,
        protected StaffService $staffService
    ) {}

    /**
     * スタッフ一覧PDFダウンロードを実行
     */
    public function execute(?string $search = null, ?string $status = null): Response
    {
        $viewData = $this->buildViewData($search, $status);

        $filename = 'staff_list_'.now()->format('Ymd_His').'.pdf';

        return $this->pdfService->download(
            'pdf.staff_list',
            $viewData,
            $filename,
            'portrait'
        );
    }

    /**
     * PDF表示用データを構築
     *
     * @return array<string, mixed>
     */
    public function buildViewData(?string $search = null, ?string $status = null): array
    {
        return $this->staffService->getPdfViewData($search, $status);
    }
}
