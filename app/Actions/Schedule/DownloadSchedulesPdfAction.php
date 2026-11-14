<?php

namespace App\Actions\Schedule;

use App\Services\PdfService;
use App\Services\ScheduleService;
use Illuminate\Http\Response;

/**
 * 年間スケジュール一覧 A4縦PDFダウンロードを実行するアクション
 */
class DownloadSchedulesPdfAction
{
    public function __construct(
        protected PdfService $pdfService,
        protected ScheduleService $scheduleService
    ) {}

    /**
     * 年間スケジュール一覧PDFダウンロードを実行
     */
    public function execute(
        mixed $category = null,
        ?string $status = null,
        ?string $search = null,
        ?int $staffId = null,
        ?string $tab = 'upcoming'
    ): Response {
        $viewData = $this->buildViewData(
            category: $category,
            status: $status,
            search: $search,
            staffId: $staffId,
            tab: $tab
        );

        $filename = 'schedules_'.now()->format('Ymd_His').'.pdf';

        return $this->pdfService->download(
            'pdf.schedules',
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
    public function buildViewData(
        mixed $category = null,
        ?string $status = null,
        ?string $search = null,
        ?int $staffId = null,
        ?string $tab = 'upcoming'
    ): array {
        return $this->scheduleService->getPdfViewData(
            category: $category,
            status: $status,
            search: $search,
            staffId: $staffId,
            tab: $tab
        );
    }
}
