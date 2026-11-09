<?php

namespace App\Enums;

enum EquipmentType: string
{
    case Committee = 'committee';
    case Rental = 'rental';
    case Personal = 'personal';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Committee => '実行委員会管理（自前保有）',
            self::Rental => 'イベント会社提携レンタル',
            self::Personal => '私物（個人持ち込み）',
        };
    }

    /**
     * 略称ラベルを取得
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Committee => '自前保有',
            self::Rental => '提携レンタル',
            self::Personal => '私物',
        };
    }

    /**
     * アイコンを取得
     */
    public function icon(): string
    {
        return match ($this) {
            self::Committee => '🏛️',
            self::Rental => '🏢',
            self::Personal => '🎒',
        };
    }

    /**
     * バッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Committee => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
            self::Rental => 'bg-sky-50 text-sky-800 border border-sky-200',
            self::Personal => 'bg-amber-50 text-amber-800 border border-amber-200',
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

