<?php

namespace App\DTOs\Lane;

use App\Models\Timetable;

/**
 * 担当班一覧PDFの帳票全体データ・ヘッダーサマリーを保持するDTO
 */
readonly class LaneListPrintDataDTO
{
    /**
     * @param  array<int, LanePrintRowDTO>  $rows
     */
    public function __construct(
        public Timetable $timetable,
        public string $title,
        public string $generatedAt,
        public int $totalLanes,
        public array $rows,
        public ?float $uniformRowHeightPt = null,
    ) {}
}
