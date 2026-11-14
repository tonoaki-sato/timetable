<?php

namespace App\Enums;

enum BoothCategory: string
{
    case FoodCooking = 'food_cooking';
    case FoodNonCooking = 'food_non_cooking';
    case GeneralMerchandise = 'general_merchandise';
    case SpecialEvent = 'special_event';
    case CorporateKitchenCar = 'corporate_kitchen_car';
    case CorporateTent = 'corporate_tent';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::FoodCooking => '調理屋外食品（赤枠）',
            self::FoodNonCooking => '非調理・屋内調理（緑枠）',
            self::GeneralMerchandise => '一般物販',
            self::SpecialEvent => '特設',
            self::CorporateKitchenCar => '企業ブース（キッチンカー）',
            self::CorporateTent => '企業ブース（テント）',
        };
    }

    /**
     * バッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::FoodCooking => 'bg-rose-50 text-rose-700 border-rose-300 font-bold',
            self::FoodNonCooking => 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold',
            self::GeneralMerchandise => 'bg-sky-50 text-sky-700 border-sky-300',
            self::SpecialEvent => 'bg-amber-50 text-amber-700 border-amber-300',
            self::CorporateKitchenCar => 'bg-indigo-50 text-indigo-700 border-indigo-300',
            self::CorporateTent => 'bg-purple-50 text-purple-700 border-purple-300',
        };
    }

    /**
     * 通りマップ枠線のCSSクラスを取得
     */
    public function borderClasses(): string
    {
        return match ($this) {
            self::FoodCooking => 'border-2 border-rose-500 bg-rose-50/60 text-rose-900',
            self::FoodNonCooking => 'border-2 border-emerald-500 bg-emerald-50/60 text-emerald-900',
            self::GeneralMerchandise => 'border border-sky-400 bg-sky-50/60 text-sky-900',
            self::SpecialEvent => 'border border-amber-400 bg-amber-50/60 text-amber-900',
            self::CorporateKitchenCar => 'border-2 border-indigo-500 bg-indigo-50/60 text-indigo-900',
            self::CorporateTent => 'border-2 border-purple-500 bg-purple-50/60 text-purple-900',
        };
    }

    /**
     * セレクトボックス用オプション配列
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
