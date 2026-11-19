<?php

namespace App\Presenters;

use App\Enums\TimetableStatus;
use App\Models\Timetable;
use Illuminate\Support\Collection;

class TimetablePresenter
{
    public function __construct(
        protected Timetable $timetable
    ) {}

    /**
     * ステータスバッジのCSSクラスを取得
     */
    public function statusBadgeClass(): string
    {
        $enum = TimetableStatus::tryFrom((string) $this->timetable->status) ?? TimetableStatus::Draft;

        return $enum->badgeClasses();
    }

    /**
     * 登録済み運行日程から開催期間ラベルを構築
     *
     * @param  Collection<int, Timetable>  $timetables
     */
    public static function formatEventDateRange(Collection $timetables): string
    {
        if ($timetables->isEmpty()) {
            return '開催日程準備中';
        }

        $first = $timetables->first();
        $last = $timetables->last();

        if (! $first || ! $first->target_date || ! $last || ! $last->target_date) {
            return '開催日程準備中';
        }

        $weekdays = ['日', '月', '火', '水', '木', '金', '土'];
        $startStr = $first->target_date->format('Y年n月j日').'('.$weekdays[$first->target_date->dayOfWeek].')';

        if ($first->target_date->isSameDay($last->target_date)) {
            return $startStr.' 開催';
        }

        $endPrefix = $first->target_date->year === $last->target_date->year
            ? $last->target_date->format('n月j日')
            : $last->target_date->format('Y年n月j日');
        $endStr = $endPrefix.'('.$weekdays[$last->target_date->dayOfWeek].')';

        return "{$startStr} 〜 {$endStr} 開催";
    }

    /**
     * フォーム表示用の歩行者天国（道路規制）時間帯配列を取得
     *
     * @return array<int, array<string, mixed>>
     */
    public static function resolveRoadPeriodsForForm(mixed $oldPeriods = null, ?Timetable $timetable = null): array
    {
        $periods = $oldPeriods ?? ($timetable?->road_periods ?? []);

        return is_array($periods) ? $periods : [];
    }

    /**
     * フォーム表示用のまつり開催時間帯配列を取得
     *
     * @return array<int, array<string, mixed>>
     */
    public static function resolveFestivalPeriodsForForm(mixed $oldPeriods = null, ?Timetable $timetable = null): array
    {
        $periods = $oldPeriods ?? ($timetable?->festival_periods ?? []);

        return is_array($periods) ? $periods : [];
    }
}
