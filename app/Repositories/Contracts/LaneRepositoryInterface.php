<?php

namespace App\Repositories\Contracts;

use App\Models\Lane;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Collection;

interface LaneRepositoryInterface
{
    /**
     * 指定タイムテーブルに紐づく担当班一覧を関連リレーション付きで取得（表示順昇順）
     *
     * @return Collection<int, Lane>
     */
    public function getForTimetable(Timetable $timetable): Collection;

    /**
     * 詳細画面用の担当班を取得（班長・固定スタッフ・タスク・要請・資材リレーション付き）
     */
    public function findWithDetails(Timetable $timetable, int $laneId): ?Lane;

    /**
     * 新規担当班を作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(Timetable $timetable, array $attributes): Lane;

    /**
     * 担当班を更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Lane $lane, array $attributes): bool;

    /**
     * 担当班を削除
     */
    public function delete(Lane $lane): bool;

    /**
     * 担当班の並び順を一括更新
     *
     * @param  array<int, int>  $laneIds
     */
    public function reorder(Timetable $timetable, array $laneIds): void;

    /**
     * 指定タイムテーブルにおける最大の表示順を取得
     */
    public function getMaxDisplayOrder(Timetable $timetable): int;
}
