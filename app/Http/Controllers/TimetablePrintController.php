<?php

namespace App\Http\Controllers;

use App\Actions\Timetable\DownloadTimetableAssignmentsPdfAction;
use App\Actions\Timetable\DownloadTimetableMatrixPdfAction;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TimetablePrintController extends Controller
{
    public function __construct(
        protected DownloadTimetableAssignmentsPdfAction $downloadAssignmentsPdfAction,
        protected DownloadTimetableMatrixPdfAction $downloadMatrixPdfAction
    ) {}

    /**
     * 運行日別 人員配置一覧表PDFダウンロード（またはプレビュー表示）
     */
    public function printAssignments(Timetable $timetable, Request $request): Response|View
    {
        $this->authorize('view', $timetable);

        // プレビュー指定時はHTMLプレビュー画面を表示
        if ($request->boolean('preview')) {
            return view('timetables.print_assignments', $this->downloadAssignmentsPdfAction->buildViewData($timetable));
        }

        return $this->downloadAssignmentsPdfAction->execute($timetable);
    }

    /**
     * 運行日別 進行表（当日タイムテーブル）PDFダウンロード（またはプレビュー表示）
     */
    public function printMatrix(Timetable $timetable, Request $request): Response|View
    {
        $this->authorize('view', $timetable);

        $laneIds = null;
        if ($request->filled('lane_ids')) {
            $input = $request->input('lane_ids');
            $rawIds = is_array($input) ? $input : explode(',', (string) $input);
            $laneIds = array_values(array_filter(array_map('intval', $rawIds), fn (int $id) => $id > 0));
        }

        if ($request->boolean('preview')) {
            return $this->downloadMatrixPdfAction->preview($timetable, $laneIds);
        }

        return $this->downloadMatrixPdfAction->execute($timetable, $laneIds);
    }
}
