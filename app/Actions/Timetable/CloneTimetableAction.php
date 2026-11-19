<?php

namespace App\Actions\Timetable;

use App\DTOs\Timetable\CloneTimetableOptionsDTO;
use App\Enums\LayoutItemStatus;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Lane;
use App\Models\LayoutArea;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Location;
use App\Models\LocationSchedule;
use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Illuminate\Support\Facades\DB;

class CloneTimetableAction
{
    public function __construct(
        protected TimetableRepositoryInterface $timetableRepository,
        protected ScheduleRepositoryInterface $scheduleRepository
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
     *         locations: int,
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

            $laneIdMap = [];
            $lanesCount = 0;
            $tasksCount = 0;
            $assignmentsCount = 0;

            if ($options->copyLanes) {
                $laneIdMap = $this->cloneLanes($source, $newTimetable, $options->copyLaneStaff);
                $lanesCount = count($laneIdMap);

                if ($options->copyTasks) {
                    [$tasksCount, $assignmentsCount] = $this->cloneTasks(
                        $source,
                        $newTimetable,
                        $laneIdMap,
                        $options->copyTaskAssignments
                    );
                }
            }

            $locationIdMap = [];
            $locationsCount = 0;

            if ($options->copyLocations) {
                $locationIdMap = $this->cloneLocations($source, $newTimetable, $dayOffset, $creator);
                $locationsCount = count($locationIdMap);
            }

            $logisticsPlansCount = 0;
            if ($options->copyLogisticsPlans) {
                $logisticsPlansCount = $this->cloneLogisticsPlans($source, $newTimetable, $locationIdMap, $dayOffset);
            }

            $layoutItemsCount = 0;
            $placementsCount = 0;
            if ($options->copySiteLayouts) {
                [$layoutItemsCount, $placementsCount] = $this->cloneSiteLayouts(
                    $source,
                    $newTimetable,
                    $locationIdMap,
                    $creator
                );
            }

            return [
                'timetable' => $newTimetable,
                'counts' => [
                    'lanes' => $lanesCount,
                    'tasks' => $tasksCount,
                    'assignments' => $assignmentsCount,
                    'locations' => $locationsCount,
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

    /**
     * 担当班（レーン）および固定スタッフを複製し、旧ID→新IDのマップを返却
     *
     * @return array<int, int>
     */
    protected function cloneLanes(Timetable $source, Timetable $newTimetable, bool $copyLaneStaff): array
    {
        $laneIdMap = [];

        $sourceLanes = Lane::with('fixedStaff')
            ->where('timetable_id', $source->id)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        foreach ($sourceLanes as $sourceLane) {
            $newLane = Lane::create([
                'timetable_id' => $newTimetable->id,
                'name' => $sourceLane->name,
                'color' => $sourceLane->color,
                'icon' => $sourceLane->icon,
                'lead_staff_id' => $copyLaneStaff ? $sourceLane->lead_staff_id : null,
                'description' => $sourceLane->description,
                'display_order' => $sourceLane->display_order,
            ]);

            if ($copyLaneStaff && $sourceLane->fixedStaff->isNotEmpty()) {
                $newLane->fixedStaff()->sync($sourceLane->fixedStaff->pluck('id')->all());
            }

            $laneIdMap[$sourceLane->id] = $newLane->id;
        }

        return $laneIdMap;
    }

    /**
     * 作業タスクおよびスタッフアサインを複製し、作成件数 [tasks, assignments] を返却
     *
     * @param  array<int, int>  $laneIdMap
     * @return array{0: int, 1: int}
     */
    protected function cloneTasks(
        Timetable $source,
        Timetable $newTimetable,
        array $laneIdMap,
        bool $copyTaskAssignments
    ): array {
        $tasksCount = 0;
        $assignmentsCount = 0;

        $sourceTasks = TimetableTask::with('assignments')
            ->where('timetable_id', $source->id)
            ->orderBy('start_time')
            ->orderBy('id')
            ->get();

        foreach ($sourceTasks as $sourceTask) {
            $newLaneId = $sourceTask->lane_id ? ($laneIdMap[$sourceTask->lane_id] ?? null) : null;
            if (! $newLaneId) {
                continue;
            }

            $newTask = TimetableTask::create([
                'timetable_id' => $newTimetable->id,
                'lane_id' => $newLaneId,
                'title' => $sourceTask->title,
                'content' => $sourceTask->content,
                'location' => $sourceTask->location,
                'start_time' => $sourceTask->start_time,
                'end_time' => $sourceTask->end_time,
                'required_people_count' => $sourceTask->required_people_count,
                'is_at_least' => $sourceTask->is_at_least,
                'is_critical' => $sourceTask->is_critical,
                'status' => 'not_started',
            ]);
            $tasksCount++;

            if ($copyTaskAssignments) {
                foreach ($sourceTask->assignments as $assignment) {
                    if ($assignment->attendance_status === 'absent') {
                        continue;
                    }

                    TaskStaffAssignment::create([
                        'timetable_task_id' => $newTask->id,
                        'staff_id' => $assignment->staff_id,
                        'role_description' => $assignment->role_description,
                        'attendance_status' => 'assigned',
                        'notes' => $assignment->notes,
                    ]);
                    $assignmentsCount++;
                }
            }
        }

        return [$tasksCount, $assignmentsCount];
    }

    /**
     * 拠点および利用時間帯スケジュールを複製（日付は日数差でシフト、確保ステータスは未確保にリセット）
     *
     * @return array<int, int>
     */
    protected function cloneLocations(Timetable $source, Timetable $newTimetable, int $dayOffset, ?User $creator): array
    {
        $locationIdMap = [];

        $sourceLocations = Location::with('schedules')
            ->where('timetable_id', $source->id)
            ->orderBy('display_order')
            ->orderBy('id')
            ->get();

        foreach ($sourceLocations as $sourceLocation) {
            $newLocation = Location::create([
                'timetable_id' => $newTimetable->id,
                'location_type_id' => $sourceLocation->location_type_id,
                'name' => $sourceLocation->name,
                'notes' => $sourceLocation->notes,
                'is_all_day' => $sourceLocation->is_all_day,
                'display_order' => $sourceLocation->display_order,
                'is_active' => $sourceLocation->is_active,
                'created_by' => $creator?->id ?? $sourceLocation->created_by,
            ]);

            foreach ($sourceLocation->schedules as $schedule) {
                LocationSchedule::create([
                    'location_id' => $newLocation->id,
                    'start_at' => $schedule->start_at?->copy()->addDays($dayOffset),
                    'end_at' => $schedule->end_at?->copy()->addDays($dayOffset),
                    'availability_status' => 'unavailable',
                    'title' => $schedule->title,
                    'note' => $schedule->note,
                ]);
            }

            $locationIdMap[$sourceLocation->id] = $newLocation->id;
        }

        return $locationIdMap;
    }

    /**
     * 資材搬出入計画を複製（拠点IDリマップ・日付シフト・ステータス初期化）
     *
     * @param  array<int, int>  $locationIdMap
     */
    protected function cloneLogisticsPlans(
        Timetable $source,
        Timetable $newTimetable,
        array $locationIdMap,
        int $dayOffset
    ): int {
        $plansCount = 0;

        $sourcePlans = EquipmentLogisticsPlan::where('timetable_id', $source->id)
            ->orderBy('id')
            ->get();

        foreach ($sourcePlans as $sourcePlan) {
            $newFromLocationId = $sourcePlan->from_location_id
                ? ($locationIdMap[$sourcePlan->from_location_id] ?? null)
                : null;
            $newToLocationId = $sourcePlan->to_location_id
                ? ($locationIdMap[$sourcePlan->to_location_id] ?? null)
                : null;

            EquipmentLogisticsPlan::create([
                'timetable_id' => $newTimetable->id,
                'equipment_id' => $sourcePlan->equipment_id,
                'target_date' => $sourcePlan->target_date?->copy()->addDays($dayOffset),
                'day_phase' => $sourcePlan->day_phase,
                'action_type' => $sourcePlan->action_type,
                'title' => $sourcePlan->title,
                'quantity' => $sourcePlan->quantity,
                'quantity_note' => $sourcePlan->quantity_note,
                'start_time' => $sourcePlan->start_time,
                'end_time' => $sourcePlan->end_time,
                'from_location' => $sourcePlan->from_location,
                'to_location' => $sourcePlan->to_location,
                'from_location_id' => $newFromLocationId,
                'to_location_id' => $newToLocationId,
                'operator_type' => $sourcePlan->operator_type,
                'witness_required' => $sourcePlan->witness_required,
                'witness_staff_id' => $sourcePlan->witness_staff_id,
                'status' => 'planned',
                'notes' => $sourcePlan->notes,
            ]);
            $plansCount++;
        }

        return $plansCount;
    }

    /**
     * 現場配置計画（LayoutMap / LayoutArea / LayoutItem）および白地図配置（SiteBaseMap / SiteMapPlacement）を複製
     *
     * @param  array<int, int>  $locationIdMap
     * @return array{0: int, 1: int}
     */
    protected function cloneSiteLayouts(
        Timetable $source,
        Timetable $newTimetable,
        array $locationIdMap,
        ?User $creator
    ): array {
        $layoutItemsCount = 0;
        $placementsCount = 0;
        $layoutItemIdMap = [];

        $sourceMaps = LayoutMap::with(['areas', 'items'])
            ->where('timetable_id', $source->id)
            ->orderBy('id')
            ->get();

        foreach ($sourceMaps as $sourceMap) {
            $newMap = LayoutMap::create([
                'timetable_id' => $newTimetable->id,
                'name' => $sourceMap->name,
                'version_tag' => $sourceMap->version_tag,
                'notes' => $sourceMap->notes,
                'created_by' => $creator?->id ?? $sourceMap->created_by,
            ]);

            $areaIdMap = [];
            foreach ($sourceMap->areas as $sourceArea) {
                $newArea = LayoutArea::create([
                    'layout_map_id' => $newMap->id,
                    'location_id' => $sourceArea->location_id
                        ? ($locationIdMap[$sourceArea->location_id] ?? null)
                        : null,
                    'zone_code' => $sourceArea->zone_code,
                    'name' => $sourceArea->name,
                    'display_order' => $sourceArea->display_order,
                ]);
                $areaIdMap[$sourceArea->id] = $newArea->id;
            }

            foreach ($sourceMap->items as $sourceItem) {
                $newAreaId = $sourceItem->layout_area_id
                    ? ($areaIdMap[$sourceItem->layout_area_id] ?? null)
                    : null;
                if (! $newAreaId) {
                    continue;
                }

                $newItem = LayoutItem::create([
                    'layout_map_id' => $newMap->id,
                    'layout_area_id' => $newAreaId,
                    'item_type' => $sourceItem->item_type,
                    'code' => $sourceItem->code,
                    'name' => $sourceItem->name,
                    'status' => LayoutItemStatus::Tentative,
                    'category' => $sourceItem->category,
                    'booth_type' => $sourceItem->booth_type,
                    'vendor_id' => $sourceItem->vendor_id,
                    'location_description' => $sourceItem->location_description,
                    'notes' => $sourceItem->notes,
                    'display_order' => $sourceItem->display_order,
                ]);

                $layoutItemIdMap[$sourceItem->id] = $newItem->id;
                $layoutItemsCount++;
            }
        }

        $sourceBaseMaps = SiteBaseMap::with('placements')
            ->where('timetable_id', $source->id)
            ->orderBy('id')
            ->get();

        foreach ($sourceBaseMaps as $sourceBaseMap) {
            $newBaseMap = SiteBaseMap::create([
                'timetable_id' => $newTimetable->id,
                'name' => $sourceBaseMap->name,
                'cad_version' => $sourceBaseMap->cad_version,
                'paper_size' => $sourceBaseMap->paper_size,
                'scale_ratio' => $sourceBaseMap->scale_ratio,
                'width_meters' => $sourceBaseMap->width_meters,
                'height_meters' => $sourceBaseMap->height_meters,
                'svg_path' => $sourceBaseMap->svg_path,
                'pdf_path' => $sourceBaseMap->pdf_path,
                'viewbox' => $sourceBaseMap->viewbox,
                'is_active' => $sourceBaseMap->is_active,
                'notes' => $sourceBaseMap->notes,
            ]);

            foreach ($sourceBaseMap->placements as $sourcePlacement) {
                $newLayoutItemId = $sourcePlacement->layout_item_id
                    ? ($layoutItemIdMap[$sourcePlacement->layout_item_id] ?? null)
                    : null;

                SiteMapPlacement::create([
                    'site_base_map_id' => $newBaseMap->id,
                    'layout_item_id' => $newLayoutItemId,
                    'shape_type' => $sourcePlacement->shape_type,
                    'display_text' => $sourcePlacement->display_text,
                    'font_size' => $sourcePlacement->font_size,
                    'color_theme' => $sourcePlacement->color_theme,
                    'pos_x' => $sourcePlacement->pos_x,
                    'pos_y' => $sourcePlacement->pos_y,
                    'width' => $sourcePlacement->width,
                    'height' => $sourcePlacement->height,
                    'rotation' => $sourcePlacement->rotation,
                    'is_rotation_locked' => $sourcePlacement->is_rotation_locked,
                    'show_hygiene_circle' => $sourcePlacement->show_hygiene_circle,
                    'arc_start_angle' => $sourcePlacement->arc_start_angle,
                    'arc_end_angle' => $sourcePlacement->arc_end_angle,
                    'z_index' => $sourcePlacement->z_index,
                    'notes' => $sourcePlacement->notes,
                    'meta_data' => $sourcePlacement->meta_data,
                ]);
                $placementsCount++;
            }
        }

        return [$layoutItemsCount, $placementsCount];
    }
}
