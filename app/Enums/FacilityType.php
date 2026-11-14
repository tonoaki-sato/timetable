<?php

namespace App\Enums;

enum FacilityType: string
{
    case WaterSupply = 'water_supply';
    case ToiletPaid = 'toilet_paid';
    case ToiletFree = 'toilet_free';
    case NursingRoom = 'nursing_room';
    case FirstAid = 'first_aid';
    case Headquarters = 'headquarters';
    case StaffWaitingArea = 'staff_waiting_area';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::WaterSupply => '給水設備',
            self::ToiletPaid => 'トイレ（有料）',
            self::ToiletFree => 'トイレ（無料）',
            self::NursingRoom => '授乳室',
            self::FirstAid => '救護所',
            self::Headquarters => '本陣',
            self::StaffWaitingArea => 'スタッフ待機場所',
        };
    }

    /**
     * 絵文字アイコンを取得
     */
    public function icon(): string
    {
        return match ($this) {
            self::WaterSupply => '🚰',
            self::ToiletPaid, self::ToiletFree => '🚻',
            self::NursingRoom => '🍼',
            self::FirstAid => '🏥',
            self::Headquarters => '🏛️',
            self::StaffWaitingArea => '👥',
        };
    }

    /**
     * バッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::WaterSupply => 'bg-sky-50 text-sky-800 border-sky-300 font-bold',
            self::ToiletPaid => 'bg-amber-50 text-amber-800 border-amber-300',
            self::ToiletFree => 'bg-emerald-50 text-emerald-800 border-emerald-300',
            self::NursingRoom => 'bg-pink-50 text-pink-800 border-pink-300',
            self::FirstAid => 'bg-rose-50 text-rose-800 border-rose-300 font-bold',
            self::Headquarters => 'bg-indigo-50 text-indigo-800 border-indigo-300 font-bold',
            self::StaffWaitingArea => 'bg-slate-100 text-slate-800 border-slate-300',
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
