<?php

namespace App\Actions\Staff;

use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Services\IcsGeneratorService;
use App\Services\StaffService;
use Illuminate\Http\Response;

class ExportStaffShiftIcsAction
{
    public function __construct(
        protected StaffService $staffService,
        protected IcsGeneratorService $icsGenerator,
    ) {}

    /**
     * スタッフの割り当てシフト一覧を .ics レスポンスとして生成
     */
    public function execute(Staff $staff, ?string $timetableFilter = null): Response
    {
        $viewData = $this->staffService->getMyShiftViewData($staff, $timetableFilter);

        $events = $viewData->assignments
            ->filter(fn (TaskStaffAssignment $assignment): bool => $assignment->task?->timetable?->target_date !== null)
            ->map(function (TaskStaffAssignment $assignment) use ($staff): array {
                $task = $assignment->task;
                $timetable = $task->timetable;
                $laneName = $task->timetableLane?->name;

                $summary = $laneName
                    ? "[{$laneName}] {$task->title}"
                    : $task->title;

                $descLines = [];
                if ($timetable->name) {
                    $descLines[] = "運行日: {$timetable->name}";
                }
                if ($assignment->role_description) {
                    $descLines[] = "役割: {$assignment->role_description}";
                }
                if ($task->content) {
                    $descLines[] = $task->content;
                }

                return [
                    'uid' => "shift-{$staff->id}-task-{$task->id}@festival-timetable",
                    'summary' => $summary,
                    'start_date' => $timetable->target_date,
                    'end_date' => $timetable->target_date,
                    'start_time' => $task->start_time,
                    'end_time' => $task->end_time,
                    'location' => $task->location,
                    'description' => implode("\n", $descLines),
                ];
            })
            ->values()
            ->all();

        $content = $this->icsGenerator->generate(
            events: $events,
            calendarName: "{$staff->name} マイシフト"
        );

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"myshift_{$staff->id}.ics\"",
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }
}
