<?php

namespace App\DTOs\Lane;

/**
 * 担当班一覧PDFの1行分の表示データを保持するDTO
 */
readonly class LanePrintRowDTO
{
    public function __construct(
        public int $orderNumber,
        public string $name,
        public string $colorKey,
        public string $leadName,
        public string $leadAffiliation,
        public bool $hasLead,
        public string $description,
    ) {}
}
