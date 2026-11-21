<?php

namespace App\Actions\Timetable;

use App\DTOs\Timetable\CloneTimetableOptionsDTO;
use App\Enums\LayoutItemStatus;
use App\Models\EquipmentLogisticsPlan;
use App\Models\Lane;
use App\Models\LayoutArea;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\SiteMapPlacement;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
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

            $logisticsPlansCount = 0;
            if ($options->copyLogisticsPlans) {
                $logisticsPlansCount = $this->cloneLogisticsPlans($source, $newTimetable, $dayOffset);
            }

            $layoutItemsCount = 0;
            $placementsCount = 0;
            if ($options->copySiteLayouts) {
                [$layoutItemsCount, $placementsCount] = $this->cloneSiteLayouts(
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

        $assignmentRows = [];
        $now = now()->format('Y-m-d H:i:s');

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

                    $assignmentRows[] = [
                        'timetable_task_id' => $newTask->id,
                        'staff_id' => $assignment->staff_id,
                        'role_description' => $assignment->role_description,
                        'attendance_status' => 'assigned',
                        'notes' => $assignment->notes,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                    $assignmentsCount++;
                }
            }
        }

        $this->chunkInsert(TaskStaffAssignment::class, $assignmentRows);

        return [$tasksCount, $assignmentsCount];
    }

    /**
     * 資材搬出入計画を複製（日付シフト・ステータス初期化）
     */
    protected function cloneLogisticsPlans(
        Timetable $source,
        Timetable $newTimetable,
        int $dayOffset
    ): int {
        $sourcePlans = EquipmentLogisticsPlan::where('timetable_id', $source->id)
            ->orderBy('id')
            ->get();

        $planRows = [];
        $now = now()->format('Y-m-d H:i:s');

        foreach ($sourcePlans as $sourcePlan) {
            $planRows[] = [
                'timetable_id' => $newTimetable->id,
                'equipment_id' => $sourcePlan->equipment_id,
                'target_date' => $sourcePlan->target_date?->copy()->addDays($dayOffset)->format('Y-m-d'),
                'day_phase' => $sourcePlan->day_phase,
                'action_type' => $sourcePlan->action_type,
                'title' => $sourcePlan->title,
                'quantity' => $sourcePlan->quantity,
                'quantity_note' => $sourcePlan->quantity_note,
                'start_time' => $sourcePlan->start_time,
                'end_time' => $sourcePlan->end_time,
                'from_location' => $sourcePlan->from_location,
                'to_location' => $sourcePlan->to_location,
                'from_location_id' => $sourcePlan->from_location_id,
                'to_location_id' => $sourcePlan->to_location_id,
                'operator_type' => $sourcePlan->operator_type,
                'witness_required' => (bool) $sourcePlan->witness_required,
                'witness_staff_id' => $sourcePlan->witness_staff_id,
                'status' => 'planned',
                'notes' => $sourcePlan->notes,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        $this->chunkInsert(EquipmentLogisticsPlan::class, $planRows);

        return count($planRows);
    }

    /**
     * 現場配置計画（LayoutMap / LayoutArea / LayoutItem）および白地図配置（SiteMapPlacement）を複製
     *
     * @return array{0: int, 1: int}
     */
    protected function cloneSiteLayouts(
        Timetable $source,
        Timetable $newTimetable,
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
                    'location_id' => $sourceArea->location_id,
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

        if ($source->site_base_map_id !== null) {
            $newTimetable->update([
                'site_base_map_id' => $source->site_base_map_id,
            ]);
        }

        $sourcePlacements = SiteMapPlacement::query()
            ->where('timetable_id', $source->id)
            ->orderBy('id')
            ->get();

        $placementRows = [];
        $now = now()->format('Y-m-d H:i:s');

        foreach ($sourcePlacements as $sourcePlacement) {
            $newLayoutItemId = $sourcePlacement->layout_item_id
                ? ($layoutItemIdMap[$sourcePlacement->layout_item_id] ?? null)
                : null;

            $placementRows[] = [
                'timetable_id' => $newTimetable->id,
                'site_base_map_id' => $sourcePlacement->site_base_map_id,
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
                'is_rotation_locked' => (bool) $sourcePlacement->is_rotation_locked,
                'show_hygiene_circle' => (bool) $sourcePlacement->show_hygiene_circle,
                'arc_start_angle' => $sourcePlacement->arc_start_angle,
                'arc_end_angle' => $sourcePlacement->arc_end_angle,
                'z_index' => $sourcePlacement->z_index,
                'notes' => $sourcePlacement->notes,
                'meta_data' => $sourcePlacement->meta_data !== null
                    ? json_encode($sourcePlacement->meta_data, JSON_UNESCAPED_UNICODE)
                    : null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
            $placementsCount++;
        }

        $this->chunkInsert(SiteMapPlacement::class, $placementRows);

        return [$layoutItemsCount, $placementsCount];
    }

    /**
     * チャンク単位でレコードを一括挿入する
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function chunkInsert(string $modelClass, array $rows, int $chunkSize = 500): void
    {
        if ($rows === []) {
            return;
        }

        foreach (array_chunk($rows, $chunkSize) as $chunk) {
            $modelClass::insert($chunk);
        }
    }
}
