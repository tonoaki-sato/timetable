<?php

namespace App\Services;

use App\Models\AnnualSchedule;
use App\Models\User;
use App\Repositories\Contracts\AnnualScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AnnualScheduleService
{
    public function __construct(
        protected AnnualScheduleRepositoryInterface $scheduleRepository
    ) {}

    /**
     * 一覧画面表示に必要なデータの取得・組み立て
     *
     * @return array{
     *     schedules: LengthAwarePaginator,
     *     selectedYear: int,
     *     availableYears: array<int, int>,
     *     yearSummary: array{total: int, planned: int, in_progress: int, completed: int},
     *     categories: array<string, string>,
     *     statuses: array<string, string>
     * }
     */
    public function getIndexViewData(
        int $year,
        ?string $category = null,
        ?string $status = null,
        ?string $search = null
    ): array {
        $availableYears = $this->scheduleRepository->getAvailableYears();
        $schedules = $this->scheduleRepository->paginateFiltered($year, $category, $status, $search);
        $yearSummary = $this->scheduleRepository->getYearStatusSummary($year);

        return [
            'schedules' => $schedules,
            'selectedYear' => $year,
            'availableYears' => $availableYears,
            'yearSummary' => $yearSummary,
            'categories' => AnnualSchedule::CATEGORIES,
            'statuses' => AnnualSchedule::STATUSES,
        ];
    }

    /**
     * 新規スケジュールの作成処理（作成者紐付け含む）
     *
     * @param  array<string, mixed>  $data
     */
    public function createSchedule(array $data, ?User $creator): AnnualSchedule
    {
        $data['created_by'] = $creator?->id;

        return $this->scheduleRepository->create($data);
    }

    /**
     * スケジュールの更新処理
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSchedule(AnnualSchedule $schedule, array $data): bool
    {
        return $this->scheduleRepository->update($schedule, $data);
    }

    /**
     * スケジュールの削除処理
     */
    public function deleteSchedule(AnnualSchedule $schedule): bool
    {
        return $this->scheduleRepository->delete($schedule);
    }
}
