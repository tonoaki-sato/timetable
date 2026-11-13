<?php

namespace App\Actions\Organization;

use App\Services\OrganizationService;
use App\Services\PdfService;
use Illuminate\Http\Response;

/**
 * 組織別所属スタッフ一覧 A4縦PDFダウンロードを実行するアクション
 */
class DownloadOrganizationStaffListPdfAction
{
    public function __construct(
        protected PdfService $pdfService,
        protected OrganizationService $organizationService
    ) {}

    /**
     * 組織別所属スタッフ一覧PDFダウンロードを実行
     */
    public function execute(): Response
    {
        $viewData = $this->buildViewData();

        $filename = 'organization_staff_list_'.now()->format('Ymd_His').'.pdf';

        return $this->pdfService->download(
            'pdf.organization_staff_list',
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
    public function buildViewData(): array
    {
        return $this->organizationService->getStaffListPdfViewData();
    }
}

