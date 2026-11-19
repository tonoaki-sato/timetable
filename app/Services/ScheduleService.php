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
        $validKeys = array_merge(['timetable'], array_keys(Schedule::CATEGORIES));

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
        foreach (Schedule::CATEGORIES as $key => $label) {
            $displayCategories[$key] = [
                'label' => $label,
                'icon' => Schedule::categoryFilterIcon($key),
            ];
        }

        return [
            'schedules' => $schedules,
            'statusSummary' => $statusSummary,
            'categories' => Schedule::CATEGORIES,
            'displayCategories' => $displayCategories,
            'statuses' => Schedule::STATUSES,
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
                } elseif (isset(Schedule::CATEGORIES[$catKey])) {
                    $catLabels[] = Schedule::CATEGORIES[$catKey];
                }
            }
            if ($catLabels !== []) {
                $subtitleParts[] = '区分: '.implode('、', $catLabels);
            }
        }

        if ($status && isset(Schedule::STATUSES[$status])) {
            $subtitleParts[] = '状態: '.Schedule::STATUSES[$status];
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
