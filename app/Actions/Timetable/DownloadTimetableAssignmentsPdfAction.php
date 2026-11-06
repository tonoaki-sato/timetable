<?php

namespace App\Actions\Timetable;

use App\Models\Timetable;
use App\Services\PdfService;
use Illuminate\Http\Response;

class DownloadTimetableAssignmentsPdfAction
{
    public function __construct(
        protected PdfService $pdfService
    ) {}

    /**
     * 運行日別 人員配置一覧表PDFダウンロードを実行
     */
    public function execute(Timetable $timetable): Response
    {
        $timetable->load([
            'lanes' => function ($query) {
                $query->orderBy('display_order')->orderBy('id');
            },
            'lanes.leadStaff',
            'lanes.tasks' => function ($query) {
                $query->orderBy('start_time')->orderBy('end_time');
            },
            'lanes.tasks.assignments.staff',
        ]);

        $totalRequired = 0;
        $totalAssigned = 0;

        foreach ($timetable->lanes as $lane) {
            foreach ($lane->tasks as $task) {
                $totalRequired += $task->required_people_count;
                $totalAssigned += $task->assigned_count;
            }
        }

        $sanitizedName = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $timetable->name);
        $filename = "assignments_{$sanitizedName}_".now()->format('Ymd_His').'.pdf';

        return $this->pdfService->download(
            'pdf.timetable_assignments',
            [
                'timetable' => $timetable,
                'totalRequired' => $totalRequired,
                'totalAssigned' => $totalAssigned,
                'title' => "【人員配置一覧表】{$timetable->name} (".$timetable->target_date?->format('Y/m/d').')',
            ],
            $filename,
            'landscape'
        );
    }
}
