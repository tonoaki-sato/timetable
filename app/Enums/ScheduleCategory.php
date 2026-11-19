<?php

namespace App\Enums;

enum ScheduleCategory: string
{
    case Committee = 'committee';
    case Permit = 'permit';
    case Briefing = 'briefing';
    case Operation = 'operation';
    case Other = 'other';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Committee => '会議・打ち合わせ',
            self::Permit => '申請・協議',
            self::Briefing => '募集・説明会',
            self::Operation => '運行・設営',
            self::Other => 'その他',
        };
    }

    /**
     * 絵文字アイコンを取得
     */
    public function icon(): string
    {
        return match ($this) {
            self::Committee => '🤝',
            self::Permit => '📑',
            self::Briefing => '📢',
            self::Operation => '🎪',
            self::Other => '📌',
        };
    }

    /**
     * バッジ用CSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Committee => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::Permit => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Briefing => 'bg-sky-50 text-sky-700 border-sky-200',
            self::Operation => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            self::Other => 'bg-slate-100 text-slate-700 border-slate-200',
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
