<?php

namespace App\DTOs;

use App\Enums\AnnualPhase;
use App\Models\Schedule;
use App\Models\Timetable;

readonly class PhaseContext
{
    public function __construct(
        public AnnualPhase $phase,
        public bool $isMainEventDay,
        public ?Timetable $activeTimetable = null,
        public ?Schedule $nextTask = null,
        public int $pendingTaskCount = 0,
        public ?string $actionUrl = null,
        public ?string $displayName = null,
        public ?string $subStatus = null,
    ) {}
}
