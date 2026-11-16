<?php

namespace App\Enums;

enum BoothType: string
{
    case Tent = 'tent';
    case PipeTent = 'pipe_tent';
    case OpenStall = 'open_stall';
    case KitchenCar = 'kitchen_car';
    case PartnerBuilding = 'partner_building';

    /**
     * UI表示用日本語ラベル
     */
    public function label(): string
    {
        return match ($this) {
            self::Tent => 'テント',
            self::PipeTent => 'パイプテント',
            self::OpenStall => '露店・露出',
            self::KitchenCar => 'キッチンカー',
            self::PartnerBuilding => '提携建屋内',
        };
    }
}
