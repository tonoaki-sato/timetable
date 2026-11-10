<?php

namespace App\Actions\Timetable;

use App\Models\Timetable;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Services\PdfService;
use Illuminate\Http\Response;

class DownloadTimetableAssignmentsPdfAction
{
    public function __construct(
        protected PdfService $pdfService,
        protected TimetableRepositoryInterface $timetableRepository
    ) {}

    /**
     * 運行日別 人員配置一覧表PDFダウンロードを実行
     */
    public function execute(Timetable $timetable): Response
    {
        $viewData = $this->buildViewData($timetable);

        $sanitizedName = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $timetable->name);
        $filename = "assignments_{$sanitizedName}_".now()->format('Ymd_His').'.pdf';

        return $this->pdfService->download(
            'pdf.timetable_assignments',
            $viewData,
            $filename,
            'landscape'
        );
    }

    /**
     * 人員配置一覧表（PDFおよびHTMLプレビュー共通）のビューデータを構築
     *
     * @return array{
     *     timetable: Timetable,
     *     totalRequired: int,
     *     totalAssigned: int,
     *     title: string
     * }
     */
    public function buildViewData(Timetable $timetable): array
    {
        $this->timetableRepository->loadForAssignmentsPrint($timetable);

        $totalRequired = 0;
        $totalAssigned = 0;

        foreach ($timetable->lanes as $lane) {
            foreach ($lane->tasks as $task) {
                $totalRequired += $task->required_people_count;
                $totalAssigned += $task->assigned_count;
            }
        }

        return [
            'timetable' => $timetable,
            'totalRequired' => $totalRequired,
            'totalAssigned' => $totalAssigned,
            'title' => "【人員配置一覧表】{$timetable->name} (".$timetable->target_date?->format('Y/m/d').')',
        ];
    }
}
