<?php

namespace App\Enums;

enum LocationCurrentStatus: string
{
    case Available = 'available';                     // 利用可能（時間枠内 かつ 承認済/申請不要）
    case UnavailableUnapplied = 'unavailable_unapplied'; // 利用不可（未申請）
    case UnavailableApplying = 'unavailable_applying';   // 利用不可（申請審査中）
    case UnavailableRejected = 'unavailable_rejected';   // 利用不可（申請却下）
    case UnavailableOutOfHours = 'unavailable_out_of_hours'; // 利用不可（利用時間外）
    case Completed = 'completed';                     // 利用終了（全枠終了後）
    case Inactive = 'inactive';                       // 無効（非稼働）
    case NoSchedule = 'no_schedule';                 // スケジュール未設定

    /**
     * 日本語ラベル
     */
    public function label(): string
    {
        return match ($this) {
            self::Available => '利用可能',
            self::UnavailableUnapplied => '未申請（利用不可）',
            self::UnavailableApplying => '申請審査中（利用不可）',
            self::UnavailableRejected => '申請却下（利用不可）',
            self::UnavailableOutOfHours => '利用時間外',
            self::Completed => '利用終了',
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
            self::UnavailableUnapplied => 'bg-rose-100 text-rose-800 border-rose-300',
            self::UnavailableApplying => 'bg-amber-100 text-amber-800 border-amber-300',
            self::UnavailableRejected => 'bg-red-100 text-red-900 border-red-300',
            self::UnavailableOutOfHours => 'bg-slate-100 text-slate-700 border-slate-300',
            self::Completed => 'bg-slate-100 text-slate-500 border-slate-200',
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
            self::UnavailableUnapplied => '⛔',
            self::UnavailableApplying => '⏳',
            self::UnavailableRejected => '❌',
            self::UnavailableOutOfHours => '⚠️',
            self::Completed => '⏹️',
            self::Inactive => '💤',
            self::NoSchedule => '📋',
        };
    }
}
