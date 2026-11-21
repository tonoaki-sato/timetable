<?php

namespace App\DTOs;

use App\Enums\LocationDailyAvailabilityStatus;
use App\Models\Location;
use App\Models\LocationSchedule;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class LocationListItemData
{
    /**
     * @param  Collection<int, LocationSchedule>  $todaySchedules
     * @param  Collection<int, LocationSchedule>|null  $upcomingSchedules
     * @param  Collection<int, LocationSchedule>|null  $pastSchedules
     */
    public function __construct(
        public Location $location,
        public LocationStatusEvaluation $currentEvaluation,
        public LocationDailyAvailabilityStatus $dailyStatus,
        public Collection $todaySchedules,
        public ?Collection $upcomingSchedules = null,
        public ?Collection $pastSchedules = null,
    ) {
        $this->upcomingSchedules ??= collect();
        $this->pastSchedules ??= collect();
    }

    /**
     * 詳細モーダル表示用の利用枠ペイロード（現在・今後／過去に分離）を生成
     *
     * @return array{
     *     upcoming: list<array{
     *         id: int,
     *         start_at_display: string,
     *         end_at_display: string,
     *         availability_status: string,
     *         status_label: string,
     *         status_badge_class: string,
     *         title: string,
     *         note: string,
     *         is_current: bool
     *     }>,
     *     past: list<array{
     *         id: int,
     *         start_at_display: string,
     *         end_at_display: string,
     *         availability_status: string,
     *         status_label: string,
     *         status_badge_class: string,
     *         title: string,
     *         note: string,
     *         is_current: bool
     *     }>
     * }
     */
    public function toDetailSchedulesPayload(Carbon $now): array
    {
        return [
            'upcoming' => ($this->upcomingSchedules ?? collect())
                ->map(fn (LocationSchedule $s): array => $this->formatDetailScheduleItem($s, $now))
                ->values()
                ->all(),
            'past' => ($this->pastSchedules ?? collect())
                ->map(fn (LocationSchedule $s): array => $this->formatDetailScheduleItem($s, $now))
                ->values()
                ->all(),
        ];
    }

    /**
     * 編集モーダルフォーム初期展開用の利用枠ペイロードを生成
     *
     * @return list<array{
     *     id: int,
     *     start_at: string,
     *     end_at: string,
     *     availability_status: string,
     *     title: string,
     *     note: string,
     *     is_past: bool
     * }>
     */
    public function toFormSchedulesPayload(Carbon $now): array
    {
        return $this->location->schedules
            ->map(fn (LocationSchedule $s): array => [
                'id' => (int) $s->id,
                'start_at' => $s->start_at ? $s->start_at->format('Y-m-d\TH:i') : '',
                'end_at' => $s->end_at ? $s->end_at->format('Y-m-d\TH:i') : '',
                'availability_status' => $s->availability_status ?? 'available',
                'title' => $s->title ?? '',
                'note' => $s->note ?? '',
                'is_past' => $s->end_at ? $s->end_at->lessThan($now) : false,
            ])
            ->values()
            ->all();
    }

    /**
     * 詳細モーダル用の単一スケジュール枠データを整形
     *
     * @return array{
     *     id: int,
     *     start_at_display: string,
     *     end_at_display: string,
     *     availability_status: string,
     *     status_label: string,
     *     status_badge_class: string,
     *     title: string,
     *     note: string,
     *     is_current: bool
     * }
     */
    private function formatDetailScheduleItem(LocationSchedule $schedule, Carbon $now): array
    {
        $startDisplay = $schedule->start_at ? $schedule->start_at->format('Y/m/d H:i') : '—';
        $endDisplay = '—';
        if ($schedule->end_at) {
            $endDisplay = $schedule->start_at && $schedule->start_at->isSameDay($schedule->end_at)
                ? $schedule->end_at->format('H:i')
                : $schedule->end_at->format('Y/m/d H:i');
        }

        $isCurrent = $schedule->start_at !== null
            && $schedule->end_at !== null
            && $schedule->start_at->lessThanOrEqualTo($now)
            && $schedule->end_at->greaterThanOrEqualTo($now);

        return [
            'id' => (int) $schedule->id,
            'start_at_display' => $startDisplay,
            'end_at_display' => $endDisplay,
            'availability_status' => (string) $schedule->availability_status,
            'status_label' => (string) $schedule->availability_status_label,
            'status_badge_class' => (string) $schedule->availability_status_badge_class,
            'title' => (string) ($schedule->title ?? ''),
            'note' => (string) ($schedule->note ?? ''),
            'is_current' => $isCurrent,
        ];
    }
}
