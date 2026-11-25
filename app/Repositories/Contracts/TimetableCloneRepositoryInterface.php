<?php

namespace App\Repositories\Contracts;

use App\Models\ConeMapBar;
use App\Models\ConeMapBarricade;
use App\Models\ConeMapCallout;
use App\Models\ConeMapCone;
use App\Models\EquipmentLogisticsPlan;
use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\TimetableTask;
use Illuminate\Database\Eloquent\Collection;

interface TimetableCloneRepositoryInterface
{
    /**
     * 複製元の担当班編成一覧を固定スタッフ付きで取得
     *
     * @return Collection<int, TimetableLane>
     */
    public function getLanesWithFixedStaff(int $timetableId): Collection;

    /**
     * 複製先の日別担当班編成を作成し、必要に応じて固定スタッフを同期
     *
     * @param  array<string, mixed>  $attributes
     * @param  array<int, int>  $fixedStaffIds
     */
    public function createTimetableLane(array $attributes, array $fixedStaffIds = []): TimetableLane;

    /**
     * 複製元の作業タスク一覧をアサイン付きで取得
     *
     * @return Collection<int, TimetableTask>
     */
    public function getTasksWithAssignments(int $timetableId): Collection;

    /**
     * 複製先の作業タスクを作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createTimetableTask(array $attributes): TimetableTask;

    /**
     * タスクアサイン配列をチャンク単位で一括挿入
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function insertTaskAssignments(array $rows): void;

    /**
     * 複製元の資材搬出入計画一覧を取得
     *
     * @return Collection<int, EquipmentLogisticsPlan>
     */
    public function getLogisticsPlans(int $timetableId): Collection;

    /**
     * 資材搬出入計画配列をチャンク単位で一括挿入
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function insertLogisticsPlans(array $rows): void;

    /**
     * 複製元の現場配置図面一覧をアイテム付きで取得
     *
     * @return Collection<int, LayoutMap>
     */
    public function getLayoutMapsWithItems(int $timetableId): Collection;

    /**
     * 複製先の現場配置図面を作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createLayoutMap(array $attributes): LayoutMap;

    /**
     * 複製先の配置アイテムを作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createLayoutItem(array $attributes): LayoutItem;

    /**
     * タイムテーブルの使用白地図マスターIDを更新
     */
    public function updateTimetableBaseMap(Timetable $timetable, int $siteBaseMapId): void;

    /**
     * 複製元の白地図配置オブジェクト一覧を取得
     *
     * @return Collection<int, SiteMapPlacement>
     */
    public function getSiteMapPlacements(int $timetableId): Collection;

    /**
     * 白地図配置オブジェクト配列をチャンク単位で一括挿入
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function insertSiteMapPlacements(array $rows): void;

    /**
     * 複製元のカラーコーン配置一覧を取得
     *
     * @return Collection<int, ConeMapCone>
     */
    public function getConeMapCones(int $timetableId): Collection;

    /**
     * 複製先のカラーコーン配置を作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createConeMapCone(array $attributes): ConeMapCone;

    /**
     * 複製元のコーンバー配置一覧を取得
     *
     * @return Collection<int, ConeMapBar>
     */
    public function getConeMapBars(int $timetableId): Collection;

    /**
     * コーンバー配置配列をチャンク単位で一括挿入
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function insertConeMapBars(array $rows): void;

    /**
     * 複製元のバリケード配置一覧を取得
     *
     * @return Collection<int, ConeMapBarricade>
     */
    public function getConeMapBarricades(int $timetableId): Collection;

    /**
     * 複製先のバリケード配置を作成
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createConeMapBarricade(array $attributes): ConeMapBarricade;

    /**
     * 複製元の吹き出し一覧を取得
     *
     * @return Collection<int, ConeMapCallout>
     */
    public function getConeMapCallouts(int $timetableId): Collection;

    /**
     * 吹き出し配列をチャンク単位で一括挿入
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    public function insertConeMapCallouts(array $rows): void;
}
