<?php

namespace App\Enums;

enum AnnualPhase: string
{
    case Preparation = 'preparation';
    case Planning = 'planning';
    case Arrangement = 'arrangement';
    case Execution = 'execution';
    case Followup = 'followup';

    /**
     * 日本語ラベルの取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Preparation => 'フェーズ1【設立準備】',
            self::Planning => 'フェーズ2【計画段階】',
            self::Arrangement => 'フェーズ3【準備段階】',
            self::Execution => 'フェーズ4【実施段階】',
            self::Followup => 'フェーズ5【フォローアップ】',
        };
    }

    /**
     * 該当する月（1〜12月）の配列を取得
     *
     * @return array<int, int>
     */
    public function months(): array
    {
        return match ($this) {
            self::Preparation => [1, 2],
            self::Planning => [3, 4, 5],
            self::Arrangement => [6, 7, 8, 9],
            self::Execution => [10],
            self::Followup => [11, 12],
        };
    }

    /**
     * フェーズバッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Preparation => 'bg-blue-50 text-blue-700 border-blue-200',
            self::Planning => 'bg-cyan-50 text-cyan-700 border-cyan-200',
            self::Arrangement => 'bg-amber-50 text-amber-700 border-amber-200',
            self::Execution => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::Followup => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        };
    }

    /**
     * アイコン文字を取得
     */
    public function icon(): string
    {
        return match ($this) {
            self::Preparation => '📋',
            self::Planning => '📝',
            self::Arrangement => '🤝',
            self::Execution => '🏮',
            self::Followup => '📑',
        };
    }

    /**
     * 月数値（1〜12）から該当フェーズを取得
     */
    public static function fromMonth(int $month): self
    {
        return match (true) {
            $month <= 2 => self::Preparation,
            $month <= 5 => self::Planning,
            $month <= 8 => self::Arrangement,
            $month <= 10 => self::Execution,
            default => self::Followup,
        };
    }
}
