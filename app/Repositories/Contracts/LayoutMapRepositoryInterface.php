<?php

namespace App\Repositories\Contracts;

use App\Models\LayoutItem;
use App\Models\LayoutMap;
use App\Models\Timetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LayoutMapRepositoryInterface
{
    /**
     * タイムテーブルに紐づく現場配置図面を取得（なければ作成）
     */
    public function getOrCreateForTimetable(Timetable $timetable): LayoutMap;

    /**
     * 図面詳細（エリア、アイテム一覧等）を取得
     */
    public function findWithDetails(int $id): ?LayoutMap;

    /**
     * 図面基本情報およびエリア一覧のみを取得（アイテム全件ロードなし）
     */
    public function findWithAreas(int $id): ?LayoutMap;

    /**
     * 図面内の配置アイテム集計値（区分別・エリア×形態別）を DB 側で集計して取得
     *
     * @return array{
     *   total_booths: int,
     *   total_facilities: int,
     *   total_items: int,
     *   booth_categories: array<string, int>,
     *   facility_categories: array<string, int>,
     *   area_totals: array<int, int>,
     *   area_booth_types: array<int, array<string, int>>
     * }
     */
    public function getSummaryAggregates(int $mapId): array;

    /**
     * 配置アイテム一覧をページネーション取得（絞り込み・検索・ソート対応）
     *
     * @param  array{item_type?: string|null, area_id?: int|string|null, category?: string|null, search?: string|null, sort?: string|null, direction?: string|null}  $filters
     */
    public function paginateItems(LayoutMap $map, array $filters = [], int $perPage = 50): LengthAwarePaginator;

    /**
     * 配置アイテムを新規登録
     *
     * @param  array<string, mixed>  $data
     */
    public function storeItem(LayoutMap $map, array $data): LayoutItem;

    /**
     * 配置アイテムを更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateItem(LayoutItem $item, array $data): LayoutItem;

    /**
     * 配置アイテムを削除
     */
    public function deleteItem(LayoutItem $item): void;
}
