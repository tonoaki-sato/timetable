<?php

namespace App\Http\Controllers;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class StaffTimelineController extends Controller
{
    /**
     * スタッフ別リソースタイムライン（ガントチャート）の表示
     */
    public function index(Request $request, Timetable $timetable): View
    {
        $this->authorize('view', $timetable);

        $activeStaff = Staff::active()->orderBy('affiliation')->orderBy('name')->get();
        $staffIds = $activeStaff->pluck('id')->all();

        // 当該運行日の全タスクとアサイン情報を取得
        $tasks = TimetableTask::with(['laneRelation', 'assignments.staff'])
            ->where('timetable_id', $timetable->id)
            ->orderBy('start_time')
            ->get();

        // スタッフIDをキーとするタスクアサイン一覧マップ
        $assignmentsByStaff = [];
        foreach ($staffIds as $sId) {
            $assignmentsByStaff[$sId] = collect();
        }

        foreach ($tasks as $task) {
            foreach ($task->assignments as $assignment) {
                if (isset($assignmentsByStaff[$assignment->staff_id])) {
                    $assignmentsByStaff[$assignment->staff_id]->push([
                        'task' => $task,
                        'assignment' => $assignment,
                    ]);
                }
            }
        }

        // 30分刻みの時間スロット一覧を生成
        $timeSlots = $this->generateTimeSlots($timetable);

        // スタッフ × 時間スロットのグリッドデータを算出
        $timelineGrid = $this->buildTimelineGrid($timeSlots, $activeStaff, $assignmentsByStaff);

        // サマリー集計
        $totalStaffCount = $activeStaff->count();
        $assignedStaffCount = collect($assignmentsByStaff)->filter(fn ($items) => $items->isNotEmpty())->count();
        $unassignedStaffCount = $totalStaffCount - $assignedStaffCount;
        $checkedInCount = $activeStaff->filter(fn (Staff $s) => $s->is_checked_in)->count();

        // 不足タスク一覧（空き枠からのクイック配置用）
        $tasksWithShortage = $tasks->filter(fn (TimetableTask $t) => $t->shortage_count > 0)->map(function (TimetableTask $t) {
            return [
                'id' => $t->id,
                'title' => $t->title,
                'lane' => $t->lane ?: ($t->laneRelation?->name ?? '担当班未設定'),
                'start_time' => substr($t->start_time, 0, 5),
                'end_time' => substr($t->end_time, 0, 5),
                'shortage_count' => $t->shortage_count,
                'assigned_staff_ids' => $t->assignments->pluck('staff_id')->all(),
            ];
        })->values();

        // 所属一覧
        $affiliations = $activeStaff->pluck('affiliation')->filter()->unique()->values();

        return view('timetables.staff_timeline', [
            'timetable' => $timetable,
            'activeStaff' => $activeStaff,
            'timeSlots' => $timeSlots,
            'timelineGrid' => $timelineGrid,
            'assignmentsByStaff' => $assignmentsByStaff,
            'tasksWithShortage' => $tasksWithShortage,
            'totalStaffCount' => $totalStaffCount,
            'assignedStaffCount' => $assignedStaffCount,
            'unassignedStaffCount' => $unassignedStaffCount,
            'checkedInCount' => $checkedInCount,
            'affiliations' => $affiliations,
            'colorOptions' => Lane::COLOR_OPTIONS,
        ]);
    }

    /**
     * 30分刻みの時間スロット配列を生成
     *
     * @return array<int, array{time: string, label: string}>
     */
    protected function generateTimeSlots(Timetable $timetable): array
    {
        $startTime = '07:00';
        $endTime = '19:30';

        if (! empty($timetable->start_time)) {
            $sH = (int) substr($timetable->start_time, 0, 2);
            $sM = (int) substr($timetable->start_time, 3, 2);
            $sM = $sM >= 30 ? 30 : 0;
            $startTime = sprintf('%02d:%02d', $sH, $sM);
        }

        if (! empty($timetable->end_time)) {
            $eH = (int) substr($timetable->end_time, 0, 2);
            $eM = (int) substr($timetable->end_time, 3, 2);
            if ($eM > 30) {
                $eH++;
                $eM = 0;
            } elseif ($eM > 0) {
                $eM = 30;
            }
            $endTime = sprintf('%02d:%02d', $eH, $eM);
        }

        $slots = [];
        $current = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        $count = 0;
        while ($current->lte($end) && $count < 48) {
            $timeStr = $current->format('H:i');
            $slots[] = [
                'time' => $timeStr,
                'label' => $timeStr,
            ];
            $current->addMinutes(30);
            $count++;
        }

        return $slots;
    }

    /**
     * スタッフ × 時間スロットのグリッドセル（横結合 colspan）を算出
     *
     * @param  array<int, array{time: string, label: string}>  $timeSlots
     * @param  Collection<int, Staff>  $activeStaff
     * @param  array<int, Collection<int, array{task: TimetableTask, assignment: TaskStaffAssignment}>>  $assignmentsByStaff
     * @return array<int, array<int, array{type: string, colspan?: int, task?: TimetableTask, assignment?: TaskStaffAssignment}>>
     */
    protected function buildTimelineGrid(array $timeSlots, $activeStaff, array $assignmentsByStaff): array
    {
        $grid = [];
        $totalSlots = count($timeSlots);

        foreach ($activeStaff as $staff) {
            $staffGrid = [];
            $coveredUntil = -1;
            $staffAssignments = $assignmentsByStaff[$staff->id] ?? collect();

            foreach ($timeSlots as $sIdx => $slot) {
                if ($coveredUntil >= $sIdx) {
                    $staffGrid[$sIdx] = ['type' => 'skip'];

                    continue;
                }

                $slotCarbon = Carbon::createFromFormat('H:i', $slot['time']);
                $slotEndCarbon = $slotCarbon->copy()->addMinutes(30);

                // このスロットの時間帯に開始または進行中のタスクを検索
                $match = $staffAssignments->first(function ($item) use ($slotCarbon, $slotEndCarbon) {
                    $t = $item['task'];
                    $tStart = Carbon::createFromFormat('H:i', substr($t->start_time, 0, 5));

                    return $tStart->gte($slotCarbon) && $tStart->lt($slotEndCarbon);
                });

                if (! $match) {
                    $match = $staffAssignments->first(function ($item) use ($slotCarbon) {
                        $t = $item['task'];
                        $tStart = Carbon::createFromFormat('H:i', substr($t->start_time, 0, 5));
                        $tEnd = Carbon::createFromFormat('H:i', substr($t->end_time, 0, 5));

                        return $tStart->lte($slotCarbon) && $tEnd->gt($slotCarbon);
                    });
                }

                if ($match) {
                    $task = $match['task'];
                    $tEnd = Carbon::createFromFormat('H:i', substr($task->end_time, 0, 5));
                    $diffMinutes = max(30, $slotCarbon->diffInMinutes($tEnd, false));
                    $span = (int) ceil($diffMinutes / 30);
                    $span = min($span, $totalSlots - $sIdx);
                    $span = max(1, $span);

                    $coveredUntil = $sIdx + $span - 1;

                    $staffGrid[$sIdx] = [
                        'type' => 'task',
                        'colspan' => $span,
                        'task' => $task,
                        'assignment' => $match['assignment'],
                    ];
                } else {
                    $staffGrid[$sIdx] = [
                        'type' => 'empty',
                        'colspan' => 1,
                    ];
                }
            }

            $grid[$staff->id] = $staffGrid;
        }

        return $grid;
    }
}
