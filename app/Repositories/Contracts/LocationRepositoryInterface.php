<?php

namespace App\Repositories\Contracts;

use App\Models\Location;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Collection;

interface LocationRepositoryInterface
{
    /**
     * IDによる拠点取得
     */
    public function findById(int $id): ?Location;

    /**
     * 名称が部分一致する最初の拠点を取得
     */
    public function findFirstByNameLike(string $keyword): ?Location;

    /**
     * 名称が完全一致する最初の拠点を取得
     */
    public function findFirstByExactName(string $name): ?Location;

    /**
     * 全拠点について「拠点名 => 拠点ID」の連想配列を取得
     *
     * @return array<string, int>
     */
    public function getNameIdMap(): array;

    /**
     * 【互換】指定タイムテーブルで名称が部分一致する最初の拠点を取得
     */
    public function findFirstByTimetableAndNameLike(?int $timetableId, string $keyword): ?Location;

    /**
     * 【互換】指定タイムテーブルで名称が完全一致する最初の拠点を取得
     */
    public function findFirstByTimetableAndExactName(?int $timetableId, string $name): ?Location;

    /**
     * 【互換】全拠点について「拠点名 => 最小拠点ID」の連想配列を取得
     *
     * @return array<string, int>
     */
    public function getNameIdMapForTimetable(?int $timetableId = null): array;

    /**
     * 全拠点一覧を表示順で取得
     *
     * @return Collection<int, Location>
     */
    public function getAllOrdered(): Collection;

    /**
     * 【互換】全拠点一覧をスケジュール付きで取得
     *
     * @return Collection<int, Location>
     */
    public function getForTimetable(?Timetable $timetable = null): Collection;

    /**
     * 有効な拠点一覧を表示順で取得
     *
     * @return Collection<int, Location>
     */
    public function getActiveOrdered(): Collection;

    /**
     * 【互換】指定タイムテーブルの有効な拠点一覧を表示順で取得
     *
     * @return Collection<int, Location>
     */
    public function getActiveOrderedForTimetable(?Timetable $timetable = null): Collection;

    /**
     * 有効な拠点一覧をタイプ・スケジュール付きで表示順に取得
     *
     * @return Collection<int, Location>
     */
    public function getActiveWithTypeAndSchedules(?Timetable $timetable = null): Collection;

    /**
     * 重複を除いた拠点名称・ID一覧を表示順で取得（パターン編集用）
     *
     * @return Collection<int, Location>
     */
    public function getUniqueNamesOrdered(): Collection;

    /**
     * 拠点の総登録数を取得
     */
    public function getTotalCount(): int;

    /**
     * 新規拠点を作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Location;

    /**
     * 拠点を更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Location $location, array $attributes): bool;

    /**
     * 拠点を削除
     */
    public function delete(Location $location): bool;

    /**
     * 拠点表示順を一括更新
     *
     * @param  array<int, int>  $locationIds
     */
    public function reorder(array $locationIds): void;

    /**
     * 最大の表示順を取得
     */
    public function getMaxDisplayOrder(): int;

    /**
     * 拠点の利用可能時間帯スケジュールを一括同期（作成・更新・削除）
     *
     * @param  array<int, array<string, mixed>>  $schedulesData
     */
    public function syncSchedules(Location $location, array $schedulesData): void;
}
