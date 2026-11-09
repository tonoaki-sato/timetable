<?php

namespace App\Enums;

enum LocationDailyAvailabilityStatus: string
{
    case Available = 'available';                       // 利用可（確保済）
    case PartiallyUnavailable = 'partially_unavailable'; // 一部未確保
    case NoSchedule = 'no_schedule';                    // 本日枠無し

    /**
     * 日本語表示ラベル
     */
    public function label(): string
    {
        return match ($this) {
            self::Available => '利用可（確保済）',
            self::PartiallyUnavailable => '一部未確保',
            self::NoSchedule => '本日枠無し',
        };
    }

    /**
     * バッジのTailwind CSSクラス
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Available => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::PartiallyUnavailable => 'bg-amber-50 text-amber-700 border-amber-200',
            self::NoSchedule => 'bg-slate-50 text-slate-500 border-slate-200',
        };
    }

    /**
     * 表示アイコン
     */
    public function icon(): string
    {
        return match ($this) {
            self::Available => '✅',
            self::PartiallyUnavailable => '⚠️',
            self::NoSchedule => 'ー',
        };
    }
}
