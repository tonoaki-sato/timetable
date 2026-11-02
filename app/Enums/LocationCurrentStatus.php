<?php

namespace App\Enums;

enum LocationCurrentStatus: string
{
    case Available = 'available';                     // 利用可能（時間枠内 かつ 利用可・確保済）
    case UnavailablePending = 'unavailable_pending';  // 利用不可（未確保・調整中）
    case UnavailableOutOfHours = 'unavailable_out_of_hours'; // 利用不可（利用時間外・中抜け時間帯）
    case Inactive = 'inactive';                       // 無効（非稼働）
    case NoSchedule = 'no_schedule';                 // スケジュール未設定

    /**
     * 日本語ラベル
     */
    public function label(): string
    {
        return match ($this) {
            self::Available => '利用可（確保済）',
            self::UnavailablePending => '未確保（調整中）',
            self::UnavailableOutOfHours => '利用時間外',
            self::Inactive => '無効',
            self::NoSchedule => '枠未登録',
        };
    }

    /**
     * バッジのCSSクラス
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Available => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::UnavailablePending => 'bg-amber-100 text-amber-800 border-amber-300',
            self::UnavailableOutOfHours => 'bg-slate-100 text-slate-700 border-slate-300',
            self::Inactive => 'bg-zinc-100 text-zinc-400 border-zinc-200',
            self::NoSchedule => 'bg-gray-100 text-gray-500 border-gray-200',
        };
    }

    /**
     * アイコン
     */
    public function icon(): string
    {
        return match ($this) {
            self::Available => '✅',
            self::UnavailablePending => '⏳',
            self::UnavailableOutOfHours => '⚠️',
            self::Inactive => '💤',
            self::NoSchedule => '📋',
        };
    }
}
