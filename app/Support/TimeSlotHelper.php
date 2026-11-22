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
}
