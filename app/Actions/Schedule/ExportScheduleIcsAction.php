<?php

namespace App\Actions\Schedule;

use App\Models\Schedule;
use App\Services\IcsGeneratorService;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Response;

class ExportScheduleIcsAction
{
    public function __construct(
        protected IcsGeneratorService $icsGenerator
    ) {}

    /**
     * 単一スケジュールの .ics レスポンスを生成
     */
    public function executeSingle(Schedule $schedule): Response
    {
        if (! $schedule->start_date) {
            abort(404, '開催日が未設定のスケジュールはエクスポートできません。');
        }

        $schedule->loadMissing(['assignee', 'timetable']);

        $content = $this->icsGenerator->generate(
            events: [$this->mapScheduleToEvent($schedule)],
            calendarName: $schedule->title
        );

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"schedule_{$schedule->id}.ics\"",
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * スケジュール一覧（複数件）の .ics レスポンスを生成
     *
     * @param  Collection<int, Schedule>  $schedules
     */
    public function executeCollection(Collection $schedules): Response
    {
        $events = $schedules
            ->filter(fn (Schedule $schedule): bool => $schedule->start_date !== null)
            ->map(fn (Schedule $schedule): array => $this->mapScheduleToEvent($schedule))
            ->values()
            ->all();

        $content = $this->icsGenerator->generate(
            events: $events,
            calendarName: 'まつり年間スケジュール'
        );

        $fileName = 'festival_schedules_'.now()->format('Ymd').'.ics';

        return response($content, 200, [
            'Content-Type' => 'text/calendar; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
            'Cache-Control' => 'no-cache, must-revalidate',
        ]);
    }

    /**
     * ScheduleモデルをVEVENT配列へ変換
     *
     * @return array{
     *     uid: string,
     *     summary: string,
     *     start_date: CarbonInterface,
     *     end_date: ?CarbonInterface,
     *     start_time: ?string,
     *     end_time: ?string,
     *     location: ?string,
     *     description: ?string
     * }
     */
    protected function mapScheduleToEvent(Schedule $schedule): array
    {
        $descriptionParts = [];
        $categoryLabel = $schedule->presenter()->categoryLabel();
        if ($categoryLabel !== '') {
            $descriptionParts[] = "区分: {$categoryLabel}";
        }
        if ($schedule->assignee) {
            $descriptionParts[] = "担当: {$schedule->assignee->name}";
        }
        if ($schedule->description) {
            $descriptionParts[] = $schedule->description;
        }

        return [
            'uid' => "schedule-{$schedule->id}@festival-timetable",
            'summary' => $schedule->title,
            'start_date' => $schedule->start_date,
            'end_date' => $schedule->end_date,
            'start_time' => $schedule->start_time,
            'end_time' => $schedule->end_time,
            'location' => $schedule->location,
            'description' => implode("\n", $descriptionParts),
        ];
    }
}
