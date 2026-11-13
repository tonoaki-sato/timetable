<?php

namespace App\DTOs\Staff;

readonly class MyShiftLaneFilterOptionDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $icon,
        public int $assignmentCount,
    ) {}
}
