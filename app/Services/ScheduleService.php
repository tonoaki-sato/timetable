<?php

namespace App\Services;

use App\DTOs\ScheduleFilterDTO;
use App\Enums\ScheduleCategory;
use App\Enums\ScheduleStatus;
use App\Models\Schedule;
use App\Models\Staff;
use App\Models\User;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use App\Repositories\Contracts\StaffRepositoryInterface;
use DomainException;
use Illuminate\Database\Eloquent\Collection;

class ScheduleService
{
    public function __construct(
        protected ScheduleRepositoryInterface $scheduleRepository,
        protected StaffRepositoryInterface $staffRepository,
    ) {}

    /**
     * リクエスト入力の category を有効な区分キー配列へ正規化（未指定またはすべて選択時は null）
     *
     * @return array<int, string>|null
     */
    public function parseCategories(mixed $input): ?array
    {
        if ($input === null || $input === '' || $input === []) {
            return null;
        }

        $raw = is_array($input) ? $input : explode(',', (string) $input);
        $validKeys = array_merge(['timetable'], ScheduleCategory::values());

        $filtered = array_values(array_unique(array_filter(
            array_map('trim', $raw),
            fn (string $key): bool => in_array($key, $validKeys, true)
        )));

        return empty($filtered) ? null : $filtered;
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
        int $perPage = 15,
        ?string $sort = null,
        ?string $direction = null,
    ): array {
        $selectedCategories = $this->parseCategories($category);

        $schedules = ($sort === null && $direction === null)
            ? $this->scheduleRepository->paginateFiltered(
                category: null,
                status: $status,
                search: $search,
                staffId: $staffId,
                tab: $tab,
                perPage: $perPage,
            )
            : $this->scheduleRepository->paginateFiltered(
                category: null,
                status: $status,
                search: $search,
                staffId: $staffId,
                tab: $tab,
                perPage: $perPage,
                sort: $sort,
                direction: $direction,
            );

        $statusSummary = $this->scheduleRepository->getStatusSummary();
        $staffs = $this->staffRepository->getActiveStaffList();

        $displayCategories = [
            'timetable' => [
                'label' => '運行スケジュール',
                'icon' => '🏮',
            ],
        ];
        foreach (ScheduleCategory::cases() as $categoryEnum) {
            $displayCategories[$categoryEnum->value] = [
                'label' => $categoryEnum->label(),
                'icon' => $categoryEnum->icon(),
            ];
        }

        return [
            'schedules' => $schedules,
            'statusSummary' => $statusSummary,
            'categories' => ScheduleCategory::options(),
            'displayCategories' => $displayCategories,
            'statuses' => ScheduleStatus::options(),
            'staffs' => $staffs,
            'selectedCategories' => $selectedCategories,
            'currentCategory' => $category,
            'currentStatus' => $status,
            'currentSearch' => $search,
            'currentStaffId' => $staffId,
            'currentTab' => $tab,
        ];
    }

    /**
     * DTO を用いてスケジュール一覧画面表示用データを組み立て
     *
     * @return array<string, mixed>
     */
    public function getIndexViewDataByFilter(ScheduleFilterDTO $filter): array
    {
        return $this->getIndexViewData(
            category: $filter->category,
            status: $filter->status,
            search: $filter->search,
            staffId: $filter->staffId,
            tab: $filter->tab,
            perPage: $filter->perPage,
            sort: $filter->sort,
            direction: $filter->direction,
        );
    }

    /**
     * 詳細画面表示用に関連リレーションをロード
     */
    public function getScheduleDetails(Schedule $schedule): Schedule
    {
        return $schedule->load(['creator', 'assignee', 'timetable']);
    }

    /**
     * JSON応答用に担当スタッフをロード
     */
    public function loadAssignee(Schedule $schedule): Schedule
    {
        return $schedule->load('assignee');
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
     *
     * @throws DomainException
     */
    public function deleteSchedule(Schedule $schedule): bool
    {
        if ($schedule->is_linked_to_timetable) {
            throw new DomainException(
                "予定「{$schedule->title}」は運行スケジュール（タイムテーブル）と自動連動しているため、スケジュール管理画面からは削除できません。運行情報管理画面から削除してください。"
            );
        }

        return $this->scheduleRepository->delete($schedule);
    }

    /**
     * 年間スケジュール一覧PDF用のビューデータを取得
     *
     * @return array<string, mixed>
     */
    public function getPdfViewData(
        mixed $category = null,
        ?string $status = null,
        ?string $search = null,
        ?int $staffId = null,
        ?string $tab = 'upcoming',
    ): array {
        $selectedCategories = $this->parseCategories($category);

        $schedules = $this->scheduleRepository->getFilteredForPdf(
            categories: $selectedCategories,
            status: $status,
            search: $search,
            staffId: $staffId,
            tab: $tab
        );

        $tabLabel = match ($tab) {
            'past' => '過去の記録',
            'all' => 'すべての期間',
            default => '今後の予定',
        };

        $subtitleParts = ["対象期間: {$tabLabel}"];

        if ($selectedCategories !== null) {
            $catLabels = [];
            foreach ($selectedCategories as $catKey) {
                if ($catKey === 'timetable') {
                    $catLabels[] = '運行スケジュール';
                } elseif (($categoryEnum = ScheduleCategory::tryFrom($catKey)) !== null) {
                    $catLabels[] = $categoryEnum->label();
                }
            }
            if ($catLabels !== []) {
                $subtitleParts[] = '区分: '.implode('、', $catLabels);
            }
        }

        if ($status && ($statusEnum = ScheduleStatus::tryFrom($status)) !== null) {
            $subtitleParts[] = '状態: '.$statusEnum->label();
        }

        if ($search) {
            $subtitleParts[] = "検索: 「{$search}」";
        }

        return [
            'title' => '保土ケ谷宿場まつり実行委員会 年間スケジュール一覧',
            'subtitle' => implode(' | ', $subtitleParts),
            'schedules' => $schedules,
            'generatedAt' => now()->format('Y年n月j日 H:i'),
        ];
    }

    /**
     * DTO を用いて .ics（iCalendar）出力用の条件付きスケジュール一覧を取得
     *
     * @return Collection<int, Schedule>
     */
    public function getFilteredForIcsByFilter(ScheduleFilterDTO $filter): Collection
    {
        return $this->getFilteredForIcs(
            category: $filter->category,
            status: $filter->status,
            search: $filter->search,
            staffId: $filter->staffId,
            tab: $filter->tab,
        );
    }

    /**
     * .ics（iCalendar）出力用の条件付きスケジュール一覧を取得
     *
     * @return Collection<int, Schedule>
     */
    public function getFilteredForIcs(
        mixed $category = null,
        ?string $status = null,
        ?string $search = null,
        ?int $staffId = null,
        ?string $tab = 'upcoming',
    ): Collection {
        $selectedCategories = $this->parseCategories($category);

        return $this->scheduleRepository->getFilteredForPdf(
            categories: $selectedCategories,
            status: $status,
            search: $search,
            staffId: $staffId,
            tab: $tab
        );
    }
}
