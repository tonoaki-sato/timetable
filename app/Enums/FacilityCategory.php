<?php

namespace App\Enums;

enum FacilityCategory: string
{
    case WaterSupply = 'water_supply';
    case ToiletAndNursing = 'toilet_nursing';
    case Other = 'other';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::WaterSupply => '給水設備',
            self::ToiletAndNursing => 'トイレ・授乳室',
            self::Other => 'その他',
        };
    }

    /**
     * 絵文字アイコンを取得
     */
    public function icon(): string
    {
        return match ($this) {
            self::WaterSupply => '🚰',
            self::ToiletAndNursing => '🚻',
            self::Other => '🏷️',
        };
    }
}
