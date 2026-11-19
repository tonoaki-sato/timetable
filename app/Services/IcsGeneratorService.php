<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonInterface;

class IcsGeneratorService
{
    /**
     * VCALENDAR文字列を生成 (RFC 5545 / Asia/Tokyo)
     *
     * @param  array<int, array{
     *     uid: string,
     *     summary: string,
     *     start_date: CarbonInterface,
     *     end_date?: ?CarbonInterface,
     *     start_time?: ?string,
     *     end_time?: ?string,
     *     location?: ?string,
     *     description?: ?string
     * }>  $events
     */
    public function generate(array $events, string $calendarName = 'まつり総合管理システム'): string
    {
        $dtStamp = Carbon::now('UTC')->format('Ymd\THis\Z');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//Festival Timetable System//JP',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escapeText($calendarName),
            'X-WR-TIMEZONE:Asia/Tokyo',
            'BEGIN:VTIMEZONE',
            'TZID:Asia/Tokyo',
            'BEGIN:STANDARD',
            'DTSTART:19700101T000000',
            'TZOFFSETFROM:+0900',
            'TZOFFSETTO:+0900',
            'TZNAME:JST',
            'END:STANDARD',
            'END:VTIMEZONE',
        ];

        foreach ($events as $event) {
            $lines = array_merge($lines, $this->buildEventLines($event, $dtStamp));
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", $lines)."\r\n";
    }

    /**
     * 単一VEVENTの行配列を構築
     *
     * @param  array{
     *     uid: string,
     *     summary: string,
     *     start_date: CarbonInterface,
     *     end_date?: ?CarbonInterface,
     *     start_time?: ?string,
     *     end_time?: ?string,
     *     location?: ?string,
     *     description?: ?string
     * }  $event
     * @return array<int, string>
     */
    protected function buildEventLines(array $event, string $dtStamp): array
    {
        $startDate = Carbon::parse($event['start_date']->format('Y-m-d'), 'Asia/Tokyo');
        $endDate = isset($event['end_date']) && $event['end_date'] !== null
            ? Carbon::parse($event['end_date']->format('Y-m-d'), 'Asia/Tokyo')
            : $startDate->copy();

        $startTime = isset($event['start_time']) ? trim((string) $event['start_time']) : '';
        $endTime = isset($event['end_time']) ? trim((string) $event['end_time']) : '';

        $lines = [
            'BEGIN:VEVENT',
            'UID:'.$this->escapeText($event['uid']),
            'DTSTAMP:'.$dtStamp,
        ];

        if ($startTime === '') {
            // 終日イベント: RFC 5545準拠のためDTENDは翌日（排他的終了日）
            $exclusiveEnd = $endDate->copy()->addDay();
            $lines[] = 'DTSTART;VALUE=DATE:'.$startDate->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:'.$exclusiveEnd->format('Ymd');
        } else {
            $startAt = Carbon::parse($startDate->format('Y-m-d').' '.$this->normalizeTime($startTime), 'Asia/Tokyo');
            $endAt = $endTime !== ''
                ? Carbon::parse($endDate->format('Y-m-d').' '.$this->normalizeTime($endTime), 'Asia/Tokyo')
                : $startAt->copy()->addHour();

            if ($endAt->lte($startAt)) {
                $endAt = $startAt->copy()->addHour();
            }

            $lines[] = 'DTSTART;TZID=Asia/Tokyo:'.$startAt->format('Ymd\THis');
            $lines[] = 'DTEND;TZID=Asia/Tokyo:'.$endAt->format('Ymd\THis');
        }

        $lines[] = 'SUMMARY:'.$this->escapeText($event['summary']);

        if (! empty($event['location'])) {
            $lines[] = 'LOCATION:'.$this->escapeText((string) $event['location']);
        }

        if (! empty($event['description'])) {
            $lines[] = 'DESCRIPTION:'.$this->escapeText((string) $event['description']);
        }

        $lines[] = 'END:VEVENT';

        return $lines;
    }

    /**
     * 時刻文字列を HH:MM:SS へ正規化
     */
    protected function normalizeTime(string $time): string
    {
        $parts = explode(':', $time);
        $hour = str_pad((string) ((int) ($parts[0] ?? 0)), 2, '0', STR_PAD_LEFT);
        $minute = str_pad((string) ((int) ($parts[1] ?? 0)), 2, '0', STR_PAD_LEFT);
        $second = str_pad((string) ((int) ($parts[2] ?? 0)), 2, '0', STR_PAD_LEFT);

        return "{$hour}:{$minute}:{$second}";
    }

    /**
     * RFC 5545準拠のテキストエスケープ
     */
    protected function escapeText(string $text): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\r", "\n"],
            ['\\\\', '\\;', '\\,', '\\n', '\\n', '\\n'],
            $text
        );
    }
}
