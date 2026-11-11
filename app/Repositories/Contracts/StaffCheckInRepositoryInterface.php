<?php

namespace App\Repositories\Contracts;

use App\Models\StaffCheckIn;
use Carbon\CarbonInterface;

interface StaffCheckInRepositoryInterface
{
    /**
     * 運行計画ID・スタッフIDで受付記録を取得
     */
    public function findByTimetableAndStaff(int $timetableId, int $staffId): ?StaffCheckIn;

    /**
     * 運行計画ID・スタッフIDで初回受付記録を冪等に登録（既存時は初回到着日時を保持）
     */
    public function firstOrCreateForTimetableAndStaff(
        int $timetableId,
        int $staffId,
        CarbonInterface $checkedInAt
    ): StaffCheckIn;

    /**
     * 指定運行計画で受付済みのスタッフID配列を取得
     *
     * @return array<int, int>
     */
    public function getStaffIdsCheckedInForTimetable(int $timetableId): array;

    /**
     * 指定運行計画におけるスタッフID別の受付記録マップを取得
     *
     * @param  array<int, int>  $staffIds
     * @return array<int, StaffCheckIn>
     */
    public function getMapByTimetable(int $timetableId, array $staffIds = []): array;
}
