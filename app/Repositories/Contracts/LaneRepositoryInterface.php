<?php

namespace App\Repositories\Contracts;

use App\Models\Lane;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Collection;

interface LaneRepositoryInterface
{
    /**
     * 全担当班マスターを利用数・参照運行計画一覧付きで表示順に取得
     *
     * @return Collection<int, Lane>
     */
    public function getAllWithCountsAndTimetables(): Collection;

    /**
     * 有効な担当班マスター一覧を表示順に取得
     *
     * @return Collection<int, Lane>
     */
    public function getActiveOrdered(): Collection;

    /**
     * 指定タイムテーブルで未追加の有効な担当班マスター一覧を表示順に取得
     *
     * @return Collection<int, Lane>
     */
    public function getAvailableForTimetable(Timetable $timetable): Collection;

    /**
     * IDによる担当班マスター取得
     */
    public function findById(int $id): ?Lane;

    /**
     * 名称（または「班」を除いた名称）から担当班マスターを取得
     */
    public function findByNameOrCleanName(string $name, string $cleanName): ?Lane;

    /**
     * 最大表示順を取得
     */
    public function getMaxDisplayOrder(): int;

    /**
     * 担当班マスターを新規作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Lane;

    /**
     * 担当班マスターを更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Lane $lane, array $attributes): Lane;

    /**
     * 担当班マスターを削除
     */
    public function delete(Lane $lane): bool;

    /**
     * 担当班マスターを参照している運行日別編成件数を取得
     */
    public function countTimetableLanes(Lane $lane): int;

    /**
     * 担当班マスターの表示順を一括更新
     *
     * @param  array<int, int>  $laneIds
     */
    public function reorder(array $laneIds): void;
}
