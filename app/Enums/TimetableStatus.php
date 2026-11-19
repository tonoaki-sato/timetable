<?php

namespace App\Enums;

enum TimetableStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => '準備中',
            self::Active => '運行中',
            self::Completed => '運行完了',
        };
    }

    /**
     * ステータスバッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Active => 'bg-emerald-50 text-emerald-700 border-emerald-200 font-bold',
            self::Completed => 'bg-slate-100 text-slate-600 border-slate-200',
            self::Draft => 'bg-blue-50 text-blue-700 border-blue-200',
        };
    }

    /**
     * 選択肢配列（value => label）を取得
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

    /**
     * 全値配列を取得
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(static fn (self $case): string => $case->value, self::cases());
    }
}
