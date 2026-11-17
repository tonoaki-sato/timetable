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
     * 運行情報に紐づく既存ブース・設備台帳一覧を取得
     *
     * @return Collection<int, LayoutItem>
     */
    public function getLayoutItemsForTimetable(Timetable $timetable): Collection;
}
