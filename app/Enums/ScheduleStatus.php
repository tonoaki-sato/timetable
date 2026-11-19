<?php

namespace App\Enums;

enum ScheduleStatus: string
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Planned => '予定',
            self::InProgress => '進行中',
            self::Completed => '完了',
            self::Cancelled => '中止',
        };
    }

    /**
     * バッジ用CSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Planned => 'bg-blue-50 text-blue-700 border-blue-200',
            self::InProgress => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Completed => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::Cancelled => 'bg-slate-100 text-slate-500 border-slate-200',
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
