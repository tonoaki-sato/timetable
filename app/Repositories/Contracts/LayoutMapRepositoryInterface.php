<?php

namespace App\Repositories\Contracts;

use App\Models\LayoutBooth;
use App\Models\LayoutFacility;
use App\Models\LayoutMap;
use App\Models\Timetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface LayoutMapRepositoryInterface
{
    /**
     * タイムテーブルに紐づく現場配置図面を取得（なければ作成）
     */
    public function getOrCreateForTimetable(Timetable $timetable): LayoutMap;

    /**
     * 図面詳細（エリア、ブース、設備等）を取得
     */
    public function findWithDetails(int $id): ?LayoutMap;

    /**
     * コマ割り一覧をページネーション取得（絞り込み・検索対応）
     *
     * @param  array{area_id?: int|string|null, category?: string|null, search?: string|null}  $filters
     */
    public function paginateBooths(LayoutMap $map, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /**
     * コマ割り情報を更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateBooth(LayoutBooth $booth, array $data): LayoutBooth;

    /**
     * 現場設備一覧を取得
     *
     * @param  array{type?: string|null}  $filters
     */
    public function getFacilities(LayoutMap $map, array $filters = []): Collection;

    /**
     * 現場設備情報を更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateFacility(LayoutFacility $facility, array $data): LayoutFacility;
}
