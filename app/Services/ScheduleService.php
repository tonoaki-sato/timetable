<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Staff;
use App\Models\User;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ScheduleService
{
    protected StaffRepositoryInterface $staffRepository;

    public function __construct(
        protected ScheduleRepositoryInterface $scheduleRepository,
        ?StaffRepositoryInterface $staffRepository = null
    ) {
        $this->staffRepository = $staffRepository ?? app(StaffRepositoryInterface::class);
    }

    /**
     * スケジュール一覧画面表示用データの組み立て
     *
     * @return array<string, mixed>
     */
    public function getIndexViewData(
        ?string $category = null,
        ?string $status = null,
        ?string $search = null,
        ?int $staffId = null,
        ?string $tab = null,
        int $perPage = 15
    ): array {
        $schedules = $this->scheduleRepository->paginateFiltered(
            category: $category,
            status: $status,
            search: $search,
            staffId: $staffId,
            tab: $tab,
            perPage: $perPage
        );

        $statusSummary = $this->scheduleRepository->getStatusSummary();
        $staffs = $this->staffRepository->getActiveStaffList();

        return [
            'schedules' => $schedules,
            'statusSummary' => $statusSummary,
            'categories' => Schedule::CATEGORIES,
            'statuses' => Schedule::STATUSES,
            'staffs' => $staffs,
            'currentCategory' => $category,
            'currentStatus' => $status,
            'currentSearch' => $search,
            'currentStaffId' => $staffId,
            'currentTab' => $tab,
        ];
    }

    /**
     * 稼働可能なスタッフ一覧を取得（フォーム選択用）
     *
     * @return Collection<int, Staff>
     */
    public function getActiveStaffList(): Collection
    {
        return $this->staffRepository->getActiveStaffList();
    }

    /**
     * 直近の会議・打ち合わせ一覧を取得
     *
     * @return Collection<int, Schedule>
     */
    public function getUpcomingMeetings(int $limit = 3): Collection
    {
        return $this->scheduleRepository->getUpcomingMeetings($limit);
    }

    /**
     * スケジュールの新規登録
     *
     * @param  array<string, mixed>  $data
     */
    public function createSchedule(array $data, ?User $creator): Schedule
    {
        if ($creator !== null) {
            $data['created_by'] = $creator->id;
        }

        return $this->scheduleRepository->create($data);
    }

    /**
     * スケジュールの更新
     *
     * @param  array<string, mixed>  $data
     */
    public function updateSchedule(Schedule $schedule, array $data): bool
    {
        return $this->scheduleRepository->update($schedule, $data);
    }

    /**
     * スケジュールの削除
     */
    public function deleteSchedule(Schedule $schedule): bool
    {
        return $this->scheduleRepository->delete($schedule);
    }
}
