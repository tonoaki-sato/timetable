<?php

namespace App\Models;

use App\Presenters\LanePresenter;
use Database\Factories\LaneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

#[Fillable([
    'name',
    'color',
    'icon',
    'description',
    'display_order',
    'is_active',
])]
class Lane extends Model
{
    /** @use HasFactory<LaneFactory> */
    use HasFactory;

    protected ?LanePresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): LanePresenter
    {
        return $this->presenterInstance ??= new LanePresenter($this);
    }

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

    /**
     * 指定カラーキーのパレット定義を取得
     *
     * @return array{btn: string, dot: string}
     */
    public static function paletteFor(string $colorKey): array
    {
        return self::COLOR_PALETTE_MAP[$colorKey] ?? self::COLOR_PALETTE_MAP['slate'];
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 有効な担当班マスターのみ抽出するスコープ
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 表示順序でソートするスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 運行日別担当班編成一覧とのリレーション
     *
     * @return HasMany<TimetableLane, $this>
     */
    public function timetableLanes(): HasMany
    {
        return $this->hasMany(TimetableLane::class, 'lane_id');
    }

    /**
     * この担当班を編成している運行計画一覧との多対多リレーション
     *
     * @return BelongsToMany<Timetable, $this>
     */
    public function timetables(): BelongsToMany
    {
        return $this->belongsToMany(Timetable::class, 'timetable_lanes')
            ->withPivot(['id', 'lead_staff_id', 'display_order', 'notes'])
            ->withTimestamps();
    }

    /**
     * 日別編成経由で紐づく全作業タスク一覧とのリレーション
     *
     * @return HasManyThrough<TimetableTask, TimetableLane, $this>
     */
    public function tasks(): HasManyThrough
    {
        return $this->hasManyThrough(TimetableTask::class, TimetableLane::class, 'lane_id', 'timetable_lane_id');
    }

    /**
     * カラーバッジ表示用のCSSクラス配列
     *
     * @return array{badge: string, border: string, bg: string, text: string}
     */
    public function getColorClassesAttribute(): array
    {
        return $this->presenter()->colorClasses();
    }

    /**
     * アイコン表示用アクセサ（未設定時は '📋'）
     */
    public function getIconLabelAttribute(): string
    {
        return $this->icon ?: '📋';
    }

    /**
     * 進行表マトリクス・PDF用のアイコン表示アクセサ（未設定時は '◆'）
     */
    public function getMatrixIconLabelAttribute(): string
    {
        return $this->icon ?: '◆';
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
        $timetablesCollection = $this->relationLoaded('timetableLanes')
            ? $this->timetableLanes->pluck('timetable')->filter()->unique('id')->sortBy('target_date')
            : $this->timetables()->orderBy('target_date')->get();

        $timetablesPayload = $timetablesCollection
            ->map(function (Timetable $timetable): array {
                $targetDate = $timetable->target_date?->format('Y-m-d');
                $targetDateLabel = $timetable->target_date
                    ? $timetable->target_date->format('Y/m/d')."（{$timetable->weekday_label}）"
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
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'color' => (string) $this->color,
            'color_label' => self::COLOR_OPTIONS[$this->color] ?? (string) $this->color,
            'color_classes' => $this->color_classes,
            'icon' => $this->icon,
            'icon_label' => $this->icon_label,
            'description' => $this->description,
            'display_order' => (int) $this->display_order,
            'is_active' => (bool) $this->is_active,
            'timetable_lanes_count' => (int) ($this->timetable_lanes_count ?? $this->timetableLanes()->count()),
            'tasks_count' => (int) ($this->tasks_count ?? $this->tasks()->count()),
            'timetables' => $timetablesPayload,
            'update_url' => route('lanes.update', $this),
            'destroy_url' => route('lanes.destroy', $this),
        ];
    }
}
