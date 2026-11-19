<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Assigned = 'assigned';
    case CheckedIn = 'checked_in';
    case InProgress = 'in_progress';
    case Completed = 'completed';
    case Absent = 'absent';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Assigned => '配置済',
            self::CheckedIn => '現場着・受付済',
            self::InProgress => '着手中',
            self::Completed => '完了',
            self::Absent => '欠席・キャンセル',
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
