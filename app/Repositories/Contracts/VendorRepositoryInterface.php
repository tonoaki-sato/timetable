<?php

namespace App\Repositories\Contracts;

use App\Models\Timetable;
use App\Models\Vendor;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface VendorRepositoryInterface
{
    /**
     * タイムテーブルに紐づく出店者一覧を取得
     */
    public function getForTimetable(Timetable $timetable): Collection;

    /**
     * 出店者一覧をページネーション取得（検索・絞り込み）
     *
     * @param  array{category?: string|null, search?: string|null}  $filters
     */
    public function paginateForTimetable(Timetable $timetable, array $filters = [], int $perPage = 25): LengthAwarePaginator;

    /**
     * 出店者を登録
     *
     * @param  array<string, mixed>  $data
     */
    public function create(Timetable $timetable, array $data): Vendor;

    /**
     * 出店者を更新
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Vendor $vendor, array $data): Vendor;

    /**
     * 出店者を削除
     */
    public function delete(Vendor $vendor): bool;
}
