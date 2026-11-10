<?php

namespace App\Repositories\Contracts;

use App\Models\Lane;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Collection;

interface LaneRepositoryInterface
{
    /**
     * IDによる担当班の取得
     */
    public function findById(int $id): ?Lane;

    /**
     * 固定スタッフリレーション付きでIDにより担当班を取得
     */
    public function findWithFixedStaff(int $id): ?Lane;

    /**
     * タイムテーブルIDと班名（または「班」を除いた名称）から担当班を取得
     */
    public function findByTimetableAndNameOrCleanName(int $timetableId, string $laneName, string $cleanName): ?Lane;

    /**
     * 指定タイムテーブルに属する特定IDの担当班を取得
     */
    public function findForTimetableById(Timetable $timetable, int $laneId): ?Lane;

    /**
     * 全担当班を表示順で取得
     *
     * @return Collection<int, Lane>
     */
    public function getAllOrdered(): Collection;

    /**
     * 指定タイムテーブルに紐づく担当班一覧を関連リレーション付きで取得（表示順昇順）
     *
     * @return Collection<int, Lane>
     */
    public function getForTimetable(Timetable $timetable): Collection;

    /**
     * 指定タイムテーブルの担当班を表示順で取得（班長リレーション・ID絞り込み対応）
     *
     * @param  array<int, int>|null  $laneIds
     * @return Collection<int, Lane>
     */
    public function getOrderedWithLeadForTimetable(Timetable $timetable, ?array $laneIds = null): Collection;

    /**
     * 指定タイムテーブルで班長が設定されている担当班一覧を取得
     *
     * @return Collection<int, Lane>
     */
    public function getLeadLanesForTimetable(Timetable $timetable): Collection;

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

    /**
     * 担当班の固定スタッフID一覧を取得
     *
     * @return array<int, int>
     */
    public function getFixedStaffIds(Lane $lane): array;

    /**
     * 担当班の固定スタッフを同期
     *
     * @param  array<int, int>  $staffIds
     */
    public function syncFixedStaff(Lane $lane, array $staffIds): void;

    /**
     * 担当班に固定スタッフをデタッチなしで追加同期
     *
     * @param  array<int, int>  $staffIds
     */
    public function syncFixedStaffWithoutDetaching(Lane $lane, array $staffIds): void;
}
