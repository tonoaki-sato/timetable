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
     * 運行情報に紐づくアクティブな白地図を取得（なければ初期作成）
     */
    public function getOrCreateActiveBaseMap(Timetable $timetable): SiteBaseMap;

    /**
     * 運行情報に紐づくアクティブな白地図を取得（存在しない場合は例外）
     */
    public function findActiveBaseMapOrFail(Timetable $timetable): SiteBaseMap;

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
     * 白地図に配置された全オブジェクトを取得
     *
     * @return Collection<int, SiteMapPlacement>
     */
    public function getPlacementsByBaseMap(SiteBaseMap $siteBaseMap): Collection;

    /**
     * 配置オブジェクトを保存
     */
    public function savePlacement(SiteMapPlacement $placement): SiteMapPlacement;

    /**
     * 配置オブジェクトを削除
     */
    public function deletePlacement(SiteMapPlacement $placement): bool;

    /**
     * 白地図上の全配置オブジェクトを一括削除
     */
    public function clearPlacementsByBaseMap(SiteBaseMap $siteBaseMap): bool;

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
}
