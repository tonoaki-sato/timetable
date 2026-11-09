<?php

namespace App\Enums;

enum EquipmentCheckoutRule: string
{
    case Free = 'free';
    case PermissionRequired = 'permission_required';
    case LeaderOnly = 'leader_only';

    /**
     * 日本語ラベルを取得
     */
    public function label(): string
    {
        return match ($this) {
            self::Free => '自由持出可（使用後要返却）',
            self::PermissionRequired => '本陣許可制（勝手な持出禁止・要記帳）',
            self::LeaderOnly => '班長・特定担当者限定',
        };
    }

    /**
     * 略称ラベルを取得
     */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Free => '自由持出可',
            self::PermissionRequired => '本陣許可制',
            self::LeaderOnly => '班長限定',
        };
    }

    /**
     * バッジのCSSクラスを取得
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Free => 'bg-slate-50 text-slate-600 border border-slate-200',
            self::PermissionRequired => 'bg-rose-50 text-rose-700 border border-rose-200',
            self::LeaderOnly => 'bg-purple-50 text-purple-700 border border-purple-200',
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

