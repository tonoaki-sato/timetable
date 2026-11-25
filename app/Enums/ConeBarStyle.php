<?php

namespace App\Enums;

enum ConeBarStyle: string
{
    case YellowBlack = 'yellow_black';
    case RedWhite = 'red_white';
    case BlueWhite = 'blue_white';
    case GreenWhite = 'green_white';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::YellowBlack => '黄黒トラ柄',
            self::RedWhite => '赤白',
            self::BlueWhite => '青白',
            self::GreenWhite => '緑白',
        };
    }

    /**
     * 1色目 Hex
     */
    public function primaryHex(): string
    {
        return match ($this) {
            self::YellowBlack => '#eab308',
            self::RedWhite => '#dc2626',
            self::BlueWhite => '#2563eb',
            self::GreenWhite => '#16a34a',
        };
    }

    /**
     * 2色目 Hex
     */
    public function secondaryHex(): string
    {
        return match ($this) {
            self::YellowBlack => '#0f172a',
            self::RedWhite, self::BlueWhite, self::GreenWhite => '#ffffff',
        };
    }

    /**
     * 1色目 CPDF RGB (0.0〜1.0)
     *
     * @return array{float, float, float}
     */
    public function primaryRgb(): array
    {
        return match ($this) {
            self::YellowBlack => [0.918, 0.702, 0.031],
            self::RedWhite => [0.863, 0.149, 0.149],
            self::BlueWhite => [0.145, 0.388, 0.922],
            self::GreenWhite => [0.086, 0.639, 0.290],
        };
    }

    /**
     * 2色目 CPDF RGB (0.0〜1.0)
     *
     * @return array{float, float, float}
     */
    public function secondaryRgb(): array
    {
        return match ($this) {
            self::YellowBlack => [0.059, 0.090, 0.165],
            self::RedWhite, self::BlueWhite, self::GreenWhite => [1.0, 1.0, 1.0],
        };
    }

    /**
     * 許可値一覧を取得
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }

    /**
     * 選択肢配列を取得
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
