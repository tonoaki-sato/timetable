<?php

namespace App\Enums;

enum BarricadeType: string
{
    case TypeA = 'type_a';
    case ResinPipe = 'resin_pipe';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::TypeA => 'A型バリケード',
            self::ResinPipe => '樹脂製単管バリケード',
        };
    }

    /**
     * 縦（長辺・幅 cm）
     */
    public function lengthCm(): float
    {
        return 120.0;
    }

    /**
     * 横（開脚奥行 cm）
     */
    public function depthCm(): float
    {
        return match ($this) {
            self::TypeA => 46.0,
            self::ResinPipe => 64.0,
        };
    }

    /**
     * 高さ（cm）
     */
    public function heightCm(): float
    {
        return match ($this) {
            self::TypeA => 80.0,
            self::ResinPipe => 82.0,
        };
    }

    /**
     * 寸法仕様ラベルを取得
     */
    public function specLabel(): string
    {
        return match ($this) {
            self::TypeA => '120×46×H80cm',
            self::ResinPipe => '120×64×H82cm',
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
