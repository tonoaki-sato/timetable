<?php

namespace App\Enums;

enum ConeCalloutColor: string
{
    case Amber = 'amber';
    case Red = 'red';
    case Blue = 'blue';
    case Slate = 'slate';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Amber => '注意 (黄)',
            self::Red => '重要 (赤)',
            self::Blue => '案内 (青)',
            self::Slate => 'メモ (灰)',
        };
    }

    /**
     * 枠線 Hex
     */
    public function borderHex(): string
    {
        return match ($this) {
            self::Amber => '#d97706',
            self::Red => '#dc2626',
            self::Blue => '#2563eb',
            self::Slate => '#475569',
        };
    }

    /**
     * 背景 Hex
     */
    public function bgHex(): string
    {
        return match ($this) {
            self::Amber => '#fffbeb',
            self::Red => '#fef2f2',
            self::Blue => '#eff6ff',
            self::Slate => '#f8fafc',
        };
    }

    /**
     * 文字 Hex
     */
    public function textHex(): string
    {
        return match ($this) {
            self::Amber => '#78350f',
            self::Red => '#7f1d1d',
            self::Blue => '#1e3a8a',
            self::Slate => '#0f172a',
        };
    }

    /**
     * 枠線 CPDF RGB
     *
     * @return array{float, float, float}
     */
    public function borderRgb(): array
    {
        return match ($this) {
            self::Amber => [0.851, 0.467, 0.024],
            self::Red => [0.863, 0.149, 0.149],
            self::Blue => [0.145, 0.388, 0.922],
            self::Slate => [0.278, 0.333, 0.412],
        };
    }

    /**
     * 背景 CPDF RGB
     *
     * @return array{float, float, float}
     */
    public function bgRgb(): array
    {
        return match ($this) {
            self::Amber => [1.0, 0.984, 0.922],
            self::Red => [0.996, 0.949, 0.949],
            self::Blue => [0.937, 0.965, 1.0],
            self::Slate => [0.973, 0.980, 0.988],
        };
    }

    /**
     * 文字 CPDF RGB
     *
     * @return array{float, float, float}
     */
    public function textRgb(): array
    {
        return match ($this) {
            self::Amber => [0.471, 0.208, 0.059],
            self::Red => [0.498, 0.114, 0.114],
            self::Blue => [0.118, 0.227, 0.541],
            self::Slate => [0.059, 0.090, 0.165],
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
