<?php

namespace App\Actions\Timetable;

use App\Actions\Timetable\Cloners\TimetableLaneTaskCloner;
use App\Actions\Timetable\Cloners\TimetableLogisticsCloner;
use App\Actions\Timetable\Cloners\TimetableSiteLayoutCloner;
use App\DTOs\Timetable\CloneTimetableOptionsDTO;
use App\Models\Timetable;
use App\Models\User;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CloneTimetableAction
{
    public function __construct(
        protected TimetableRepositoryInterface $timetableRepository,
        protected ScheduleRepositoryInterface $scheduleRepository,
        protected TimetableLaneTaskCloner $laneTaskCloner,
        protected TimetableLogisticsCloner $logisticsCloner,
        protected TimetableSiteLayoutCloner $siteLayoutCloner,
    ) {}

    /**
     * 過去の運行情報をもとに新しい運行情報をディープコピーで作成する
     *
     * @param  array<string, mixed>  $attributes
     * @return array{
     *     timetable: Timetable,
     *     counts: array{
     *         lanes: int,
     *         tasks: int,
     *         assignments: int,
     *         logistics_plans: int,
     *         layout_items: int,
     *         placements: int
     *     }
     * }
     */
    public function execute(array $attributes, CloneTimetableOptionsDTO $options, ?User $creator): array
    {
        $source = $this->timetableRepository->findOrFail($options->sourceTimetableId);

        return DB::transaction(function () use ($source, $attributes, $options, $creator): array {
            $timetableData = [
                'target_date' => $attributes['target_date'],
                'name' => $attributes['name'],
                'description' => $attributes['description'] ?? null,
                'start_time' => $attributes['start_time'],
                'end_time' => $attributes['end_time'],
                'status' => $attributes['status'],
                'road_periods' => $attributes['road_periods'] ?? null,
                'festival_periods' => $attributes['festival_periods'] ?? null,
                'created_by' => $creator?->id,
            ];

            $newTimetable = $this->timetableRepository->create($timetableData);
            $this->scheduleRepository->syncForTimetable($newTimetable);

            $dayOffset = $this->calculateDayOffset($source, $newTimetable);

            $lanesCount = 0;
            $tasksCount = 0;
            $assignmentsCount = 0;

            if ($options->copyLanes) {
                $laneIdMap = $this->laneTaskCloner->cloneLanes($source, $newTimetable, $options->copyLaneStaff);
                $lanesCount = count($laneIdMap);

                if ($options->copyTasks) {
                    [$tasksCount, $assignmentsCount] = $this->laneTaskCloner->cloneTasks(
                        $source,
                        $newTimetable,
                        $laneIdMap,
                        $options->copyTaskAssignments
                    );
                }
            }

            $logisticsPlansCount = 0;
            if ($options->copyLogisticsPlans) {
                $logisticsPlansCount = $this->logisticsCloner->cloneLogisticsPlans($source, $newTimetable, $dayOffset);
            }

            $layoutItemsCount = 0;
            $placementsCount = 0;
            if ($options->copySiteLayouts) {
                [$layoutItemsCount, $placementsCount] = $this->siteLayoutCloner->cloneSiteLayouts(
                    $source,
                    $newTimetable,
                    $creator
                );
            }

            return [
                'timetable' => $newTimetable,
                'counts' => [
                    'lanes' => $lanesCount,
                    'tasks' => $tasksCount,
                    'assignments' => $assignmentsCount,
                    'logistics_plans' => $logisticsPlansCount,
                    'layout_items' => $layoutItemsCount,
                    'placements' => $placementsCount,
                ],
            ];
        });
    }

    /**
     * 複製元と新規作成先の開催日数の差（日数オフセット）を算出
     */
    protected function calculateDayOffset(Timetable $source, Timetable $target): int
    {
        if (! $source->target_date || ! $target->target_date) {
            return 0;
        }

        $sourceDate = $source->target_date->copy()->startOfDay();
        $targetDate = $target->target_date->copy()->startOfDay();

        return (int) $sourceDate->diffInDays($targetDate, false);
    }
}
