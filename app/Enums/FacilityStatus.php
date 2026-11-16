<?php

namespace App\Enums;

enum FacilityStatus: string
{
    case Tentative = 'tentative';
    case Confirmed = 'confirmed';

    /**
     * UI表示用日本語ラベル
     */
    public function label(): string
    {
        return match ($this) {
            self::Tentative => '未定',
            self::Confirmed => '確定',
        };
    }

    /**
     * バッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Tentative => 'bg-amber-50 text-amber-700 border-amber-300 font-medium',
            self::Confirmed => 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold',
        };
    }
}
