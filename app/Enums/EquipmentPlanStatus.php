<?php

namespace App\Enums;

enum EquipmentPlanStatus: string
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
            self::Planned => '計画中',
            self::InProgress => '進行中',
            self::Completed => '完了',
            self::Cancelled => '中止',
        };
    }

    /**
     * バッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Planned => 'bg-slate-100 text-slate-700 border border-slate-200',
            self::InProgress => 'bg-amber-50 text-amber-800 border border-amber-200 animate-pulse',
            self::Completed => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
            self::Cancelled => 'bg-rose-50 text-rose-700 border border-rose-200',
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

