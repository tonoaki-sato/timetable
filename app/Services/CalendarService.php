<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\Staff;
use App\Models\Timetable;
use App\Repositories\Contracts\ScheduleRepositoryInterface;
use Illuminate\Support\Carbon;

class CalendarService
{
    public function __construct(
        protected ScheduleRepositoryInterface $scheduleRepository
    ) {}

    /**
     * 統合月間カレンダー表示に必要なデータ一式の取得・組み立て
     *
     * @return array<string, mixed>
     */
    public function getMonthCalendarViewData(
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

        // 1. 指定月のスケジュール取得（会議・協議・説明会・設営等すべて含む）
        $schedules = $this->scheduleRepository->getForMonth($year, $month, $category, $status, $staffId, $search);

        // 2. 指定月の本番運行日（タイムテーブル）取得
        $timetableQuery = Timetable::whereYear('target_date', $year)
            ->whereMonth('target_date', $month);
        if ($search) {
            $timetableQuery->where('name', 'like', "%{$search}%");
        }
        $timetables = $timetableQuery->orderBy('target_date')->orderBy('start_time')->get();

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

            // A. 本番運行日（タイムテーブル）を最優先マッピング
            foreach ($timetables as $timetable) {
                if ($timetable->target_date?->toDateString() === $dateStr) {
                    $dayEvents[] = [
                        'id' => $timetable->id,
                        'source' => 'timetable',
                        'title' => $timetable->name,
                        'category' => 'operation',
                        'category_label' => 'まつり本番運行日',
                        'icon' => '🏮',
                        'badge_class' => 'bg-rose-100 text-rose-800 border-rose-300 hover:bg-rose-200 font-bold',
                        'time' => $timetable->start_time ? substr($timetable->start_time, 0, 5).' 〜 '.substr($timetable->end_time ?? '', 0, 5) : null,
                        'start_time' => substr($timetable->start_time ?? '', 0, 5),
                        'end_time' => $timetable->end_time ? substr($timetable->end_time, 0, 5) : null,
                        'location' => '会場一帯',
                        'staff_id' => null,
                        'staff_name' => null,
                        'has_unassigned_host' => false,
                        'is_period' => false,
                        'date' => $dateStr,
                        'start_date' => $dateStr,
                        'end_date' => null,
                        'status' => $timetable->status,
                        'status_label' => $timetable->status_label,
                        'description' => $timetable->description,
                        'share_text' => null,
                        'show_url' => route('timetables.show', $timetable),
                        'edit_url' => route('timetables.edit', $timetable),
                    ];
                }
            }

            // B. スケジュールイベント（会議含む、タイムテーブル連動はAで優先表示済みのためスキップ）をマッピング
            foreach ($schedules as $schedule) {
                if ($schedule->timetable_id !== null) {
                    continue;
                }

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
                        'source' => 'schedule',
                        'title' => $schedule->title,
                        'category' => $schedule->category,
                        'category_label' => $meta['label'],
                        'icon' => $meta['icon'],
                        'badge_class' => $meta['class'],
                        'time' => $schedule->formatted_time_range,
                        'start_time' => $schedule->start_time ? substr($schedule->start_time, 0, 5) : null,
                        'end_time' => $schedule->end_time ? substr($schedule->end_time, 0, 5) : null,
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
                        'share_text' => $schedule->share_text,
                        'show_url' => route('schedules.show', $schedule),
                        'edit_url' => route('schedules.edit', $schedule),
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
            'availableYears' => range($year - 2, $year + 2),
            'categories' => Schedule::CATEGORIES,
            'statuses' => Schedule::STATUSES,
            'staffs' => Staff::active()->orderBy('kana')->get(),
        ];
    }
}
