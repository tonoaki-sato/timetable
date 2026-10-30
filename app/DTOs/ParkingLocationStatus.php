<?php

namespace App\DTOs;

use App\Enums\ParkingConstraintStatus;
use App\Models\LogisticsLocation;
use Illuminate\Support\Collection;

readonly class ParkingLocationStatus
{
    /**
     * @param  Collection<int, mixed>  $activePlans
     * @param  array<string>  $violations
     * @param  array<string>  $warnings
     */
    public function __construct(
        public LogisticsLocation $location,
        public ParkingConstraintStatus $status,
        public string $statusDetail,
        public ?string $periodLabel,
        public ?string $countdownText,
        public Collection $activePlans,
        public int $storedEquipmentCount,
        public array $violations = [],
        public array $warnings = [],
    ) {}

    /**
     * 違反メッセージが存在するか
     */
    public function hasViolations(): bool
    {
        return ! empty($this->violations);
    }

    /**
     * 警告メッセージが存在するか
     */
    public function hasWarnings(): bool
    {
        return ! empty($this->warnings);
    }
}

