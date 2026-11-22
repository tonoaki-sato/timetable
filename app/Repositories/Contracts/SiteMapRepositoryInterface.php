<?php

namespace App\Repositories\Contracts;

use App\Models\LayoutItem;
use App\Models\SiteBaseMap;
use App\Models\SiteMapIconDefinition;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Collection;

interface SiteMapRepositoryInterface
{
    /**
     * 運行情報数・配置数カウント付きで全白地図マスターを取得
     *
     * @return Collection<int, SiteBaseMap>
     */
    public function getAllBaseMapsWithCounts(): Collection;

    /**
     * 有効な白地図マスター一覧を取得（デフォルト優先）
     *
     * @return Collection<int, SiteBaseMap>
     */
    public function getActiveBaseMaps(): Collection;

    /**
     * IDで白地図マスターを取得
     */
    public function findBaseMapById(int $id): ?SiteBaseMap;

    /**
     * 有効なデフォルト白地図マスターを取得（存在しない場合は標準白地図を作成）
     */
    public function getOrCreateDefaultBaseMap(): SiteBaseMap;

    /**
     * 運行情報で使用する白地図マスターを解決（未設定時はデフォルト白地図を取得・作成）
     */
    public function resolveBaseMapForTimetable(Timetable $timetable): SiteBaseMap;

    /**
     * 運行情報で使用する白地図マスターを取得（存在しない場合は例外）
     */
    public function findBaseMapForTimetableOrFail(Timetable $timetable): SiteBaseMap;

    /**
     * 白地図マスターを作成
     *
     * @param  array<string, mixed>  $data
     */
    public function createBaseMap(array $data): SiteBaseMap;

    /**
     * 白地図マスターを更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBaseMap(SiteBaseMap $siteBaseMap, array $data): SiteBaseMap;

    /**
     * 白地図マスターを削除
     */
    public function deleteBaseMap(SiteBaseMap $siteBaseMap): bool;

    /**
     * 指定ID以外の白地図マスターのデフォルトフラグを解除
     */
    public function clearDefaultFlagExcept(?int $exceptId = null): void;

    /**
     * 指定白地図を使用している運行情報件数を取得
     */
    public function countTimetablesUsingBaseMap(SiteBaseMap $siteBaseMap): int;

    /**
     * 指定白地図に紐づく配置オブジェクト件数を取得
     */
    public function countPlacementsUsingBaseMap(SiteBaseMap $siteBaseMap): int;

    /**
     * CADシンボル定義一覧を取得
     *
     * @return Collection<int, SiteMapIconDefinition>
     */
    public function getAllIconDefinitions(): Collection;

    /**
     * 指定キーのCADシンボル定義を取得
     */
    public function findIconDefinitionByKey(string $iconKey): ?SiteMapIconDefinition;

    /**
     * 運行情報・白地図に紐づく全配置オブジェクトを取得
     *
     * @return Collection<int, SiteMapPlacement>
     */
    public function getPlacements(Timetable $timetable, SiteBaseMap $siteBaseMap): Collection;

    /**
     * 指定ID群に一致する白地図配置オブジェクトを一括取得
     *
     * @param  array<int, int>  $ids
     * @return Collection<int, SiteMapPlacement>
     */
    public function findPlacementsByIds(Timetable $timetable, SiteBaseMap $siteBaseMap, array $ids): Collection;

    /**
     * 配置オブジェクトを保存
     */
    public function savePlacement(SiteMapPlacement $placement, bool $withRelations = true): SiteMapPlacement;

    /**
     * 配置オブジェクトを削除
     */
    public function deletePlacement(SiteMapPlacement $placement): bool;

    /**
     * 運行情報・白地図上の全配置オブジェクトを一括削除
     */
    public function clearPlacements(Timetable $timetable, SiteBaseMap $siteBaseMap): bool;

    /**
     * 運行情報に紐づく既存ブース・設備台帳一覧を取得
     *
     * @return Collection<int, LayoutItem>
     */
    public function getLayoutItemsForTimetable(Timetable $timetable): Collection;

    /**
     * 新しいシンボル定義（カスタムアイコン等）を作成
     *
     * @param  array<string, mixed>  $data
     */
    public function createIconDefinition(array $data): SiteMapIconDefinition;

    /**
     * シンボル定義を更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateIconDefinition(SiteMapIconDefinition $iconDefinition, array $data): SiteMapIconDefinition;

    /**
     * シンボル定義を削除
     */
    public function deleteIconDefinition(SiteMapIconDefinition $iconDefinition): bool;

    /**
     * 指定のシンボル種別を使用している配置件数をカウント
     */
    public function countPlacementsUsingIcon(string $iconKey): int;

    /**
     * 指定のシンボル種別を使用している配置を一括削除
     */
    public function deletePlacementsByShapeType(string $shapeType): int;

    /**
     * デフォルト白地図が既に存在するか判定
     */
    public function hasDefaultBaseMap(): bool;

    /**
     * 指定ID以外の有効な代替白地図を1件取得
     */
    public function findFallbackActiveBaseMap(int $excludeId): ?SiteBaseMap;

    /**
     * 指定された相対ファイルパスが白地図マスターで使用中か判定
     */
    public function isAssetPathUsed(string $relativePath): bool;
}
