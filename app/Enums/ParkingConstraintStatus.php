<?php

namespace App\Enums;

enum ParkingConstraintStatus: string
{
    case Violation = 'violation';
    case Warning = 'warning';
    case InUse = 'in_use';
    case Standby = 'standby';

    /**
     * ステータス名（短縮ラベル）
     */
    public function label(): string
    {
        return match ($this) {
            self::Violation => '制約違反',
            self::Warning => '返却接近',
            self::InUse => '利用中 (適正)',
            self::Standby => '待機中',
        };
    }

    /**
     * バッジ表示用クラス
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Violation => 'bg-red-100 text-red-700 border border-red-300 font-bold',
            self::Warning => 'bg-amber-100 text-amber-800 border border-amber-300 font-bold',
            self::InUse => 'bg-emerald-100 text-emerald-800 border border-emerald-300 font-bold',
            self::Standby => 'bg-slate-100 text-slate-700 border border-slate-200 font-medium',
        };
    }

    /**
     * カードの枠線・背景ハイライト用クラス
     */
    public function containerClasses(): string
    {
        return match ($this) {
            self::Violation => 'bg-red-50/60 border-red-200 hover:border-red-300',
            self::Warning => 'bg-amber-50/60 border-amber-200 hover:border-amber-300',
            self::InUse => 'bg-slate-50/80 border-slate-200 hover:border-slate-300',
            self::Standby => 'bg-slate-50/40 border-slate-200 hover:border-slate-300',
        };
    }

    /**
     * アイコン
     */
    public function icon(): string
    {
        return match ($this) {
            self::Violation => '🚨',
            self::Warning => '⚠️',
            self::InUse => '🟢',
            self::Standby => '🅿️',
        };
    }

    /**
     * アラート状態か
     */
    public function isAlert(): bool
    {
        return match ($this) {
            self::Violation, self::Warning => true,
            default => false,
        };
    }
}
