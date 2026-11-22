<?php

namespace App\Enums;

enum StaffAttribute: string
{
    case Executive = 'executive';
    case ExecutiveBoard = 'executive_board';
    case General = 'general';
    case External = 'external';

    public const FILTER_EXECUTIVE_GROUP = 'executive_group';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Executive => '幹事',
            self::ExecutiveBoard => '幹事会',
            self::General => '一般',
            self::External => '外部',
        };
    }

    /**
     * 幹事・幹事会グループかどうか
     */
    public function isExecutiveGroup(): bool
    {
        return match ($this) {
            self::Executive, self::ExecutiveBoard => true,
            self::General, self::External => false,
        };
    }

    /**
     * PDF出力時の先頭マークを取得
     */
    public function pdfMark(): string
    {
        return match ($this) {
            self::Executive => '★',
            self::ExecutiveBoard => '☆',
            self::General, self::External => '',
        };
    }

    /**
     * バッジのTailwind CSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Executive => 'bg-amber-50 text-amber-800 border border-amber-300',
            self::ExecutiveBoard => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
            self::General => 'bg-slate-100 text-slate-600 border border-slate-200',
            self::External => 'bg-teal-50 text-teal-700 border border-teal-200',
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
     * 絞り込み用選択肢配列（幹事・幹事会グループ含む）を取得
     *
     * @return array<string, string>
     */
    public static function filterOptions(): array
    {
        return [
            self::FILTER_EXECUTIVE_GROUP => '幹事・幹事会',
            ...self::options(),
        ];
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

    /**
     * 幹事・幹事会グループの値配列を取得
     *
     * @return array<int, string>
     */
    public static function executiveGroupValues(): array
    {
        return [self::Executive->value, self::ExecutiveBoard->value];
    }
}
