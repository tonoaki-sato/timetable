<?php

namespace App\DTOs\Staff;

readonly class MyShiftTimetableOptionDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $dateLabel,
        public string $shortDateLabel,
        public string $weekdayLabel,
        public string $temporalStatus,
        public string $temporalLabel,
        public int $assignmentCount,
        public int $ledLaneCount,
        public bool $isSelected,
    ) {}
}

