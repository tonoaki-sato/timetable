<?php

namespace App\Enums;

enum EquipmentDayPhase: string
{
    case Other = 'other';
    case PreDay = 'pre_day';
    case Day1Morning = 'day1_morning';
    case Day1Evening = 'day1_evening';
    case Day2Morning = 'day2_morning';
    case Day2Evening = 'day2_evening';
    case PostDay = 'post_day';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Other => 'その他（事前準備等）',
            self::PreDay => '前日（準備日）',
            self::Day1Morning => '1日目 朝（設置）',
            self::Day1Evening => '1日目 夜（夜間保管移動）',
            self::Day2Morning => '2日目 朝（設置）',
            self::Day2Evening => '2日目 夜（倉庫格納・回収待ち集約）',
            self::PostDay => '翌日（業者回収）',
        };
    }

    /**
     * 略称ラベルを取得
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Other => '事前準備等',
            self::PreDay => '前日準備',
            self::Day1Morning => '1日目朝 設置',
            self::Day1Evening => '1日目夜 保管',
            self::Day2Morning => '2日目朝 設置',
            self::Day2Evening => '2日目夜 格納',
            self::PostDay => '翌日回収',
        };
    }

    /**
     * バッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Other => 'bg-slate-50 text-slate-700 border border-slate-200',
            self::PreDay => 'bg-amber-50 text-amber-800 border border-amber-200',
            self::Day1Morning, self::Day2Morning => 'bg-indigo-50 text-indigo-700 border border-indigo-200',
            self::Day1Evening, self::Day2Evening => 'bg-purple-50 text-purple-700 border border-purple-200',
            self::PostDay => 'bg-teal-50 text-teal-700 border border-teal-200',
        };
    }

    /**
     * セレクトボックス等の選択肢一覧を取得
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

