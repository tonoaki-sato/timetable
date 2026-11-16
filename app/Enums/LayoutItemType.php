<?php

namespace App\Enums;

enum LayoutItemType: string
{
    case Booth = 'booth';
    case Facility = 'facility';

    /**
     * UI表示用日本語ラベル
     */
    public function label(): string
    {
        return match ($this) {
            self::Booth => '出店ブース',
            self::Facility => '現場設備',
        };
    }

    /**
     * 絵文字アイコン
     */
    public function icon(): string
    {
        return match ($this) {
            self::Booth => '🎪',
            self::Facility => '🚰',
        };
    }

    /**
     * バッジのCSSクラス
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Booth => 'bg-indigo-50 text-indigo-700 border-indigo-200',
            self::Facility => 'bg-cyan-50 text-cyan-800 border-cyan-200',
        };
    }
}
