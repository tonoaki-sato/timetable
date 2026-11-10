<?php

namespace App\Repositories\Contracts;

use App\Models\LaneRequest;
use Illuminate\Database\Eloquent\Collection;

interface LaneRequestRepositoryInterface
{
    /**
     * 指定レーン・タスク群に紐づく未完了の現場要請を取得
     *
     * @param  array<int, int>  $laneIds
     * @param  array<int, int>  $taskIds
     * @return Collection<int, LaneRequest>
     */
    public function getPendingForLanesOrTasks(array $laneIds, array $taskIds, int $limit = 6): Collection;

    /**
     * 新規要請を作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): LaneRequest;

    /**
     * 要請を更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(LaneRequest $laneRequest, array $attributes): bool;

    /**
     * 指定タイムテーブルに紐づく要請を一括削除
     */
    public function deleteByTimetableId(int $timetableId): int;
}
