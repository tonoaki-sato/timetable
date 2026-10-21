<?php

namespace App\Services;

use App\Models\Meeting;
use App\Models\Staff;
use App\Models\User;
use App\Repositories\Contracts\MeetingRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MeetingService
{
    public function __construct(
        protected MeetingRepositoryInterface $meetingRepository
    ) {}

    /**
     * 一覧画面表示に必要なデータの取得・組み立て
     *
     * @return array{
     *     meetings: LengthAwarePaginator,
     *     currentTab: string,
     *     upcomingCount: int,
     *     pastCount: int,
     *     staffs: Collection<int, Staff>
     * }
     */
    public function getIndexViewData(
        string $tab = 'upcoming',
        ?string $search = null,
        ?int $hostStaffId = null
    ): array {
        $meetings = $this->meetingRepository->paginateFiltered($tab, $search, $hostStaffId);
        $upcomingCount = $this->meetingRepository->getUpcomingCount();
        $pastCount = $this->meetingRepository->getPastCount();
        $staffs = Staff::active()->orderBy('kana')->get();

        return [
            'meetings' => $meetings,
            'currentTab' => $tab,
            'upcomingCount' => $upcomingCount,
            'pastCount' => $pastCount,
            'staffs' => $staffs,
        ];
    }

    /**
     * 新規会議の作成処理（登録者Userの紐付け含む）
     *
     * @param  array<string, mixed>  $data
     */
    public function createMeeting(array $data, ?User $creator): Meeting
    {
        $data['created_by'] = $creator?->id;

        return $this->meetingRepository->create($data);
    }

    /**
     * 会議情報の更新処理
     *
     * @param  array<string, mixed>  $data
     */
    public function updateMeeting(Meeting $meeting, array $data): bool
    {
        return $this->meetingRepository->update($meeting, $data);
    }

    /**
     * 会議の削除処理
     */
    public function deleteMeeting(Meeting $meeting): bool
    {
        return $this->meetingRepository->delete($meeting);
    }
}
