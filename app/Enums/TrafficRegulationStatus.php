<?php

namespace App\Enums;

enum TrafficRegulationStatus: string
{
    case PreRegulation = 'pre_regulation';
    case RoadSetup = 'road_setup';
    case MainEvent = 'main_event';
    case PeakTeardown = 'peak_teardown';
    case PostRegulation = 'post_regulation';
    case Inactive = 'inactive';

    /**
     * 上部バッジラベル
     */
    public function badgeLabel(): string
    {
        return match ($this) {
            self::PreRegulation => '⚠️ 通行止め前（一般車通行中）',
            self::RoadSetup => '🚨 道路交通規制中（通行止め）',
            self::MainEvent => '🚨 道路交通規制中（歩行者天国）',
            self::PeakTeardown => '⚠️ 車両通行止め中（撤収ピーク）',
            self::PostRegulation => '✅ 通行止め解除済（車両再開）',
            self::Inactive => 'ℹ️ 道路交通状況',
        };
    }

    /**
     * 大見出し
     */
    public function headline(): string
    {
        return match ($this) {
            self::PreRegulation => '機材仮設置・設営準備中',
            self::RoadSetup => '路上への本設置集中中',
            self::MainEvent => 'まつり本番開催中',
            self::PeakTeardown => '路上資材の撤収ピーク',
            self::PostRegulation => '車両注意・残作業中',
            self::Inactive => '通常交通（規制なし）',
        };
    }

    /**
     * バッジテキストのCSSクラス
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::PreRegulation => 'text-amber-600 font-bold',
            self::RoadSetup => 'text-red-600 font-bold',
            self::MainEvent => 'text-red-600 font-bold',
            self::PeakTeardown => 'text-red-600 font-bold',
            self::PostRegulation => 'text-emerald-700 font-bold',
            self::Inactive => 'text-slate-500 font-medium',
        };
    }

    /**
     * カードの枠線クラス
     */
    public function borderClasses(): string
    {
        return match ($this) {
            self::PreRegulation => 'border-amber-200',
            self::RoadSetup => 'border-red-300',
            self::MainEvent => 'border-red-200',
            self::PeakTeardown => 'border-red-400',
            self::PostRegulation => 'border-emerald-200',
            self::Inactive => 'border-slate-200',
        };
    }

    /**
     * カード右端のアクセントバーのクラス
     */
    public function accentBarClasses(): string
    {
        return match ($this) {
            self::PreRegulation => 'bg-amber-400',
            self::RoadSetup => 'bg-red-500 animate-pulse',
            self::MainEvent => 'bg-red-500',
            self::PeakTeardown => 'bg-red-600 animate-pulse',
            self::PostRegulation => 'bg-emerald-500',
            self::Inactive => 'bg-slate-300',
        };
    }

    /**
     * アイコン文字
     */
    public function icon(): string
    {
        return match ($this) {
            self::PreRegulation => '🚧',
            self::RoadSetup => '🚨',
            self::MainEvent => '⛔',
            self::PeakTeardown => '⚠️',
            self::PostRegulation => '🚗',
            self::Inactive => '🚙',
        };
    }

    /**
     * 車両通行止め（路上利用）が有効か
     */
    public function isRoadClosed(): bool
    {
        return match ($this) {
            self::RoadSetup, self::MainEvent, self::PeakTeardown => true,
            default => false,
        };
    }
}
