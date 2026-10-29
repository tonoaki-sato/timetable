<?php

namespace App\DTOs;

use App\Models\Timetable;
use App\Models\TimetableTask;
use Illuminate\Support\Collection;

readonly class NextTasksContext
{
    /**
     * @param  Collection<int, TimetableTask>  $tasks
     */
    public function __construct(
        public bool $isMainEventDay,
        public ?Timetable $activeTimetable,
        public Collection $tasks,
        public int $totalNextCount,
        public ?string $nextStartTimeLabel,
        public ?string $remainingTimeLabel,
        public string $actionUrl,
    ) {}
}
