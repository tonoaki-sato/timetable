<?php

namespace App\Repositories\Contracts;

use App\Models\TaskStaffAssignment;
use Illuminate\Database\Eloquent\Collection;

interface TaskStaffAssignmentRepositoryInterface
{
    /**
     * 指定運行日・指定スタッフ群の有効アサイン一覧（タスク付き）を取得
     *
     * @param  array<int, int>  $staffIds
     * @return Collection<int, TaskStaffAssignment>
     */
    public function getActiveByTimetableAndStaffIds(int $timetableId, array $staffIds): Collection;

    /**
     * 指定運行計画・指定スタッフにおける最初のタスクアサインを取得
     */
    public function findFirstAssignmentForStaffInTimetable(int $staffId, int $timetableId): ?TaskStaffAssignment;

    /**
     * 指定スタッフに紐づく特定アサインをIDで取得
     */
    public function findForStaffById(int $staffId, int $assignmentId): ?TaskStaffAssignment;

    /**
     * タスクIDとスタッフIDでアサインを取得
     */
    public function findByTaskAndStaff(int $taskId, int $staffId): ?TaskStaffAssignment;

    /**
     * タスクIDとスタッフIDのアサインが存在するか判定
     */
    public function existsByTaskAndStaff(int $taskId, int $staffId): bool;

    /**
     * 指定タスクにアサイン済みのスタッフID配列を取得
     *
     * @return array<int, int>
     */
    public function getStaffIdsForTask(int $taskId): array;

    /**
     * 条件に一致するアサインを取得または新規作成
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $values
     */
    public function firstOrCreate(array $attributes, array $values = []): TaskStaffAssignment;

    /**
     * 新規アサインを作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TaskStaffAssignment;

    /**
     * アサイン情報を更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(TaskStaffAssignment $assignment, array $attributes): bool;

    /**
     * アサインを削除
     */
    public function delete(TaskStaffAssignment $assignment): bool;

    /**
     * 指定タスク・スタッフ・役割のアサインを削除
     */
    public function deleteByTaskAndStaffAndRole(int $taskId, int $staffId, string $roleDescription): int;

    /**
     * 指定タスクID群に紐づくアサインを一括削除
     *
     * @param  iterable<int>  $taskIds
     */
    public function deleteByTaskIds(iterable $taskIds): int;
}
