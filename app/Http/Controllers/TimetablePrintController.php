<?php

namespace App\Http\Controllers;

use App\Actions\Timetable\DownloadTimetableAssignmentsPdfAction;
use App\Models\Timetable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class TimetablePrintController extends Controller
{
    public function __construct(
        protected DownloadTimetableAssignmentsPdfAction $downloadAssignmentsPdfAction
    ) {}

    /**
     * 運行日別 人員配置一覧表PDFダウンロード（またはプレビュー表示）
     */
    public function printAssignments(Timetable $timetable, Request $request): Response|View
    {
        $this->authorize('view', $timetable);

        // プレビュー指定時はHTMLプレビュー画面を表示
        if ($request->boolean('preview')) {
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

            return view('timetables.print_assignments', [
                'timetable' => $timetable,
                'totalRequired' => $totalRequired,
                'totalAssigned' => $totalAssigned,
                'title' => "【人員配置一覧表】{$timetable->name} (".$timetable->target_date?->format('Y/m/d').')',
            ]);
        }

        return $this->downloadAssignmentsPdfAction->execute($timetable);
    }
}
