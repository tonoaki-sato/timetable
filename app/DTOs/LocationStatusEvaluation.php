<?php

namespace App\DTOs;

use App\Enums\LocationCurrentStatus;
use App\Models\LocationSchedule;

class LocationStatusEvaluation
{
    public function __construct(
        public LocationCurrentStatus $status,
        public string $detail,
        public ?LocationSchedule $matchedSchedule = null,
        public ?LocationSchedule $nextSchedule = null,
    ) {}

    public function label(): string
    {
        return $this->status->label();
    }

    public function badgeClass(): string
    {
        return $this->status->badgeClass();
    }

    public function icon(): string
    {
        return $this->status->icon();
    }
}
