<?php

namespace App\Support;

use App\Models\Timetable;
use Carbon\Carbon;

class TimeSlotHelper
{
    /**
     * 運行タイムテーブルの開始・終了時刻から30分刻みの時間スロット配列を生成
     *
     * @return array<int, array{
     *     time: string,
     *     label: string,
     *     road: array{status: string, label: string},
     *     festival: array{status: string, label: string}
     * }>
     */
    public static function generateForTimetable(?Timetable $timetable): array
    {
        $startTime = '07:00';
        $endTime = '19:30';

        if ($timetable !== null) {
            if (! empty($timetable->start_time)) {
                $sH = (int) substr($timetable->start_time, 0, 2);
                $sM = (int) substr($timetable->start_time, 3, 2);
                $sM = $sM >= 30 ? 30 : 0;
                $startTime = sprintf('%02d:%02d', $sH, $sM);
            }
            if (! empty($timetable->end_time)) {
                $eH = (int) substr($timetable->end_time, 0, 2);
                $eM = (int) substr($timetable->end_time, 3, 2);
                if ($eM > 30) {
                    $eH++;
                    $eM = 0;
                } elseif ($eM > 0) {
                    $eM = 30;
                }
                $endTime = sprintf('%02d:%02d', $eH, $eM);
            }
        }

        $slots = [];
        $current = Carbon::createFromFormat('H:i', $startTime);
        $end = Carbon::createFromFormat('H:i', $endTime);

        $count = 0;
        while ($current->lte($end) && $count < 48) {
            $timeStr = $current->format('H:i');

            $roadData = $timetable !== null
                ? $timetable->getRoadStatusAt($timeStr)
                : ['status' => 'open', 'label' => '-'];

            $festivalData = $timetable !== null
                ? $timetable->getFestivalStatusAt($timeStr)
                : ['status' => 'inactive', 'label' => '-'];

            $slots[] = [
                'time' => $timeStr,
                'label' => $timeStr,
                'road' => $roadData,
                'festival' => $festivalData,
            ];

            $current->addMinutes(30);
            $count++;
        }

        return $slots;
    }

    /**
     * HH:MM 形式文字列を 00:00 からの経過分数へ変換
     */
    public static function hhmmToMinutes(string $hhmm): int
    {
        $parts = explode(':', $hhmm);
        if (count($parts) < 2) {
            return 0;
        }

        return ((int) $parts[0] * 60) + (int) $parts[1];
    }

    /**
     * 経過分数を HH:MM 形式文字列へ変換
     */
    public static function minutesToHhmm(int $minutes): string
    {
        $clamped = max(0, min(24 * 60, $minutes));
        $h = intdiv($clamped, 60);
        $m = $clamped % 60;

        return sprintf('%02d:%02d', $h, $m);
    }

    /**
     * タイムテーブルとタスク群全体を包含するタイムライン表示範囲（30分単位丸め、分単位）を算出
     *
     * @param  iterable<int, object>  $tasks
     * @return array{start_min: int, end_min: int}
     */
    public static function resolveTimelineBoundsMinutes(?Timetable $timetable, iterable $tasks = []): array
    {
        $minMinutes = null;
        $maxMinutes = null;

        if ($timetable !== null) {
            if (! empty($timetable->start_time)) {
                $minMinutes = self::hhmmToMinutes(substr((string) $timetable->start_time, 0, 5));
            }
            if (! empty($timetable->end_time)) {
                $maxMinutes = self::hhmmToMinutes(substr((string) $timetable->end_time, 0, 5));
            }
        }

        foreach ($tasks as $task) {
            $startTime = $task->start_time ?? null;
            $endTime = $task->end_time ?? null;
            if (! empty($startTime)) {
                $sMin = self::hhmmToMinutes(substr((string) $startTime, 0, 5));
                $minMinutes = $minMinutes === null ? $sMin : min($minMinutes, $sMin);
            }
            if (! empty($endTime)) {
                $eMin = self::hhmmToMinutes(substr((string) $endTime, 0, 5));
                $maxMinutes = $maxMinutes === null ? $eMin : max($maxMinutes, $eMin);
            }
        }

        $minMinutes ??= self::hhmmToMinutes('07:00');
        $maxMinutes ??= self::hhmmToMinutes('20:00');

        $startBound = (int) (floor($minMinutes / 30) * 30);
        $endBound = (int) (ceil($maxMinutes / 30) * 30);

        if ($endBound <= $startBound) {
            $endBound = $startBound + 60;
        }

        return [
            'start_min' => $startBound,
            'end_min' => $endBound,
        ];
    }

    /**
     * 時間区間群を貪欲法（Greedy Interval Partitioning）で重ならない最小段数のサブトラックへ割り振る
     *
     * @template TItem of array{start_min: int, end_min: int}
     *
     * @param  array<int, TItem>  $items
     * @return array{tracks_count: int, items: array<int, array<string, mixed>>}
     */
    public static function packIntervalsIntoTracks(array $items): array
    {
        if ($items === []) {
            return [
                'tracks_count' => 1,
                'items' => [],
            ];
        }

        usort($items, static function (array $a, array $b): int {
            if ($a['start_min'] !== $b['start_min']) {
                return $a['start_min'] <=> $b['start_min'];
            }
            if ($a['end_min'] !== $b['end_min']) {
                return $a['end_min'] <=> $b['end_min'];
            }

            return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
        });

        /** @var array<int, int> $trackEnds */
        $trackEnds = [];
        $packed = [];

        foreach ($items as $item) {
            $assignedTrack = null;
            foreach ($trackEnds as $trackIdx => $lastEnd) {
                if ($lastEnd <= $item['start_min']) {
                    $assignedTrack = $trackIdx;
                    $trackEnds[$trackIdx] = max($item['end_min'], $item['start_min'] + 1);
                    break;
                }
            }

            if ($assignedTrack === null) {
                $assignedTrack = count($trackEnds);
                $trackEnds[] = max($item['end_min'], $item['start_min'] + 1);
            }

            $item['sub_track'] = $assignedTrack;
            $packed[] = $item;
        }

        // 各アイテムについて同一配列内で時間帯重複している他アイテムが存在するか判定
        $count = count($packed);
        for ($i = 0; $i < $count; $i++) {
            $hasOverlap = false;
            for ($j = 0; $j < $count; $j++) {
                if ($i === $j) {
                    continue;
                }
                if ($packed[$i]['start_min'] < $packed[$j]['end_min'] && $packed[$i]['end_min'] > $packed[$j]['start_min']) {
                    $hasOverlap = true;
                    break;
                }
            }
            $packed[$i]['has_overlap'] = $hasOverlap;
        }

        return [
            'tracks_count' => max(1, count($trackEnds)),
            'items' => $packed,
        ];
    }

    /**
     * 重複する複数の時間区間をマージし、実拘束分数（二重計上なし）を算出
     *
     * @param  iterable<int, array{0: int, 1: int}>  $intervals
     */
    public static function calculateMergedDurationMinutes(iterable $intervals): int
    {
        $normalized = [];
        foreach ($intervals as $interval) {
            $start = (int) ($interval[0] ?? 0);
            $end = (int) ($interval[1] ?? 0);
            if ($end > $start) {
                $normalized[] = [$start, $end];
            }
        }

        if ($normalized === []) {
            return 0;
        }

        usort($normalized, static function (array $a, array $b): int {
            if ($a[0] !== $b[0]) {
                return $a[0] <=> $b[0];
            }

            return $a[1] <=> $b[1];
        });

        $mergedMinutes = 0;
        $curStart = $normalized[0][0];
        $curEnd = $normalized[0][1];

        $total = count($normalized);
        for ($i = 1; $i < $total; $i++) {
            [$nextStart, $nextEnd] = $normalized[$i];
            if ($nextStart <= $curEnd) {
                $curEnd = max($curEnd, $nextEnd);
            } else {
                $mergedMinutes += ($curEnd - $curStart);
                $curStart = $nextStart;
                $curEnd = $nextEnd;
            }
        }

        $mergedMinutes += ($curEnd - $curStart);

        return $mergedMinutes;
    }

    /**
     * タイムテーブルの道路規制帯・開催帯を分単位バンド配列へ正規化
     *
     * @return array{
     *     road_bands: list<array{start_min: int, end_min: int, label: string, status: string}>,
     *     festival_bands: list<array{start_min: int, end_min: int, label: string, status: string}>
     * }
     */
    public static function extractPeriodBands(Timetable $timetable, int $boundStartMin, int $boundEndMin): array
    {
        $normalize = static function (?array $periods, string $defaultLabel, string $defaultStatus) use ($boundStartMin, $boundEndMin): array {
            if (empty($periods)) {
                return [];
            }

            $bands = [];
            foreach ($periods as $period) {
                $sStr = substr((string) ($period['start_time'] ?? ''), 0, 5);
                $eStr = substr((string) ($period['end_time'] ?? ''), 0, 5);
                if ($sStr === '' || $eStr === '') {
                    continue;
                }

                $sMin = max($boundStartMin, self::hhmmToMinutes($sStr));
                $eMin = min($boundEndMin, self::hhmmToMinutes($eStr));
                if ($eMin <= $sMin) {
                    continue;
                }

                $bands[] = [
                    'start_min' => $sMin,
                    'end_min' => $eMin,
                    'label' => (string) ($period['label'] ?? $defaultLabel),
                    'status' => (string) ($period['status'] ?? $defaultStatus),
                ];
            }

            return $bands;
        };

        return [
            'road_bands' => $normalize($timetable->road_periods, '通行止め', 'closed'),
            'festival_bands' => $normalize($timetable->festival_periods, '開催中', 'active'),
        ];
    }
}
