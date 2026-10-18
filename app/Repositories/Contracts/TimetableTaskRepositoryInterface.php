<?php

namespace App\Repositories\Contracts;

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
     * 新規タスク作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): TimetableTask;

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
     * スタッフのアサイン解除
     */
    public function removeStaff(TimetableTask $task, int $staffId): bool;

    /**
     * アサイン出欠ステータス更新
     */
    public function updateAttendanceStatus(int $assignmentId, string $status): bool;
}
