<?php

namespace App\Services;

use App\Models\LaneRequest;
use App\Models\Staff;
use App\Models\StaffCheckIn;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use App\Repositories\Contracts\LaneRequestRepositoryInterface;
use App\Repositories\Contracts\StaffCheckInRepositoryInterface;
use App\Repositories\Contracts\TaskStaffAssignmentRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use DomainException;

class StaffShiftService
{
    public function __construct(
        protected TaskStaffAssignmentRepositoryInterface $assignmentRepository,
        protected TimetableTaskRepositoryInterface $taskRepository,
        protected LaneRequestRepositoryInterface $laneRequestRepository,
        protected StaffCheckInRepositoryInterface $staffCheckInRepository,
    ) {}

    /**
     * 運行情報ごとの現場到着チェックイン（打刻および対象運行計画の第1タスク出欠連動）
     */
    public function checkInStaff(Staff $staff, Timetable $timetable): StaffCheckIn
    {
        $this->ensureTimetableOperableOnMyShift($timetable, '現場到着の受付');

        $checkIn = $this->staffCheckInRepository->firstOrCreateForTimetableAndStaff(
            $timetable->id,
            $staff->id,
            now()
        );

        // 対象運行計画のタスクの中で最も早いタスクの出欠を checked_in に連動更新
        $firstAssignment = $this->assignmentRepository->findFirstAssignmentForStaffInTimetable(
            $staff->id,
            $timetable->id
        );

        if ($firstAssignment && $firstAssignment->attendance_status === 'assigned') {
            $this->assignmentRepository->update($firstAssignment, ['attendance_status' => 'checked_in']);
        }

        return $checkIn;
    }

    /**
     * 現場スタッフによる自身のタスク進捗更新（着手・完了報告）
     */
    public function updateStaffAssignmentStatus(Staff $staff, int $assignmentId, string $status): bool
    {
        $assignment = $this->assignmentRepository->findForStaffById($staff->id, $assignmentId);
        if (! $assignment) {
            return false;
        }

        $assignment->loadMissing('task.timetable');
        $this->ensureTimetableOperableOnMyShift($assignment->task?->timetable, 'タスク進捗の更新');

        return $this->assignmentRepository->update($assignment, ['attendance_status' => $status]);
    }

    /**
     * 班長による担当班タスク全体のステータス更新
     */
    public function updateLaneTaskStatus(TimetableTask $task, string $status): bool
    {
        $task->loadMissing('timetable');
        $this->ensureTimetableOperableOnMyShift($task->timetable, '班タスクのステータス更新');

        return $this->taskRepository->update($task, ['status' => $status]);
    }

    /**
     * 班長による管理者（本部）への人員不足要請・必要機材申請の作成
     *
     * @param  array<string, mixed>  $validated
     */
    public function createLaneRequestFromLeader(TimetableLane $timetableLane, Staff $staff, array $validated): LaneRequest
    {
        $timetableLane->loadMissing('timetable');
        $this->ensureTimetableOperableOnMyShift($timetableLane->timetable, '管理者への要請・申請');

        return $this->laneRequestRepository->create([
            'timetable_id' => $timetableLane->timetable_id,
            'timetable_lane_id' => $timetableLane->id,
            'timetable_task_id' => $validated['timetable_task_id'] ?? null,
            'requested_by_staff_id' => $staff->id,
            'request_type' => $validated['request_type'],
            'requested_count' => (int) $validated['requested_count'],
            'equipment_name' => $validated['equipment_name'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * 指定スタッフが指定タスク所属班の班長であるか判定
     */
    public function isLeaderOfTask(Staff $staff, TimetableTask $task): bool
    {
        $task->loadMissing('timetableLane');

        return $task->timetableLane !== null
            && (int) $task->timetableLane->lead_staff_id === (int) $staff->id;
    }

    /**
     * 対象運行計画がマイシフト上で操作可能（運行日当日以降かつ未完了）であることを検証
     *
     * @throws DomainException
     */
    protected function ensureTimetableOperableOnMyShift(?Timetable $timetable, string $actionLabel): void
    {
        if (! $timetable) {
            throw new DomainException("対象の運行計画が特定できないため、{$actionLabel}は実行できません。");
        }

        if ($timetable->isCompleted()) {
            throw new DomainException("運行完了した運行計画では{$actionLabel}を行えません。");
        }

        if (! $timetable->hasReachedOperationDay()) {
            throw new DomainException("運行日当日までは閲覧のみのため、{$actionLabel}は行えません。");
        }
    }
}
