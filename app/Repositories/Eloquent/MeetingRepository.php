<?php

namespace App\Repositories\Eloquent;

use App\Models\Meeting;
use App\Repositories\Contracts\MeetingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class MeetingRepository implements MeetingRepositoryInterface
{
    /**
     * 条件付き会議一覧の取得
     */
    public function paginateFiltered(
        string $tab = 'upcoming',
        ?string $search = null,
        ?int $hostStaffId = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = Meeting::with(['host', 'creator']);

        if ($tab === 'past') {
            $query->past();
        } else {
            $query->upcoming();
        }

        if ($hostStaffId) {
            $query->host($hostStaffId);
        }

        if ($search) {
            $query->search($search);
        }

        return $query->paginate($perPage)->withQueryString();
    }

    /**
     * 開催予定の会議件数
     */
    public function getUpcomingCount(): int
    {
        return Meeting::where('meeting_date', '>=', now()->toDateString())->count();
    }

    /**
     * 過去の会議件数
     */
    public function getPastCount(): int
    {
        return Meeting::where('meeting_date', '<', now()->toDateString())->count();
    }

    /**
     * 新規会議の永続化
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Meeting
    {
        return Meeting::create($attributes);
    }

    /**
     * 会議情報の更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Meeting $meeting, array $attributes): bool
    {
        return $meeting->update($attributes);
    }

    /**
     * 会議の削除
     */
    public function delete(Meeting $meeting): bool
    {
        return (bool) $meeting->delete();
    }
}
