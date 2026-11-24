<?php

namespace App\Services;

use App\DTOs\Timeline\GanttBarDTO;
use App\DTOs\Timeline\LaneTimelineRowDTO;
use App\Enums\TimetableTaskStatus;
use App\Enums\TimetableTaskType;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Presenters\LanePresenter;
use App\Repositories\Contracts\TimetableLaneRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use App\Support\TimeSlotHelper;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LaneTimelineService
{
    public function __construct(
        protected TimetableLaneRepositoryInterface $timetableLaneRepository,
        protected TimetableTaskRepositoryInterface $taskRepository,
    ) {}

    /**
     * 班別タイムライン（担当班別タスクガントチャート）画面用の全表示データを構築
     *
     * @return array<string, mixed>
     */
    public function getLaneTimelineViewData(Timetable $timetable): array
    {
        $timetableLanes = $this->timetableLaneRepository->getForTimetable($timetable);
        $tasks = $this->taskRepository->getForTimelineByTimetableId($timetable->id);

        $tasksByLane = $tasks->groupBy('timetable_lane_id');
        $bounds = TimeSlotHelper::resolveTimelineBoundsMinutes($timetable, $tasks);
        $periodBands = TimeSlotHelper::extractPeriodBands($timetable, $bounds['start_min'], $bounds['end_min']);

        /** @var list<LaneTimelineRowDTO> $laneRows */
        $laneRows = [];
        $totalRoutineCount = 0;
        $totalSpotCount = 0;
        $totalShortageTaskCount = 0;
        $allTaskPayloads = [];

        foreach ($timetableLanes as $lane) {
            /** @var Collection<int, TimetableTask> $laneTasks */
            $laneTasks = $tasksByLane->get($lane->id, collect());
            $laneColor = (string) ($lane->color ?? 'indigo');

            $routineItems = [];
            $spotItems = [];
            $shortageCount = 0;

            foreach ($laneTasks as $task) {
                $allTaskPayloads[] = $task->presenter()->toModalPayload($lane);

                if (! $task->is_satisfied && $task->status !== 'cancelled') {
                    $shortageCount++;
                }

                $startMin = TimeSlotHelper::hhmmToMinutes(substr((string) $task->start_time, 0, 5));
                $endMin = max($startMin + 15, TimeSlotHelper::hhmmToMinutes(substr((string) $task->end_time, 0, 5)));

                $item = [
                    'id' => (int) $task->id,
                    'start_min' => $startMin,
                    'end_min' => $endMin,
                    'task' => $task,
                ];

                if ($task->isRoutine()) {
                    $routineItems[] = $item;
                } else {
                    $spotItems[] = $item;
                }
            }

            $packedRoutine = TimeSlotHelper::packIntervalsIntoTracks($routineItems);
            $packedSpot = TimeSlotHelper::packIntervalsIntoTracks($spotItems);

            $routineBars = array_map(static function (array $packed) use ($laneColor): GanttBarDTO {
                /** @var TimetableTask $task */
                $task = $packed['task'];

                return GanttBarDTO::fromTask(
                    task: $task,
                    subTrack: (int) $packed['sub_track'],
                    startMin: (int) $packed['start_min'],
                    endMin: (int) $packed['end_min'],
                    barClass: LanePresenter::timelineRoutineBarClassFor($laneColor),
                    hasSameTypeOverlap: (bool) $packed['has_overlap'],
                );
            }, $packedRoutine['items']);

            $spotBars = array_map(static function (array $packed) use ($laneColor): GanttBarDTO {
                /** @var TimetableTask $task */
                $task = $packed['task'];

                return GanttBarDTO::fromTask(
                    task: $task,
                    subTrack: (int) $packed['sub_track'],
                    startMin: (int) $packed['start_min'],
                    endMin: (int) $packed['end_min'],
                    barClass: LanePresenter::timelineSpotBarClassFor($laneColor),
                    hasSameTypeOverlap: (bool) $packed['has_overlap'],
                );
            }, $packedSpot['items']);

            $routineCount = count($routineBars);
            $spotCount = count($spotBars);

            $totalRoutineCount += $routineCount;
            $totalSpotCount += $spotCount;
            $totalShortageTaskCount += $shortageCount;

            $laneRows[] = new LaneTimelineRowDTO(
                timetableLane: $lane,
                routineCount: $routineCount,
                spotCount: $spotCount,
                shortageTaskCount: $shortageCount,
                routineTracksCount: $packedRoutine['tracks_count'],
                spotTracksCount: $packedSpot['tracks_count'],
                routineBars: $routineBars,
                spotBars: $spotBars,
                suggestedStartTime: $lane->presenter()->suggestedStartTime(),
            );
        }

        $nowTime = Carbon::now('Asia/Tokyo')->format('H:i');

        return [
            'timetable' => $timetable,
            'timetableLanes' => $timetableLanes,
            'laneRows' => $laneRows,
            'bounds' => $bounds,
            'roadBands' => $periodBands['road_bands'],
            'festivalBands' => $periodBands['festival_bands'],
            'nowTime' => $nowTime,
            'nowMin' => TimeSlotHelper::hhmmToMinutes($nowTime),
            'totalLanesCount' => $timetableLanes->count(),
            'totalTasksCount' => $totalRoutineCount + $totalSpotCount,
            'totalRoutineCount' => $totalRoutineCount,
            'totalSpotCount' => $totalSpotCount,
            'totalShortageTaskCount' => $totalShortageTaskCount,
            'statuses' => TimetableTaskStatus::options(),
            'taskTypes' => TimetableTaskType::options(),
            'allTaskPayloads' => $allTaskPayloads,
        ];
    }
}
