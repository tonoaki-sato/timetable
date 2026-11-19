<?php

namespace App\Enums;

enum LaneRequestStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Resolved = 'resolved';
    case Rejected = 'rejected';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => '要請・申請中',
            self::Approved => '承認・手配済',
            self::Resolved => '対応完了',
            self::Rejected => '見送り・却下',
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
