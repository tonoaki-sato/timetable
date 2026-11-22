<?php

namespace App\Presenters;

use App\Models\LocationSchedule;

class LocationSchedulePresenter
{
    public function __construct(
        protected LocationSchedule $locationSchedule
    ) {}

    /**
     * 確保ステータスの日本語ラベルを取得
     */
    public function availabilityStatusLabel(): string
    {
        return LocationSchedule::AVAILABILITY_STATUSES[$this->locationSchedule->availability_status]
            ?? (string) $this->locationSchedule->availability_status;
    }

    /**
     * 確保ステータスのバッジCSSクラスを取得
     */
    public function availabilityStatusBadgeClass(): string
    {
        return match ($this->locationSchedule->availability_status) {
            'available' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            'unavailable' => 'bg-amber-100 text-amber-800 border-amber-300',
            default => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }
}
