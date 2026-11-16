<?php

namespace App\Enums;

enum BoothType: string
{
    case Tent = 'tent';
    case PipeTent = 'pipe_tent';
    case StreetStall = 'street_stall';
    case KitchenCar = 'kitchen_car';

    /**
     * UI表示用日本語ラベル
     */
    public function label(): string
    {
        return match ($this) {
            self::Tent => 'テント',
            self::PipeTent => 'パイプテント',
            self::StreetStall => '露店',
            self::KitchenCar => 'キッチンカー',
        };
    }
}
