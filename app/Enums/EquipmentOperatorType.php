<?php

namespace App\Enums;

enum EquipmentOperatorType: string
{
    case Committee = 'committee';
    case EventCompany = 'event_company';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Committee => '委員会スタッフ',
            self::EventCompany => 'イベント会社',
        };
    }

    /**
     * バッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Committee => 'bg-emerald-50 text-emerald-800 border border-emerald-200',
            self::EventCompany => 'bg-sky-50 text-sky-800 border border-sky-200',
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

