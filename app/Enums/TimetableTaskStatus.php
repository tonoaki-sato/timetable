<?php

namespace App\Enums;

enum TimetableTaskStatus: string
{
    case NotStarted = 'not_started';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::NotStarted => '未着手',
            self::InProgress => '進行中',
            self::Completed => '完了',
            self::Cancelled => '中止',
        };
    }

    /**
     * ステータスバッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Completed => 'bg-emerald-100 text-emerald-800 border-emerald-300',
            self::InProgress => 'bg-indigo-100 text-indigo-800 border-indigo-300',
            self::Cancelled => 'bg-slate-200 text-slate-500 border-slate-300',
            self::NotStarted => 'bg-slate-100 text-slate-700 border-slate-300',
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
