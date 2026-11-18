<?php

namespace App\DTOs\Lane;

/**
 * 担当班一覧PDFの1行分の表示データを保持するDTO
 */
readonly class LanePrintRowDTO
{
    /**
     * @param  array<int, string>  $assignedStaffNames
     */
    public function __construct(
        public int $orderNumber,
        public string $name,
        public string $colorKey,
        public string $leadName,
        public string $leadAffiliation,
        public bool $hasLead,
        public string $description,
        public array $assignedStaffNames = [],
    ) {}
}
