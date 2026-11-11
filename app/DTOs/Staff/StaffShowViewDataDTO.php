<?php

namespace App\DTOs\Staff;

use App\Models\Staff;

readonly class StaffShowViewDataDTO
{
    public function __construct(
        public Staff $staff,
        public string $shiftUrl,
        public string $qrDataUri,
    ) {}

    /**
     * ビューへ展開する配列形式に変換
     *
     * @return array{
     *     viewData: self,
     *     staff: Staff,
     *     shiftUrl: string,
     *     qrDataUri: string
     * }
     */
    public function toArray(): array
    {
        return [
            'viewData' => $this,
            'staff' => $this->staff,
            'shiftUrl' => $this->shiftUrl,
            'qrDataUri' => $this->qrDataUri,
        ];
    }
}

