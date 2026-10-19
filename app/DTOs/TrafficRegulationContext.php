<?php

namespace App\DTOs;

use App\Enums\TrafficRegulationStatus;

readonly class TrafficRegulationContext
{
    public function __construct(
        public TrafficRegulationStatus $status,
        public string $badgeText,
        public string $headline,
        public string $subStatus,
        public bool $isRoadClosed,
        public bool $isAlertActive = false,
        public ?string $actionUrl = null,
    ) {}
}
