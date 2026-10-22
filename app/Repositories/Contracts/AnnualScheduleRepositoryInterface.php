<?php

namespace App\Repositories\Contracts;

use App\Models\AnnualSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AnnualScheduleRepositoryInterface
{
    /**
     * 条件付き年間スケジュール一覧の取得
     */
    public function paginateFiltered(
        int $year,
        ?string $category = null,
        ?string $status = null,
        ?string $search = null,
        ?int $staffId = null,
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * 指定月のスケジュール一覧の取得
     *
     * @return Collection<int, AnnualSchedule>
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
     * 登録済み対象年度一覧の取得
     *
     * @return array<int, int>
     */
    public function getAvailableYears(): array;

    /**
     * 指定年度のステータス別集計の取得
     *
     * @return array{total: int, planned: int, in_progress: int, completed: int}
     */
    public function getYearStatusSummary(int $year): array;

    /**
     * 新規スケジュールの永続化
     *
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): AnnualSchedule;

    /**
     * スケジュールの更新
     *
     * @param  array<string, mixed>  $attributes
     */
    public function update(AnnualSchedule $schedule, array $attributes): bool;

    /**
     * スケジュールの削除
     */
    public function delete(AnnualSchedule $schedule): bool;
}
