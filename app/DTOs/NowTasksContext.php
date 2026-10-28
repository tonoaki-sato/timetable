<?php

namespace App\DTOs;

use App\Models\Timetable;
use App\Models\TimetableTask;
use Illuminate\Support\Collection;

readonly class NowTasksContext
{
    /**
     * @param  Collection<int, TimetableTask>  $tasks
     */
    public function __construct(
        public bool $isMainEventDay,
        public ?Timetable $activeTimetable,
        public Collection $tasks,
        public string $currentTimeLabel,
        public string $actionUrl,
    ) {}
}
