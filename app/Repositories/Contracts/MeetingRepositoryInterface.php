<?php

namespace App\Repositories\Contracts;

use App\Models\Meeting;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface MeetingRepositoryInterface
{
    /**
     * 条件付き会議一覧の取得（開催予定または過去）
     */
    public function paginateFiltered(
        string $tab = 'upcoming',
        ?string $search = null,
        ?int $staffId = null,
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * 開催予定の会議件数
     */
    public function getUpcomingCount(): int;

    /**
     * 過去の会議件数
     */
    public function getPastCount(): int;

    /**
     * 新規会議の永続化
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Meeting;

    /**
     * 会議情報の更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Meeting $meeting, array $attributes): bool;

    /**
     * 会議の削除
     */
    public function delete(Meeting $meeting): bool;
}
