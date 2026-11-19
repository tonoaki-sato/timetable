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
