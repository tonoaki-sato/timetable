<?php

namespace App\DTOs;

use App\Enums\AnnualPhase;
use App\Models\AnnualSchedule;
use App\Models\Timetable;

readonly class PhaseContext
{
    public function __construct(
        public AnnualPhase $phase,
        public bool $isMainEventDay,
        public ?Timetable $activeTimetable = null,
        public ?AnnualSchedule $nextTask = null,
        public int $pendingTaskCount = 0,
        public ?string $actionUrl = null,
        public ?string $displayName = null,
        public ?string $subStatus = null,
    ) {}
}
