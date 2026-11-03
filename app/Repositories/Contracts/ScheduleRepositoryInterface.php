<?php

namespace App\Repositories\Contracts;

use App\Models\Schedule;
use App\Models\Timetable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface ScheduleRepositoryInterface
{
    /**
     * 条件付きスケジュール一覧の取得
     */
    public function paginateFiltered(
        ?string $category = null,
        ?string $status = null,
        ?string $search = null,
        ?int $staffId = null,
        ?string $tab = null,
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * 指定月のスケジュール一覧の取得（カレンダー用）
     *
     * @return Collection<int, Schedule>
     */
    public function getForMonth(
        int $year,
        int $month,
        ?string $category = null,
        ?string $status = null,
        ?int $staffId = null,
        ?string $search = null
    ): Collection;

    /**
     * 直近の会議・打ち合わせ一覧を取得
     *
     * @return Collection<int, Schedule>
     */
    public function getUpcomingMeetings(int $limit = 3): Collection;

    /**
     * 全体のステータス別集計の取得
     *
     * @return array{total: int, planned: int, in_progress: int, completed: int}
     */
    public function getStatusSummary(): array;

    /**
     * 新規スケジュールの永続化
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Schedule;

    /**
     * スケジュールの更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(Schedule $schedule, array $attributes): bool;

    /**
     * スケジュールの削除
     */
    public function delete(Schedule $schedule): bool;

    /**
     * 運行スケジュール（タイムテーブル）に対応するスケジュールを同期
     */
    public function syncForTimetable(Timetable $timetable): Schedule;

    /**
     * 運行スケジュール（タイムテーブル）に紐づくスケジュールを削除
     */
    public function deleteForTimetable(int $timetableId): bool;
}
