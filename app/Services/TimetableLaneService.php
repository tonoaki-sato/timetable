<?php

namespace App\Services;

use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Repositories\Contracts\LaneRepositoryInterface;
use App\Repositories\Contracts\LaneRequestRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use App\Repositories\Contracts\TimetableLaneRepositoryInterface;
use App\Repositories\Contracts\TimetableTaskRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TimetableLaneService
{
    public function __construct(
        protected TimetableLaneRepositoryInterface $timetableLaneRepository,
        protected LaneRepositoryInterface $laneRepository,
        protected TimetableTaskRepositoryInterface $taskRepository,
        protected StaffRepositoryInterface $staffRepository,
        protected LaneRequestRepositoryInterface $laneRequestRepository,
        protected TimetableTaskService $taskService,
    ) {}

    /**
     * 指定タイムテーブルに紐づく運行日別担当班編成一覧を取得
     *
     * @return Collection<int, TimetableLane>
     */
    public function getTimetableLanesForTimetable(Timetable $timetable): Collection
    {
        return $this->timetableLaneRepository->getForTimetable($timetable);
    }

    /**
     * 指定タイムテーブルで未追加の有効担当班マスター一覧を取得
     *
     * @return Collection<int, Lane>
     */
    public function getAvailableMasterLanes(Timetable $timetable): Collection
    {
        return $this->laneRepository->getAvailableForTimetable($timetable);
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
     * 詳細取得
     */
    public function findWithDetails(Timetable $timetable, int $timetableLaneId): ?TimetableLane
    {
        return $this->timetableLaneRepository->findWithDetails($timetable, $timetableLaneId);
    }

    /**
     * 単一の担当班を当該運行日の編成へ追加および固定スタッフの同期
     *
     * @param  array<string, mixed>  $data
     */
    public function createTimetableLane(Timetable $timetable, array $data): TimetableLane
    {
        $fixedStaffIds = $data['fixed_staff_ids'] ?? [];
        unset($data['fixed_staff_ids']);

        if (! isset($data['display_order']) || $data['display_order'] === null) {
            $data['display_order'] = $this->timetableLaneRepository->getMaxDisplayOrder($timetable) + 10;
        }

        $timetableLane = $this->timetableLaneRepository->create($timetable, $data);

        if ($timetableLane->lead_staff_id && ! in_array((int) $timetableLane->lead_staff_id, array_map('intval', $fixedStaffIds), true)) {
            $fixedStaffIds[] = (int) $timetableLane->lead_staff_id;
        }

        if (! empty($fixedStaffIds)) {
            $this->taskService->syncTimetableLaneFixedStaffToTasks($timetableLane, $fixedStaffIds);
        }

        return $timetableLane->fresh(['lane', 'leadStaff', 'fixedStaff'])?->loadCount('tasks') ?? $timetableLane;
    }

    /**
     * 担当班マスターから複数の班を当該運行日へ一括追加する
     *
     * @param  array<int, int|string>  $laneIds
     * @return Collection<int, TimetableLane>
     */
    public function bulkCreateFromMasters(Timetable $timetable, array $laneIds): Collection
    {
        $cleanLaneIds = array_values(array_unique(array_map('intval', array_filter($laneIds))));
        if ($cleanLaneIds === []) {
            return new Collection;
        }

        return DB::transaction(function () use ($timetable, $cleanLaneIds): Collection {
            $existingMasterIds = $timetable->timetableLanes()
                ->pluck('lane_id')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $targetMasterIds = array_values(array_diff($cleanLaneIds, $existingMasterIds));
            if ($targetMasterIds === []) {
                return new Collection;
            }

            $masters = $this->laneRepository->getActiveOrdered()
                ->whereIn('id', $targetMasterIds)
                ->values();

            $order = $this->timetableLaneRepository->getMaxDisplayOrder($timetable) + 10;
            $created = new Collection;

            foreach ($masters as $master) {
                $timetableLane = $this->timetableLaneRepository->create($timetable, [
                    'lane_id' => $master->id,
                    'lead_staff_id' => null,
                    'display_order' => $order,
                    'notes' => null,
                ]);
                $order += 10;
                $created->push($timetableLane);
            }

            return $created;
        });
    }

    /**
     * 運行日別担当班編成情報の更新および班長・固定スタッフの同期
     *
     * @param  array<string, mixed>  $validated
     */
    public function updateTimetableLane(TimetableLane $timetableLane, array $validated, bool $hasFixedStaffInput): TimetableLane
    {
        $oldLeadStaffId = $timetableLane->lead_staff_id ? (int) $timetableLane->lead_staff_id : null;
        $fixedStaffIds = $validated['fixed_staff_ids'] ?? [];
        unset($validated['fixed_staff_ids']);

        $this->timetableLaneRepository->update($timetableLane, $validated);

        if ($timetableLane->lead_staff_id) {
            $this->timetableLaneRepository->syncFixedStaffWithoutDetaching($timetableLane, [(int) $timetableLane->lead_staff_id]);
            $this->taskService->syncTimetableLaneLeadToTasks($timetableLane, $oldLeadStaffId);
        }

        if ($hasFixedStaffInput) {
            if ($timetableLane->lead_staff_id && ! in_array((int) $timetableLane->lead_staff_id, array_map('intval', $fixedStaffIds), true)) {
                $fixedStaffIds[] = (int) $timetableLane->lead_staff_id;
            }
            $this->taskService->syncTimetableLaneFixedStaffToTasks($timetableLane, $fixedStaffIds);
        }

        return $timetableLane->fresh(['lane', 'leadStaff', 'fixedStaff'])?->loadCount('tasks') ?? $timetableLane;
    }

    /**
     * 運行日別担当班編成の表示順を一括更新
     *
     * @param  array<int, int>  $timetableLaneIds
     */
    public function reorderTimetableLanes(Timetable $timetable, array $timetableLaneIds): void
    {
        $this->timetableLaneRepository->reorder($timetable, $timetableLaneIds);
    }

    /**
     * 運行日別担当班編成に紐づくタスク数を取得
     */
    public function countTimetableLaneTasks(TimetableLane $timetableLane): int
    {
        return $this->taskRepository->countByTimetableLane($timetableLane);
    }

    /**
     * 同一タイムテーブル内の移行先日別編成を取得
     */
    public function findTargetTimetableLaneForMigration(Timetable $timetable, int $targetTimetableLaneId): ?TimetableLane
    {
        return $this->timetableLaneRepository->findForTimetableById($timetable, $targetTimetableLaneId);
    }

    /**
     * タスクを別編成へ引き継いで日別編成を解除
     */
    public function migrateTasksAndDeleteTimetableLane(TimetableLane $timetableLane, TimetableLane $targetTimetableLane): void
    {
        DB::transaction(function () use ($timetableLane, $targetTimetableLane): void {
            $this->taskRepository->migrateTimetableLaneTasks($timetableLane, $targetTimetableLane);
            $this->timetableLaneRepository->delete($timetableLane);
        });
    }

    /**
     * 紐づくタスクごと日別編成を一括削除
     */
    public function cascadeDeleteTasksAndTimetableLane(TimetableLane $timetableLane): void
    {
        DB::transaction(function () use ($timetableLane): void {
            $this->taskRepository->deleteByTimetableLane($timetableLane);
            $this->timetableLaneRepository->delete($timetableLane);
        });
    }

    /**
     * 日別編成単体の解除・削除
     */
    public function deleteTimetableLane(TimetableLane $timetableLane): bool
    {
        return $this->timetableLaneRepository->delete($timetableLane);
    }

    /**
     * 削除方針（タスク移行・カスケード削除・単体解除）に応じて日別編成削除を実行
     *
     * @return array{status: string, message: string, task_count: int}
     */
    public function deleteTimetableLaneWithStrategy(
        Timetable $timetable,
        TimetableLane $timetableLane,
        ?int $migrateToTimetableLaneId,
        bool $cascadeDeleteTasks,
    ): array {
        $timetableLane->loadMissing('lane');
        $laneName = $timetableLane->name;
        $taskCount = $this->countTimetableLaneTasks($timetableLane);

        if ($migrateToTimetableLaneId !== null && $migrateToTimetableLaneId > 0) {
            $targetTimetableLane = $this->findTargetTimetableLaneForMigration($timetable, $migrateToTimetableLaneId);

            if (! $targetTimetableLane || $targetTimetableLane->id === $timetableLane->id) {
                return [
                    'status' => 'invalid_target',
                    'message' => '有効な移行先担当班を選択してください。',
                    'task_count' => $taskCount,
                ];
            }

            $this->migrateTasksAndDeleteTimetableLane($timetableLane, $targetTimetableLane);

            return [
                'status' => 'migrated',
                'message' => "担当班「{$laneName}」の作業タスク {$taskCount} 件を「{$targetTimetableLane->name}」に引き継ぎ、編成から解除しました。",
                'task_count' => $taskCount,
            ];
        }

        if ($cascadeDeleteTasks) {
            $this->cascadeDeleteTasksAndTimetableLane($timetableLane);

            return [
                'status' => 'cascaded',
                'message' => "担当班「{$laneName}」の編成および紐づく作業タスク {$taskCount} 件をすべて削除しました。",
                'task_count' => $taskCount,
            ];
        }

        if ($taskCount > 0) {
            return [
                'status' => 'blocked',
                'message' => "担当班「{$laneName}」には {$taskCount} 件の作業タスクが紐づいているため解除できません。引き継ぎ先を選択するか、タスクを含めて削除してください。",
                'task_count' => $taskCount,
            ];
        }

        $this->deleteTimetableLane($timetableLane);

        return [
            'status' => 'deleted',
            'message' => "担当班「{$laneName}」の編成を解除しました。",
            'task_count' => 0,
        ];
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
