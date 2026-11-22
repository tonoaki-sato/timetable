<?php

namespace App\Presenters;

use App\Models\Lane;
use App\Models\Timetable;

class LanePresenter
{
    /**
     * テーマカラーの選択肢定義
     *
     * @var array<string, string>
     */
    public const array COLOR_OPTIONS = [
        'amber' => 'アンバー（橙）',
        'sky' => 'スカイ（空）',
        'emerald' => 'エメラルド（緑）',
        'purple' => 'パープル（紫）',
        'pink' => 'ピンク（桃）',
        'red' => 'レッド（赤）',
        'orange' => 'オレンジ（朱）',
        'indigo' => 'インディゴ（藍）',
        'teal' => 'ティール（青緑）',
        'yellow' => 'イエロー（黄）',
        'slate' => 'スレート（灰）',
    ];

    /**
     * カラーパレット選択肢のボタンスタイル・ドット色・バッジ色定義
     *
     * @var array<string, array{btn: string, dot: string, badge: string}>
     */
    public const array COLOR_PALETTE_MAP = [
        'amber' => ['btn' => 'border-amber-300 bg-amber-50 text-amber-800', 'dot' => 'bg-amber-500', 'badge' => 'bg-amber-100 text-amber-800 border-amber-300'],
        'sky' => ['btn' => 'border-sky-300 bg-sky-50 text-sky-800', 'dot' => 'bg-sky-500', 'badge' => 'bg-sky-100 text-sky-800 border-sky-300'],
        'emerald' => ['btn' => 'border-emerald-300 bg-emerald-50 text-emerald-800', 'dot' => 'bg-emerald-500', 'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
        'purple' => ['btn' => 'border-purple-300 bg-purple-50 text-purple-800', 'dot' => 'bg-purple-500', 'badge' => 'bg-purple-100 text-purple-800 border-purple-300'],
        'pink' => ['btn' => 'border-pink-300 bg-pink-50 text-pink-800', 'dot' => 'bg-pink-500', 'badge' => 'bg-pink-100 text-pink-800 border-pink-300'],
        'red' => ['btn' => 'border-rose-300 bg-rose-50 text-rose-800', 'dot' => 'bg-rose-500', 'badge' => 'bg-rose-100 text-rose-800 border-rose-300'],
        'orange' => ['btn' => 'border-orange-300 bg-orange-50 text-orange-800', 'dot' => 'bg-orange-500', 'badge' => 'bg-orange-100 text-orange-800 border-orange-300'],
        'indigo' => ['btn' => 'border-indigo-300 bg-indigo-50 text-indigo-800', 'dot' => 'bg-indigo-500', 'badge' => 'bg-indigo-100 text-indigo-800 border-indigo-300'],
        'teal' => ['btn' => 'border-teal-300 bg-teal-50 text-teal-800', 'dot' => 'bg-teal-500', 'badge' => 'bg-teal-100 text-teal-800 border-teal-300'],
        'yellow' => ['btn' => 'border-yellow-300 bg-yellow-50 text-yellow-800', 'dot' => 'bg-yellow-400', 'badge' => 'bg-yellow-100 text-yellow-800 border-yellow-300'],
        'slate' => ['btn' => 'border-slate-300 bg-slate-50 text-slate-800', 'dot' => 'bg-slate-500', 'badge' => 'bg-slate-100 text-slate-800 border-slate-300'],
    ];

    /**
     * アイコン絵文字プリセット一覧
     *
     * @var array<int, string>
     */
    public const array EMOJI_PRESETS = ['🔺', '🚰', '🚻', '🚨', '🎈', '🏮', '🧺', '🏛️', '🧹', '🪧', '🚚', '📢', '🎪', '🛡️', '⚡', '📦', '🚑', '🅿️'];

    public function __construct(
        protected Lane $lane
    ) {}

    /**
     * 指定カラーキーのパレット定義を取得
     *
     * @return array{btn: string, dot: string, badge: string}
     */
    public static function paletteFor(string $colorKey): array
    {
        return self::COLOR_PALETTE_MAP[$colorKey] ?? self::COLOR_PALETTE_MAP['slate'];
    }

    /**
     * スタッフタイムライン用ガントブロックの配色CSSクラスを取得
     */
    public static function timelineBadgeClassFor(string $color): string
    {
        return match ($color) {
            'amber' => 'bg-amber-100 border-amber-300 text-amber-900',
            'sky' => 'bg-sky-100 border-sky-300 text-sky-900',
            'emerald' => 'bg-emerald-100 border-emerald-300 text-emerald-900',
            'purple' => 'bg-purple-100 border-purple-400/30 text-purple-900',
            'pink' => 'bg-pink-100 border-pink-300 text-pink-900',
            'red' => 'bg-rose-100 border-rose-300 text-rose-900',
            'orange' => 'bg-orange-100 border-orange-300 text-orange-900',
            'teal' => 'bg-teal-100 border-teal-300 text-teal-900',
            default => 'bg-indigo-100 border-indigo-300 text-indigo-900',
        };
    }

    /**
     * カラーバッジ表示用のCSSクラス配列を取得
     *
     * @return array{badge: string, border: string, bg: string, text: string}
     */
    public function colorClasses(): array
    {
        return self::colorClassesFor((string) $this->lane->color);
    }

    /**
     * アイコン表示用文字列を取得（未設定時は '📋'）
     */
    public function iconLabel(): string
    {
        return $this->lane->icon ?: '📋';
    }

    /**
     * 進行表マトリクス・PDF用のアイコン表示文字列を取得（未設定時は '◆'）
     */
    public function matrixIconLabel(): string
    {
        return $this->lane->icon ?: '◆';
    }

    /**
     * 詳細・編集モーダル用ペイロード配列を生成
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     color: string,
     *     color_label: string,
     *     color_classes: array{badge: string, border: string, bg: string, text: string},
     *     icon: ?string,
     *     icon_label: string,
     *     description: ?string,
     *     display_order: int,
     *     is_active: bool,
     *     timetable_lanes_count: int,
     *     tasks_count: int,
     *     timetables: list<array{id: int, name: string, target_date: ?string, target_date_label: string}>,
     *     update_url: string,
     *     destroy_url: string
     * }
     */
    public function toModalPayload(): array
    {
        $timetablesCollection = $this->lane->relationLoaded('timetableLanes')
            ? $this->lane->timetableLanes->pluck('timetable')->filter()->unique('id')->sortBy('target_date')
            : $this->lane->timetables()->orderBy('target_date')->get();

        $timetablesPayload = $timetablesCollection
            ->map(function (Timetable $timetable): array {
                $targetDate = $timetable->target_date?->format('Y-m-d');
                $weekday = $timetable->presenter()->weekdayLabel();
                $targetDateLabel = $timetable->target_date
                    ? $timetable->target_date->format('Y/m/d')."（{$weekday}）"
                    : '';

                return [
                    'id' => (int) $timetable->id,
                    'name' => (string) $timetable->name,
                    'target_date' => $targetDate,
                    'target_date_label' => $targetDateLabel,
                ];
            })
            ->values()
            ->all();

        return [
            'id' => (int) $this->lane->id,
            'name' => (string) $this->lane->name,
            'color' => (string) $this->lane->color,
            'color_label' => self::COLOR_OPTIONS[$this->lane->color] ?? (string) $this->lane->color,
            'color_classes' => $this->colorClasses(),
            'icon' => $this->lane->icon,
            'icon_label' => $this->iconLabel(),
            'description' => $this->lane->description,
            'display_order' => (int) $this->lane->display_order,
            'is_active' => (bool) $this->lane->is_active,
            'timetable_lanes_count' => (int) ($this->lane->timetable_lanes_count ?? $this->lane->timetableLanes()->count()),
            'tasks_count' => (int) ($this->lane->tasks_count ?? $this->lane->tasks()->count()),
            'timetables' => $timetablesPayload,
            'update_url' => route('lanes.update', $this->lane),
            'destroy_url' => route('lanes.destroy', $this->lane),
        ];
    }

    /**
     * 指定カラーキーのCSSクラス配列を取得
     *
     * @return array{badge: string, border: string, bg: string, text: string}
     */
    public static function colorClassesFor(string $color): array
    {
        return match ($color) {
            'amber' => [
                'badge' => 'bg-amber-100 text-amber-800 border-amber-300',
                'border' => 'border-amber-300',
                'bg' => 'bg-amber-50',
                'text' => 'text-amber-800',
            ],
            'sky' => [
                'badge' => 'bg-sky-100 text-sky-800 border-sky-300',
                'border' => 'border-sky-300',
                'bg' => 'bg-sky-50',
                'text' => 'text-sky-800',
            ],
            'emerald' => [
                'badge' => 'bg-emerald-100 text-emerald-800 border-emerald-300',
                'border' => 'border-emerald-300',
                'bg' => 'bg-emerald-50',
                'text' => 'text-emerald-800',
            ],
            'purple' => [
                'badge' => 'bg-purple-100 text-purple-800 border-purple-300',
                'border' => 'border-purple-300',
                'bg' => 'bg-purple-50',
                'text' => 'text-purple-800',
            ],
            'pink' => [
                'badge' => 'bg-pink-100 text-pink-800 border-pink-300',
                'border' => 'border-pink-300',
                'bg' => 'bg-pink-50',
                'text' => 'text-pink-800',
            ],
            'red' => [
                'badge' => 'bg-rose-100 text-rose-800 border-rose-300',
                'border' => 'border-rose-300',
                'bg' => 'bg-rose-50',
                'text' => 'text-rose-800',
            ],
            'orange' => [
                'badge' => 'bg-orange-100 text-orange-800 border-orange-300',
                'border' => 'border-orange-300',
                'bg' => 'bg-orange-50',
                'text' => 'text-orange-800',
            ],
            'indigo' => [
                'badge' => 'bg-indigo-100 text-indigo-800 border-indigo-300',
                'border' => 'border-indigo-300',
                'bg' => 'bg-indigo-50',
                'text' => 'text-indigo-800',
            ],
            'teal' => [
                'badge' => 'bg-teal-100 text-teal-800 border-teal-300',
                'border' => 'border-teal-300',
                'bg' => 'bg-teal-50',
                'text' => 'text-teal-800',
            ],
            'yellow' => [
                'badge' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                'border' => 'border-yellow-500',
                'bg' => 'bg-yellow-50',
                'text' => 'text-yellow-800',
            ],
            default => [
                'badge' => 'bg-slate-100 text-slate-800 border-slate-300',
                'border' => 'border-slate-300',
                'bg' => 'bg-slate-50',
                'text' => 'text-slate-800',
            ],
        };
    }
}
