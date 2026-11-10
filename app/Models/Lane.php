<?php

namespace App\Models;

use Database\Factories\LaneFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'timetable_id',
    'name',
    'color',
    'icon',
    'lead_staff_id',
    'description',
    'display_order',
])]
class Lane extends Model
{
    /** @use HasFactory<LaneFactory> */
    use HasFactory;

    /**
     * デフォルト10レーンの初期定義一覧
     *
     * @var array<int, array{name: string, icon: string, color: string, description: string, display_order: int}>
     */
    public const array DEFAULT_LANES = [
        ['name' => 'カラーコーン', 'icon' => '🔺', 'color' => 'amber', 'description' => '路上規制バー・コーン搬入・本設置・撤去', 'display_order' => 10],
        ['name' => '給水設備', 'icon' => '🚰', 'color' => 'sky', 'description' => '給水タンク・仮設手洗い場・配管ホース設置', 'display_order' => 20],
        ['name' => '衛生', 'icon' => '🚻', 'color' => 'emerald', 'description' => '仮設トイレ・ごみステーション設置・回収袋交換', 'display_order' => 30],
        ['name' => '巡回', 'icon' => '🚨', 'color' => 'purple', 'description' => '会場警備・迷子対応・防犯パトロール・動線監視', 'display_order' => 40],
        ['name' => 'キッズ村', 'icon' => '🎈', 'color' => 'pink', 'description' => 'こども体験ブース設営・運営・安全管理', 'display_order' => 50],
        ['name' => 'ちょうちん', 'icon' => '🏮', 'color' => 'red', 'description' => '提灯取付・配線点検・点灯確認・翌日回収', 'display_order' => 60],
        ['name' => 'ござ市', 'icon' => '🧺', 'color' => 'orange', 'description' => 'ござ敷設・出店区画案内・境界管理・漆原さん連携', 'display_order' => 70],
        ['name' => '本陣', 'icon' => '🏛️', 'color' => 'indigo', 'description' => '本部運営・来賓応接・放送アナウンス・総合案内', 'display_order' => 80],
        ['name' => '道路清掃', 'icon' => '🧹', 'color' => 'teal', 'description' => '開催前清掃・巡回清掃・規制解除前の最終路面清掃', 'display_order' => 90],
        ['name' => 'ステカン', 'icon' => '🪧', 'color' => 'yellow', 'description' => '捨て看板（案内標識）の電柱・フェンス設置および完全回収', 'display_order' => 100],
    ];

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
        ];
    }

    /**
     * 表示順序でソートするスコープ
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order', 'asc');
    }

    /**
     * 親タイムテーブルとのリレーション
     */
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    /**
     * 班長（主担当スタッフ）とのリレーション
     */
    public function leadStaff(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'lead_staff_id');
    }

    /**
     * 固定スタッフ一覧との多対多リレーション
     */
    public function fixedStaff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'lane_fixed_staff')
            ->withTimestamps();
    }

    /**
     * 班長からの要請・機材申請一覧とのリレーション
     */
    public function laneRequests(): HasMany
    {
        return $this->hasMany(LaneRequest::class)->orderByDesc('id');
    }

    /**
     * 所属作業タスク一覧とのリレーション
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(TimetableTask::class)->orderBy('start_time', 'asc');
    }

    /**
     * 班長（主担当）が設定されているか
     */
    public function hasLead(): bool
    {
        return $this->lead_staff_id !== null;
    }

    /**
     * カラーバッジ表示用のCSSクラス配列
     *
     * @return array{badge: string, border: string, bg: string, text: string}
     */
    public function getColorClassesAttribute(): array
    {
        return match ($this->color) {
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
     * 班長の苗字（短縮名）アクセサ
     */
    public function getLeadShortNameAttribute(): string
    {
        return $this->leadStaff
            ? (explode(' ', $this->leadStaff->name)[0] ?? $this->leadStaff->name)
            : '';
    }

    /**
     * PDF進行表ヘッダー用の班長表記（例: '（佐藤さん）'）
     */
    public function getPdfLeadSuffixAttribute(): string
    {
        $leadName = $this->lead_short_name;
        if ($leadName === '') {
            return '';
        }

        return (str_ends_with($leadName, 'さん') || str_ends_with($leadName, '外'))
            ? "（{$leadName}）"
            : "（{$leadName}さん）";
    }

    /**
     * 新規タスク追加時の推奨開始時刻（最遅終了時刻または '08:00'）
     */
    public function getSuggestedStartTimeAttribute(): string
    {
        $latestTask = $this->tasks->sortByDesc('end_time')->first();

        return $latestTask ? substr((string) $latestTask->end_time, 0, 5) : '08:00';
    }

    /**
     * 固定スタッフID配列アクセサ
     *
     * @return array<int, int>
     */
    public function getFixedStaffIdsAttribute(): array
    {
        return $this->fixedStaff->pluck('id')->all();
    }

    /**
     * 編集フォーム表示用の選択済み固定スタッフID配列を取得
     *
     * @param  array<int, int|string>|null  $oldFixedIds
     * @return array<int, int|string>
     */
    public function resolveSelectedFixedIds(?array $oldFixedIds = null): array
    {
        return $oldFixedIds !== null ? $oldFixedIds : $this->fixed_staff_ids;
    }
}
