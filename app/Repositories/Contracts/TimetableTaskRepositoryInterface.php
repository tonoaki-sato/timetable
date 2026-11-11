<?php

namespace App\Repositories\Contracts;

use App\Models\Lane;
use App\Models\TaskStaffAssignment;
use App\Models\TimetableTask;
use Illuminate\Database\Eloquent\Collection;

interface TimetableTaskRepositoryInterface
{
    /**
     * IDによる取得（アサイン情報及び担当スタッフ情報付き）
     */
    public function findWithAssignments(int $id): ?TimetableTask;

    /**
     * 指定タイムテーブルに紐づくタスク一覧を取得
     *
     * @return Collection<int, TimetableTask>
     */
    public function getByTimetableId(int $timetableId): Collection;

    /**
     * スタッフタイムライン画面用に指定タイムテーブルのタスク一覧を取得
     *
     * @return Collection<int, TimetableTask>
     */
    public function getForTimelineByTimetableId(int $timetableId): Collection;

    /**
     * マトリクス進行表用に指定タイムテーブルのタスク一覧（アサイン・班情報付き）を取得
     *
     * @return Collection<int, TimetableTask>
     */
    public function getWithAssignmentsAndLaneByTimetableId(int $timetableId): Collection;

    /**
     * 指定タスクと時間帯が重複し、指定スタッフが有効アサインされている他タスク一覧を取得
     *
     * @return Collection<int, TimetableTask>
     */
    public function getConflictingTasksForStaff(TimetableTask $task, int $staffId): Collection;

    /**
     * 指定タスクと時間帯が重複し、指定スタッフID群のいずれかが有効アサインされている他タスク一覧を取得
     *
     * @param  array<int, int>  $staffIds
     * @return Collection<int, TimetableTask>
     */
    public function getOverlappingTasksForStaffIds(TimetableTask $task, array $staffIds): Collection;

    /**
     * 指定レーンの有効（キャンセル以外）なタスク一覧をアサイン付きで取得
     *
     * @return Collection<int, TimetableTask>
     */
    public function getActiveForLaneWithAssignments(int $laneId): Collection;

    /**
     * 指定レーンの有効タスク一覧（除外タスクID指定対応）を取得
     *
     * @param  array<int, int>  $ignoredTaskIds
     * @return Collection<int, TimetableTask>
     */
    public function getActiveForLaneExcludingIds(int $laneId, array $ignoredTaskIds = []): Collection;

    /**
     * 指定された複数レーンの有効（キャンセル以外）なタスク一覧を取得
     *
     * @param  array<int, int>  $laneIds
     * @return Collection<int, TimetableTask>
     */
    public function getActiveForLanes(array $laneIds): Collection;

    /**
     * 指定レーンに紐づくタスク件数を取得
     */
    public function countByLane(Lane $lane): int;

    /**
     * 指定レーンの全タスクを別レーンへ一括移行
     */
    public function migrateLaneTasks(Lane $sourceLane, Lane $targetLane): int;

    /**
     * 指定レーンに紐づく全タスクを削除
     */
    public function deleteByLane(Lane $lane): int;

    /**
     * 新規タスク作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TimetableTask;

    /**
     * 複数タスクの一括新規作成
     *
     * @param  array<int, array<string, mixed>>  $tasksAttributes
     * @return Collection<int, TimetableTask>
     */
    public function createMany(array $tasksAttributes): Collection;

    /**
     * タスク更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(TimetableTask $task, array $attributes): bool;

    /**
     * タスク削除
     */
    public function delete(TimetableTask $task): bool;

    /**
     * スタッフをタスクへアサイン
     */
    public function assignStaff(TimetableTask $task, int $staffId, ?string $roleDescription = null): TaskStaffAssignment;

    /**
     * 複数スタッフをタスクへ一括アサイン
     *
     * @param  array<int, int>  $staffIds
     * @return Collection<int, TaskStaffAssignment>
     */
    public function bulkAssignStaff(TimetableTask $task, array $staffIds, ?string $roleDescription = null): Collection;

    /**
     * スタッフのアサイン解除
     */
    public function removeStaff(TimetableTask $task, int $staffId): bool;

    /**
     * アサイン出欠ステータス更新
     */
    public function updateAttendanceStatus(int $assignmentId, string $status): bool;
}
