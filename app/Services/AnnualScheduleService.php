<?php

namespace App\Services;

use App\Models\AnnualSchedule;
use App\Models\Meeting;
use App\Models\Staff;
use App\Models\User;
use App\Repositories\Contracts\AnnualScheduleRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

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
     *     statuses: array<string, string>,
     *     staffs: Collection<int, Staff>
     * }
     */
    public function getIndexViewData(
        int $year,
        ?string $category = null,
        ?string $status = null,
        ?string $search = null,
        ?int $staffId = null
    ): array {
        $availableYears = $this->scheduleRepository->getAvailableYears();
        $schedules = $this->scheduleRepository->paginateFiltered($year, $category, $status, $search, $staffId);
        $yearSummary = $this->scheduleRepository->getYearStatusSummary($year);
        $staffs = Staff::active()->orderBy('kana')->get();

        return [
            'schedules' => $schedules,
            'selectedYear' => $year,
            'availableYears' => $availableYears,
            'yearSummary' => $yearSummary,
            'categories' => AnnualSchedule::CATEGORIES,
            'statuses' => AnnualSchedule::STATUSES,
            'staffs' => $staffs,
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

    /**
     * カレンダー表示に必要なデータ一式の取得・組み立て
     *
     * @return array<string, mixed>
     */
    public function getCalendarViewData(
        int $year,
        int $month,
        ?string $category = null,
        ?string $status = null,
        ?string $search = null,
        ?int $staffId = null
    ): array {
        $firstDay = Carbon::createFromDate($year, $month, 1);
        $daysInMonth = $firstDay->daysInMonth;
        $startDayOfWeek = $firstDay->dayOfWeek; // 0=Sun, 1=Mon, ..., 6=Sat

        // グリッド開始日（日曜始まり）
        $gridStart = $firstDay->copy()->subDays($startDayOfWeek);
        $totalCells = (int) (ceil(($startDayOfWeek + $daysInMonth) / 7) * 7);

        // 指定月の年間スケジュール取得
        $schedules = $this->scheduleRepository->getForMonth($year, $month, $category, $status, $staffId, $search);

        // 指定月の会議スケジュール取得
        $meetingQuery = Meeting::with(['host', 'creator'])
            ->whereYear('meeting_date', $year)
            ->whereMonth('meeting_date', $month);

        if ($search) {
            $meetingQuery->search($search);
        }
        if ($staffId) {
            $meetingQuery->host($staffId);
        }
        $meetings = $meetingQuery->orderBy('meeting_date')->orderBy('start_time')->get();

        // 日付ごとのイベント辞書を組み立て
        $weeks = [];
        $currentCellDate = $gridStart->copy();
        $todayStr = now()->toDateString();

        for ($i = 0; $i < $totalCells; $i++) {
            $dateStr = $currentCellDate->toDateString();
            $dayOfWeek = $currentCellDate->dayOfWeek;
            $isCurrentMonth = (int) $currentCellDate->month === $month;
            $isToday = $dateStr === $todayStr;

            $dayEvents = [];

            // 1. 会議イベントをマッピング
            foreach ($meetings as $meeting) {
                if ($meeting->meeting_date?->toDateString() === $dateStr) {
                    $dayEvents[] = [
                        'id' => $meeting->id,
                        'source' => 'meeting',
                        'title' => $meeting->title,
                        'category' => 'committee',
                        'category_label' => '会議・打合せ',
                        'icon' => '🤝',
                        'badge_class' => 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100',
                        'time' => $meeting->formatted_time_range,
                        'start_time' => substr($meeting->start_time ?? '', 0, 5),
                        'end_time' => $meeting->end_time ? substr($meeting->end_time, 0, 5) : null,
                        'location' => $meeting->location,
                        'staff_id' => $meeting->staff_id,
                        'staff_name' => $meeting->host?->name,
                        'has_unassigned_host' => $meeting->staff_id === null,
                        'is_period' => false,
                        'date' => $dateStr,
                        'start_date' => $dateStr,
                        'end_date' => null,
                        'status' => $meeting->is_upcoming ? 'planned' : 'completed',
                        'status_label' => $meeting->is_upcoming ? '予定' : '終了',
                        'description' => $meeting->description,
                        'notice_note' => $meeting->notice_note,
                        'line_share_text' => $meeting->line_share_text,
                        'show_url' => route('meetings.show', $meeting),
                        'edit_url' => route('meetings.edit', $meeting),
                    ];
                }
            }

            // 2. 年間スケジュールイベントをマッピング
            foreach ($schedules as $schedule) {
                $startStr = $schedule->start_date?->toDateString();
                $endStr = $schedule->end_date?->toDateString() ?? $startStr;

                if ($startStr && $dateStr >= $startStr && $dateStr <= $endStr) {
                    $isPeriod = $schedule->end_date && $startStr !== $endStr;
                    $isPeriodStart = $isPeriod && $startStr === $dateStr;

                    $meta = match ($schedule->category) {
                        'committee' => ['icon' => '🤝', 'label' => '会議・打合せ', 'class' => 'bg-indigo-50 text-indigo-700 border-indigo-200 hover:bg-indigo-100'],
                        'permit' => ['icon' => '📑', 'label' => '申請・協議', 'class' => 'bg-amber-50 text-amber-700 border-amber-200 hover:bg-amber-100'],
                        'briefing' => ['icon' => '📢', 'label' => '募集・説明会', 'class' => 'bg-sky-50 text-sky-700 border-sky-200 hover:bg-sky-100'],
                        'operation' => ['icon' => '🎪', 'label' => '運行・設営', 'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200 hover:bg-emerald-100'],
                        default => ['icon' => '📌', 'label' => 'その他', 'class' => 'bg-slate-100 text-slate-700 border-slate-200 hover:bg-slate-200'],
                    };

                    $dayEvents[] = [
                        'id' => $schedule->id,
                        'source' => 'annual_schedule',
                        'title' => $schedule->title,
                        'category' => $schedule->category,
                        'category_label' => $meta['label'],
                        'icon' => $meta['icon'],
                        'badge_class' => $meta['class'],
                        'time' => null,
                        'location' => $schedule->location,
                        'staff_id' => $schedule->staff_id,
                        'staff_name' => $schedule->assignee?->name,
                        'has_unassigned_host' => $schedule->staff_id === null,
                        'is_period' => $isPeriod,
                        'is_period_start' => $isPeriodStart,
                        'date' => $dateStr,
                        'start_date' => $startStr,
                        'end_date' => $endStr,
                        'status' => $schedule->status,
                        'status_label' => $schedule->status_label,
                        'description' => $schedule->description,
                        'notice_note' => null,
                        'line_share_text' => null,
                        'show_url' => route('annual-schedules.show', $schedule),
                        'edit_url' => route('annual-schedules.edit', $schedule),
                    ];
                }
            }

            $weekIndex = (int) floor($i / 7);
            $weeks[$weekIndex][] = [
                'date' => $dateStr,
                'day' => (int) $currentCellDate->day,
                'isCurrentMonth' => $isCurrentMonth,
                'isToday' => $isToday,
                'dayOfWeek' => $dayOfWeek,
                'events' => $dayEvents,
            ];

            $currentCellDate->addDay();
        }

        $prevCarbon = $firstDay->copy()->subMonth();
        $nextCarbon = $firstDay->copy()->addMonth();

        $categoryOptions = [
            'committee' => [
                'key' => 'committee',
                'name' => '会議・打合せ',
                'icon' => '🤝',
            ],
            'permit' => [
                'key' => 'permit',
                'name' => '申請・協議',
                'icon' => '📑',
            ],
            'briefing' => [
                'key' => 'briefing',
                'name' => '募集・説明会',
                'icon' => '📢',
            ],
            'operation' => [
                'key' => 'operation',
                'name' => '運行・設営',
                'icon' => '🎪',
            ],
            'other' => [
                'key' => 'other',
                'name' => 'その他',
                'icon' => '📌',
            ],
        ];

        return [
            'calendarWeeks' => $weeks,
            'currentYear' => $year,
            'currentMonth' => $month,
            'prevYear' => $prevCarbon->year,
            'prevMonth' => $prevCarbon->month,
            'nextYear' => $nextCarbon->year,
            'nextMonth' => $nextCarbon->month,
            'categoryOptions' => $categoryOptions,
            'availableYears' => $this->scheduleRepository->getAvailableYears(),
            'yearSummary' => $this->scheduleRepository->getYearStatusSummary($year),
            'categories' => AnnualSchedule::CATEGORIES,
            'statuses' => AnnualSchedule::STATUSES,
            'staffs' => Staff::active()->orderBy('kana')->get(),
        ];
    }
}
