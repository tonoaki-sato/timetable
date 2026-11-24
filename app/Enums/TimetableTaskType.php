<?php

namespace App\Enums;

enum TimetableTaskType: string
{
    case Routine = 'routine';
    case Spot = 'spot';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Routine => '定常タスク',
            self::Spot => 'スポットタスク',
        };
    }

    /**
     * 短縮ラベルを取得
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Routine => '定常',
            self::Spot => 'スポット',
        };
    }

    /**
     * アイコンを取得
     */
    public function icon(): string
    {
        return match ($this) {
            self::Routine => '🔄',
            self::Spot => '⚡',
        };
    }

    /**
     * 種別バッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Routine => 'bg-teal-50 text-teal-800 border-teal-300',
            self::Spot => 'bg-amber-50 text-amber-800 border-amber-300',
        };
    }

    /**
     * 指定の別タスク種別と時間帯の重複を許容するか判定（定常×スポットのみ許容）
     */
    public function allowsOverlapWith(self $other): bool
    {
        return $this !== $other;
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
