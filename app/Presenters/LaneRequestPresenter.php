<?php

namespace App\Presenters;

use App\Models\LaneRequest;

class LaneRequestPresenter
{
    public function __construct(
        protected LaneRequest $laneRequest
    ) {}

    /**
     * 要請・申請種別の日本語表示ラベルを取得
     */
    public function typeLabel(): string
    {
        return $this->laneRequest->request_type_enum?->label()
            ?? (string) $this->laneRequest->request_type;
    }

    /**
     * 対応ステータスの日本語表示ラベルを取得
     */
    public function statusLabel(): string
    {
        return $this->laneRequest->status_enum?->label()
            ?? (string) $this->laneRequest->status;
    }
}
