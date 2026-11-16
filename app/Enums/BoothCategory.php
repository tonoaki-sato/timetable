<?php

namespace App\Enums;

enum BoothCategory: string
{
    case GozaOutdoorCooking = 'goza_outdoor_cooking';
    case GozaCookedFood = 'goza_cooked_food';
    case GozaGeneralGoods = 'goza_general_goods';
    case CorporateBooth = 'corporate_booth';
    case SpecialEvent = 'special_event';
    case SpecialDirect = 'special_direct';

    /**
     * UI表示用日本語ラベル
     */
    public function label(): string
    {
        return match ($this) {
            self::GozaOutdoorCooking => 'ござ市（屋外調理販売）',
            self::GozaCookedFood => 'ござ市（調理済品販売）',
            self::GozaGeneralGoods => 'ござ市（一般雑貨販売）',
            self::CorporateBooth => '企業ブース',
            self::SpecialEvent => '特設',
            self::SpecialDirect => '特設（直営）',
        };
    }

    /**
     * 絵文字アイコン
     */
    public function icon(): string
    {
        return match ($this) {
            self::GozaOutdoorCooking => '🔴',
            self::GozaCookedFood => '🟢',
            self::GozaGeneralGoods => '🛍️',
            self::CorporateBooth => '🏢',
            self::SpecialEvent => '🎪',
            self::SpecialDirect => '🏛️',
        };
    }

    /**
     * バッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::GozaOutdoorCooking => 'bg-rose-50 text-rose-700 border-rose-300 font-bold',
            self::GozaCookedFood => 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold',
            self::GozaGeneralGoods => 'bg-sky-50 text-sky-700 border-sky-300',
            self::CorporateBooth => 'bg-indigo-50 text-indigo-700 border-indigo-300 font-bold',
            self::SpecialEvent => 'bg-amber-50 text-amber-700 border-amber-300 font-bold',
            self::SpecialDirect => 'bg-purple-50 text-purple-700 border-purple-300 font-bold',
        };
    }

    /**
     * 通りマップ枠線のCSSクラスを取得
     */
    public function borderClasses(): string
    {
        return match ($this) {
            self::GozaOutdoorCooking => 'border-2 border-rose-500 bg-rose-50/60 text-rose-900',
            self::GozaCookedFood => 'border-2 border-emerald-500 bg-emerald-50/60 text-emerald-900',
            self::CorporateBooth => 'border-2 border-indigo-500 bg-indigo-50/60 text-indigo-900',
            self::SpecialEvent => 'border-2 border-amber-500 bg-amber-50/60 text-amber-900',
            self::SpecialDirect => 'border-2 border-purple-500 bg-purple-50/60 text-purple-900',
            default => 'border border-slate-300 bg-white text-slate-800',
        };
    }
}
