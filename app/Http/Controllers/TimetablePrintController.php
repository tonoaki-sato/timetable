<?php

namespace App\Http\Controllers;

use App\Models\Timetable;
use Illuminate\View\View;

class TimetablePrintController extends Controller
{
    /**
     * 運行日別 人員配置一覧表（A4横向き印刷用帳票）の表示
     */
    public function printAssignments(Timetable $timetable): View
    {
        $this->authorize('view', $timetable);

        // 担当班（レーン）およびタスク、配置スタッフを時系列順にロード
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

        // 全体の必要人員と現在配置人員の集計
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
}
