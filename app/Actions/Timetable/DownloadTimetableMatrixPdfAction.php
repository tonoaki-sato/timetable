<?php

namespace App\Actions\Timetable;

use App\Models\Lane;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Services\PdfService;
use App\Services\TimetableGridService;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DownloadTimetableMatrixPdfAction
{
    public function __construct(
        protected PdfService $pdfService,
        protected TimetableGridService $gridService
    ) {}

    /**
     * 進行表マトリクスPDFダウンロードを実行
     *
     * @param  array<int, int>|null  $laneIds
     */
    public function execute(Timetable $timetable, ?array $laneIds = null): Response
    {
        $viewData = $this->buildViewData($timetable, $laneIds);

        $sanitizedName = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $timetable->name);
        $filename = "timetable_matrix_{$sanitizedName}_".now()->format('Ymd_His').'.pdf';

        return $this->pdfService->download(
            'pdf.timetable_matrix',
            $viewData,
            $filename,
            'landscape'
        );
    }

    /**
     * ブラウザでのHTMLプレビュー表示
     *
     * @param  array<int, int>|null  $laneIds
     */
    public function preview(Timetable $timetable, ?array $laneIds = null): View
    {
        $viewData = $this->buildViewData($timetable, $laneIds);
        $viewData['japaneseFontPath'] = $this->pdfService->getJapaneseFontPath();

        return view('pdf.timetable_matrix', $viewData);
    }

    /**
     * PDFおよびプレビュー用のビューデータを生成
     *
     * @param  array<int, int>|null  $laneIds
     * @return array<string, mixed>
     */
    public function buildViewData(Timetable $timetable, ?array $laneIds = null): array
    {
        if ($timetable->lanes()->doesntExist()) {
            $timetable->createDefaultLanes();
        }

        $lanesQuery = $timetable->lanes()
            ->with('leadStaff')
            ->ordered();

        if (! empty($laneIds)) {
            $lanesQuery->whereIn('id', $laneIds);
        }

        /** @var Collection<int, Lane> $lanes */
        $lanes = $lanesQuery->get();

        $tasks = TimetableTask::with(['assignments.staff', 'laneRelation'])
            ->where('timetable_id', $timetable->id)
            ->orderBy('start_time')
            ->get();

        $tasksByLane = [];
        foreach ($lanes as $lane) {
            $tasksByLane[$lane->id] = $tasks->filter(function (TimetableTask $t) use ($lane) {
                return $t->lane_id === $lane->id;
            })->values();
        }

        $timeSlots = $this->gridService->generateTimeSlots($timetable);
        $grid = $this->gridService->buildPdfMatrixGrid($timeSlots, $lanes, $tasksByLane);

        $dateLabel = '1日目';
        $dateTheme = 'day1';
        if (str_contains($timetable->name, '2日目')) {
            $dateLabel = '2日目';
            $dateTheme = 'day2';
        } elseif (str_contains($timetable->name, '1日目')) {
            $dateLabel = '1日目';
            $dateTheme = 'day1';
        } else {
            $dateLabel = $timetable->name;
            $dateTheme = 'day1';
        }

        return [
            'timetable' => $timetable,
            'lanes' => $lanes,
            'timeSlots' => $timeSlots,
            'grid' => $grid,
            'hasRoad' => $timetable->hasRoadPeriods(),
            'hasFestival' => $timetable->hasFestivalPeriods(),
            'dateLabel' => $dateLabel,
            'dateTheme' => $dateTheme,
            'title' => '当日タイムテーブル',
        ];
    }
}
