<?php

namespace App\Actions\Organization;

use App\Services\OrganizationService;
use App\Services\PdfService;
use Illuminate\Http\Response;

/**
 * 組織図 A4縦PDFダウンロードを実行するアクション
 */
class DownloadOrganizationPdfAction
{
    public function __construct(
        protected PdfService $pdfService,
        protected OrganizationService $organizationService
    ) {}

    /**
     * 組織図PDFダウンロードを実行
     */
    public function execute(): Response
    {
        $viewData = $this->buildViewData();

        $filename = 'organization_chart_'.now()->format('Ymd_His').'.pdf';

        return $this->pdfService->download(
            'pdf.organization_chart',
            $viewData,
            $filename,
            'portrait'
        );
    }

    /**
     * 組織図PDF用の表示データを構築
     *
     * @return array<string, mixed>
     */
    public function buildViewData(): array
    {
        $printData = $this->organizationService->getPrintViewData();

        return [
            'treeUnits' => $printData['treeUnits'],
            'title' => '保土ケ谷宿場まつり実行委員会 組織図',
            'generatedAt' => now()->format('Y年m月d日 H:i'),
        ];
    }
}
