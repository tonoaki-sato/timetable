<?php

namespace App\Enums;

enum ConeColor: string
{
    case Red = 'red';
    case Yellow = 'yellow';
    case Blue = 'blue';
    case Green = 'green';
    case Orange = 'orange';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Red => '赤コーン',
            self::Yellow => '黄コーン',
            self::Blue => '青コーン',
            self::Green => '緑コーン',
            self::Orange => '橙コーン',
        };
    }

    /**
     * Hex カラーコードを取得
     */
    public function hex(): string
    {
        return match ($this) {
            self::Red => '#dc2626',
            self::Yellow => '#eab308',
            self::Blue => '#2563eb',
            self::Green => '#16a34a',
            self::Orange => '#f97316',
        };
    }

    /**
     * CPDF 用 RGB (0.0〜1.0) を取得
     *
     * @return array{float, float, float}
     */
    public function rgb(): array
    {
        return match ($this) {
            self::Red => [0.863, 0.149, 0.149],
            self::Yellow => [0.918, 0.702, 0.031],
            self::Blue => [0.145, 0.388, 0.922],
            self::Green => [0.086, 0.639, 0.290],
            self::Orange => [0.976, 0.451, 0.086],
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
