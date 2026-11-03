<?php

namespace App\Services;

use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\TimetableTask;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StaffAllocationService
{
    public function __construct(
        protected ConflictDetectionService $conflictService
    ) {}

    /**
     * 指定スタッフが別班タスクで「人数余剰（定員超過）」により他班へ融通可能な状態か判定し、
     * 融通元となる重複タスク一覧を返却する（余剰でない通常重複が1件でもあれば空コレクションを返す）
     *
     * @return Collection<int, TimetableTask>
     */
    public function getTransferableSurplusTasks(TimetableTask $targetTask, int $staffId): Collection
    {
        $conflictingTasks = $this->conflictService->getConflictingTasks($targetTask, $staffId);
        if ($conflictingTasks->isEmpty()) {
            return new Collection;
        }

        foreach ($conflictingTasks as $ovTask) {
            $leadId = $ovTask->laneRelation?->lead_staff_id ? (int) $ovTask->laneRelation->lead_staff_id : null;
            // 班長は自班タスクから自動引き抜きしない
            if ($leadId !== null && $leadId === $staffId) {
                return new Collection;
            }

            // 必要人数に対して余剰（assigned_count > required_people_count かつ !is_at_least）でない場合は融通不可
            if ($ovTask->required_people_count <= 0 || $ovTask->is_at_least || $ovTask->assigned_count <= $ovTask->required_people_count) {
                return new Collection;
            }
        }

        return $conflictingTasks;
    }

    /**
     * タスクに対するスタッフ適合度（空き状況・班所属・稼働負荷）を判定し、優先度順にソートしたコレクションを返却
     *
     * @param  Collection<int, Staff>  $activeStaff
     * @return Collection<int, Staff>
     */
    public function getPrioritizedStaffForTask(TimetableTask $task, Collection $activeStaff): Collection
    {
        if ($activeStaff->isEmpty()) {
            return new Collection;
        }

        $conflictsMap = $this->conflictService->getConflictsMapForStaffList($task, $activeStaff);
        $staffIds = $activeStaff->pluck('id')->all();

        // 当日累計稼働時間の算出（同一運行日）
        $dayAssignments = TaskStaffAssignment::whereHas('task', function ($query) use ($task) {
            $query->where('timetable_id', $task->timetable_id);
        })
            ->whereIn('staff_id', $staffIds)
            ->where('attendance_status', '!=', 'absent')
            ->with('task')
            ->get();

        $staffDayMinutes = [];
        foreach ($dayAssignments as $assignment) {
            $t = $assignment->task;
            if ($t && $t->start_time && $t->end_time) {
                $start = Carbon::parse($t->start_time);
                $end = Carbon::parse($t->end_time);
                $diff = max(0, $start->diffInMinutes($end));
                $staffDayMinutes[$assignment->staff_id] = ($staffDayMinutes[$assignment->staff_id] ?? 0) + $diff;
            }
        }

        $task->loadMissing('laneRelation.fixedStaff');
        if ($activeStaff instanceof \Illuminate\Database\Eloquent\Collection) {
            $activeStaff->loadMissing('fixedLanes');
        }

        $taskLaneName = $task->lane ?: ($task->laneRelation?->name ?? '');
        $leadStaffId = $task->laneRelation?->lead_staff_id
            ? (int) $task->laneRelation->lead_staff_id
            : null;
        $taskLaneFixedStaffIds = $task->laneRelation
            ? $task->laneRelation->fixedStaff->pluck('id')->map(fn ($id) => (int) $id)->all()
            : [];

        foreach ($activeStaff as $staff) {
            $conflict = $conflictsMap[$staff->id] ?? null;
            $isLaneLead = $leadStaffId !== null && (int) $staff->id === $leadStaffId;
            $isFixedInTaskLane = in_array((int) $staff->id, $taskLaneFixedStaffIds, true);
            $isSameLane = $isLaneLead || $isFixedInTaskLane;

            if (! $isSameLane && ! empty($taskLaneName) && ! empty($staff->affiliation)) {
                $isSameLane = (mb_strpos($staff->affiliation, $taskLaneName) !== false)
                    || (mb_strpos($taskLaneName, $staff->affiliation) !== false);
            }

            $isOtherLaneFixed = ! $isSameLane && ($staff->isFixed() || $staff->fixedLanes->isNotEmpty());
            $isSurplusFixed = false;
            $surplusType = null;
            $surplusSourceTask = null;

            if ($conflict === null && $isOtherLaneFixed) {
                // パターン1: 自班タスクが存在しない空き時間帯の固定スタッフ（時間帯余剰）
                $isSurplusFixed = true;
                $surplusType = 'idle';
            } elseif ($conflict !== null) {
                // パターン2: 自班タスクの必要人数を超過している余剰固定スタッフ（人数余剰）
                $transferable = $this->getTransferableSurplusTasks($task, (int) $staff->id);
                if ($transferable->isNotEmpty()) {
                    $isSurplusFixed = true;
                    $surplusType = 'over_capacity';
                    $surplusSourceTask = $transferable->first();
                }
            }

            $minutes = $staffDayMinutes[$staff->id] ?? 0;

            // 優先度ティア判定: 1=推奨(自班の班長・固定スタッフで空き), 2=充足スタッフ空き または 他班の余剰固定スタッフ, 3=重複あり
            $tier = 2;
            if ($conflict !== null && ! $isSurplusFixed) {
                $tier = 3;
            } elseif ($isSameLane && $conflict === null) {
                $tier = 1;
            }

            $staff->conflict_task = $isSurplusFixed ? null : $conflict;
            $staff->surplus_source_task = $surplusSourceTask;
            $staff->is_surplus_fixed = $isSurplusFixed;
            $staff->surplus_type = $surplusType;
            $staff->is_lane_lead = $isLaneLead;
            $staff->is_fixed_in_lane = $isFixedInTaskLane;
            $staff->is_same_lane = $isSameLane;
            $staff->today_assigned_minutes = $minutes;
            $staff->priority_tier = $tier;
            $staff->is_checked_in = (bool) $staff->is_checked_in;
        }

        return $activeStaff->sort(function (Staff $a, Staff $b) {
            // 1. 優先度ティア順 (1=推奨同班空き, 2=充足スタッフ空き/余剰固定スタッフ, 3=重複)
            if ($a->priority_tier !== $b->priority_tier) {
                return $a->priority_tier <=> $b->priority_tier;
            }

            // 2. 当該レーンの班長を最優先
            if ($a->is_lane_lead !== $b->is_lane_lead) {
                return $b->is_lane_lead <=> $a->is_lane_lead;
            }

            // 3. 当該レーンの固定スタッフを優先
            if (($a->is_fixed_in_lane ?? false) !== ($b->is_fixed_in_lane ?? false)) {
                return ($b->is_fixed_in_lane ?? false) <=> ($a->is_fixed_in_lane ?? false);
            }

            // 4. 充足スタッフ（空き）を他班の余剰固定スタッフより優先
            if ($a->is_surplus_fixed !== $b->is_surplus_fixed) {
                return $a->is_surplus_fixed <=> $b->is_surplus_fixed;
            }

            // 5. 現場チェックイン（到着受付）済みを優先
            if ($a->is_checked_in !== $b->is_checked_in) {
                return $b->is_checked_in <=> $a->is_checked_in;
            }

            // 6. 当日累計稼働時間が少ない順（負荷平準化）
            if ($a->today_assigned_minutes !== $b->today_assigned_minutes) {
                return $a->today_assigned_minutes <=> $b->today_assigned_minutes;
            }

            return strcmp($a->kana ?? '', $b->kana ?? '');
        })->values();
    }
}
