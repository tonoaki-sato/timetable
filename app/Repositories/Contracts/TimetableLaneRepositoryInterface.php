<?php

namespace App\Repositories\Contracts;

use App\Models\Timetable;
use App\Models\TimetableLane;
use Illuminate\Database\Eloquent\Collection;

interface TimetableLaneRepositoryInterface
{
    /**
     * IDによる運行日別担当班編成の取得
     */
    public function findById(int $id): ?TimetableLane;

    /**
     * 固定スタッフ・マスターリレーション付きでIDにより運行日別担当班編成を取得
     */
    public function findWithFixedStaff(int $id): ?TimetableLane;

    /**
     * 指定タイムテーブルに属する複数IDの運行日別担当班編成を固定スタッフ込み・表示順で取得
     *
     * @param  array<int, int>  $timetableLaneIds
     * @return Collection<int, TimetableLane>
     */
    public function getByIdsWithFixedStaff(int $timetableId, array $timetableLaneIds): Collection;

    /**
     * タイムテーブルIDと担当班マスターIDから運行日別担当班編成を取得
     */
    public function findByTimetableAndLaneId(int $timetableId, int $laneId): ?TimetableLane;

    /**
     * 指定タイムテーブルに属する特定IDの運行日別担当班編成を取得
     */
    public function findForTimetableById(Timetable $timetable, int $timetableLaneId): ?TimetableLane;

    /**
     * 全運行日別担当班編成を表示順で取得
     *
     * @return Collection<int, TimetableLane>
     */
    public function getAllOrdered(): Collection;

    /**
     * 指定タイムテーブルに紐づく運行日別担当班編成一覧を関連リレーション付きで取得（表示順昇順）
     *
     * @return Collection<int, TimetableLane>
     */
    public function getForTimetable(Timetable $timetable): Collection;

    /**
     * 指定タイムテーブルの運行日別担当班編成一覧をPDF出力用に取得（班長付き、表示順昇順）
     *
     * @return Collection<int, TimetableLane>
     */
    public function getForTimetablePrint(Timetable $timetable): Collection;

    /**
     * 指定タイムテーブルの運行日別担当班編成を表示順で取得（班長・マスターリレーション・ID絞り込み対応）
     *
     * @param  array<int, int>|null  $timetableLaneIds
     * @return Collection<int, TimetableLane>
     */
    public function getOrderedWithLeadForTimetable(Timetable $timetable, ?array $timetableLaneIds = null): Collection;

    /**
     * 指定タイムテーブルで班長が設定されている運行日別担当班編成一覧を取得
     *
     * @return Collection<int, TimetableLane>
     */
    public function getLeadTimetableLanesForTimetable(Timetable $timetable): Collection;

    /**
     * 詳細取得（班長・固定スタッフ・タスク・要請・マスターリレーション付き）
     */
    public function findWithDetails(Timetable $timetable, int $timetableLaneId): ?TimetableLane;

    /**
     * 新規運行日別担当班編成を作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(Timetable $timetable, array $attributes): TimetableLane;

    /**
     * 運行日別担当班編成を更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(TimetableLane $timetableLane, array $attributes): bool;

    /**
     * 運行日別担当班編成を削除
     */
    public function delete(TimetableLane $timetableLane): bool;

    /**
     * 運行日別担当班編成の並び順を一括更新
     *
     * @param  array<int, int>  $timetableLaneIds
     */
    public function reorder(Timetable $timetable, array $timetableLaneIds): void;

    /**
     * 指定タイムテーブルにおける最大の表示順を取得
     */
    public function getMaxDisplayOrder(Timetable $timetable): int;

    /**
     * 運行日別担当班編成の固定スタッフID一覧を取得
     *
     * @return array<int, int>
     */
    public function getFixedStaffIds(TimetableLane $timetableLane): array;

    /**
     * 運行日別担当班編成の固定スタッフを同期
     *
     * @param  array<int, int>  $staffIds
     */
    public function syncFixedStaff(TimetableLane $timetableLane, array $staffIds): void;

    /**
     * 運行日別担当班編成に固定スタッフをデタッチなしで追加同期
     *
     * @param  array<int, int>  $staffIds
     */
    public function syncFixedStaffWithoutDetaching(TimetableLane $timetableLane, array $staffIds): void;
}
