<?php

namespace App\Services;

use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\Staff;
use App\Models\Timetable;
use App\Repositories\Contracts\LaneRepositoryInterface;
use App\Repositories\Contracts\LaneRequestRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LaneService
{
    public function __construct(
        protected LaneRepositoryInterface $laneRepository,
        protected TimetableRepositoryInterface $timetableRepository,
        protected TimetableTaskRepositoryInterface $taskRepository,
        protected StaffRepositoryInterface $staffRepository,
        protected LaneRequestRepositoryInterface $laneRequestRepository,
        protected TimetableTaskService $taskService,
    ) {}

    /**
     * 担当班管理の対象タイムテーブル選択画面用一覧を取得
     *
     * @return Collection<int, Timetable>
     */
    public function getTimetablesForSelect(): Collection
    {
        return $this->timetableRepository->getAllWithLanesAndLeadStaff();
    }

    /**
     * 指定タイムテーブルに紐づく担当班一覧を取得
     *
     * @return Collection<int, Lane>
     */
    public function getLanesForTimetable(Timetable $timetable): Collection
    {
        return $this->laneRepository->getForTimetable($timetable);
    }

    /**
     * 稼働可能なスタッフを所属・氏名順で取得
     *
     * @return Collection<int, Staff>
     */
    public function getActiveStaffOrderedByAffiliation(): Collection
    {
        return $this->staffRepository->getActiveOrderedByAffiliation();
    }

    /**
     * 詳細画面用の担当班を取得
     */
    public function findWithDetails(Timetable $timetable, int $laneId): ?Lane
    {
        return $this->laneRepository->findWithDetails($timetable, $laneId);
    }

    /**
     * 編集画面用に必要なリレーションをロード
     */
    public function loadEditRelations(Lane $lane): Lane
    {
        return $lane->loadMissing(['leadStaff', 'fixedStaff']);
    }

    /**
     * 新規担当班の登録および固定スタッフの同期
     *
     * @param  array<string, mixed>  $data
     */
    public function createLane(Timetable $timetable, array $data): Lane
    {
        $fixedStaffIds = $data['fixed_staff_ids'] ?? [];
        unset($data['fixed_staff_ids']);

        if (! isset($data['display_order']) || $data['display_order'] === null) {
            $data['display_order'] = $this->laneRepository->getMaxDisplayOrder($timetable) + 10;
        }

        $lane = $this->laneRepository->create($timetable, $data);

        if ($lane->lead_staff_id && ! in_array((int) $lane->lead_staff_id, array_map('intval', $fixedStaffIds), true)) {
            $fixedStaffIds[] = (int) $lane->lead_staff_id;
        }

        if (! empty($fixedStaffIds)) {
            $this->taskService->syncLaneFixedStaffToTasks($lane, $fixedStaffIds);
        }

        return $lane;
    }

    /**
     * 担当班情報の更新および班長・固定スタッフの同期
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateLane(Lane $lane, array $validated, bool $hasFixedStaffInput): Lane
    {
        $oldLeadStaffId = $lane->lead_staff_id ? (int) $lane->lead_staff_id : null;
        $fixedStaffIds = $validated['fixed_staff_ids'] ?? [];
        unset($validated['fixed_staff_ids']);

        $this->laneRepository->update($lane, $validated);

        if ($lane->lead_staff_id) {
            $this->laneRepository->syncFixedStaffWithoutDetaching($lane, [(int) $lane->lead_staff_id]);
            $this->staffRepository->updateStaffTypeByIds([(int) $lane->lead_staff_id], 'fixed');
            $this->taskService->syncLaneLeadToTasks($lane, $oldLeadStaffId);
        }

        if ($hasFixedStaffInput) {
            if ($lane->lead_staff_id && ! in_array((int) $lane->lead_staff_id, array_map('intval', $fixedStaffIds), true)) {
                $fixedStaffIds[] = (int) $lane->lead_staff_id;
            }
            $this->taskService->syncLaneFixedStaffToTasks($lane, $fixedStaffIds);
        }

        return $lane->fresh(['leadStaff', 'fixedStaff']) ?? $lane;
    }

    /**
     * 担当班の表示順を一括更新
     *
     * @param  array<int, int>  $laneIds
     */
    public function reorderLanes(Timetable $timetable, array $laneIds): void
    {
        $this->laneRepository->reorder($timetable, $laneIds);
    }

    /**
     * 担当班に紐づくタスク数を取得
     */
    public function countLaneTasks(Lane $lane): int
    {
        return $this->taskRepository->countByLane($lane);
    }

    /**
     * 同一タイムテーブル内の移行先レーンを取得
     */
    public function findTargetLaneForMigration(Timetable $timetable, int $targetLaneId): ?Lane
    {
        return $this->laneRepository->findForTimetableById($timetable, $targetLaneId);
    }

    /**
     * タスクを別レーンへ引き継いでレーンを削除
     */
    public function migrateTasksAndDeleteLane(Lane $lane, Lane $targetLane): void
    {
        DB::transaction(function () use ($lane, $targetLane): void {
            $this->taskRepository->migrateLaneTasks($lane, $targetLane);
            $this->laneRepository->delete($lane);
        });
    }

    /**
     * 紐づくタスクごとレーンを一括削除
     */
    public function cascadeDeleteTasksAndLane(Lane $lane): void
    {
        DB::transaction(function () use ($lane): void {
            $this->taskRepository->deleteByLane($lane);
            $this->laneRepository->delete($lane);
        });
    }

    /**
     * レーン単体の削除
     */
    public function deleteLane(Lane $lane): bool
    {
        return $this->laneRepository->delete($lane);
    }

    /**
     * 班長からの応援要請・機材申請ステータスを更新
     */
    public function updateLaneRequestStatus(LaneRequest $laneRequest, string $status, ?int $userId): LaneRequest
    {
        $this->laneRequestRepository->update($laneRequest, [
            'status' => $status,
            'resolved_by_user_id' => $userId,
            'resolved_at' => in_array($status, ['approved', 'resolved', 'rejected'], true) ? now() : null,
        ]);

        return $laneRequest;
    }
}
