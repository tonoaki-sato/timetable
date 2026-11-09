<?php

namespace App\Models;

use App\DTOs\LocationStatusEvaluation;
use App\Services\LocationAvailabilityService;
use Carbon\Carbon;
use Database\Factories\LocationFactory;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'timetable_id',
    'location_type_id',
    'name',
    'notes',
    'is_all_day',
    'display_order',
    'is_active',
    'created_by',
])]
class Location extends Model
{
    /** @use HasFactory<LocationFactory> */
    use HasFactory;

    /**
     * デフォルト拠点定義一覧（運行計画作成時の自動生成用）
     *
     * @var array<int, array{name: string, type_slug: string, is_all_day: bool, notes: string, display_order: int}>
     */
    public const array DEFAULT_LOCATIONS = [
        [
            'name' => '番所地下倉庫',
            'type_slug' => 'storage',
            'is_all_day' => false,
            'notes' => '大型自前機材（カラーコーン、ござ市用テント、大型台車等）の常時保管場所。',
            'display_order' => 10,
        ],
        [
            'name' => '番所倉庫',
            'type_slug' => 'storage',
            'is_all_day' => false,
            'notes' => '地下倉庫とは別の番所倉庫。交通整理用LED誘導棒などの常時保管場所。',
            'display_order' => 15,
        ],
        [
            'name' => '番所前スペース',
            'type_slug' => 'open_space',
            'is_all_day' => false,
            'notes' => "番所空きスペース。前日の倉庫出し・業者納品時の一時集約場所、および2日目終了後〜翌日の倉庫格納前・業者回収待ち集約場所。\n※1日目・2日目の通行止め（まつり開催）期間中はイベントブースとして使用されるため資材置き場として利用不可。",
            'display_order' => 20,
        ],
        [
            'name' => 'コミュニティルーム',
            'type_slug' => 'community_space',
            'is_all_day' => false,
            'notes' => "主に消耗品および看板（ステカン）の常時保管場所。\n本祭期間中の盗難のおそれがある資材や荒天時退避資材の夜間保管先としても活用。",
            'display_order' => 30,
        ],
        [
            'name' => '税務署前駐車場',
            'type_slug' => 'parking',
            'is_all_day' => false,
            'notes' => 'イベントブース。機材の前日中継集約および1日目夜の分散夜間保管場所として利用。',
            'display_order' => 40,
        ],
        [
            'name' => '長島駐車場（旧北川駐車場）',
            'type_slug' => 'parking',
            'is_all_day' => false,
            'notes' => '近隣貸切駐車場。企業ブース、および1日目夜の夜間分散保管場所として利用。',
            'display_order' => 50,
        ],
        [
            'name' => 'S&S駐車場',
            'type_slug' => 'parking',
            'is_all_day' => false,
            'notes' => '本陣待機場所。機材の前日中継集約および1日目夜の分散夜間保管場所として利用。',
            'display_order' => 60,
        ],
        [
            'name' => '横浜銀行駐車場',
            'type_slug' => 'parking',
            'is_all_day' => false,
            'notes' => '近隣住民用の代替駐車場として利用。',
            'display_order' => 70,
        ],
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_all_day' => 'boolean',
            'is_active' => 'boolean',
            'display_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 運行計画（タイムテーブル）とのリレーション
     */
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    /**
     * 拠点タイプ
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(LocationType::class, 'location_type_id');
    }

    /**
     * 拠点利用可能時間帯スロット一覧
     */
    public function schedules(): HasMany
    {
        return $this->hasMany(LocationSchedule::class)->orderBy('start_at', 'asc');
    }

    /**
     * 登録者ユーザー
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * この拠点へ搬入・保管される機材物流計画
     */
    public function plansTo(): HasMany
    {
        return $this->hasMany(EquipmentLogisticsPlan::class, 'to_location_id');
    }

    /**
     * この拠点から搬出される機材物流計画
     */
    public function plansFrom(): HasMany
    {
        return $this->hasMany(EquipmentLogisticsPlan::class, 'from_location_id');
    }

    /**
     * 種別ラベルのアクセサ
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type?->label ?? $this->type?->name ?? '';
    }

    /**
     * 説明・備考アクセサ（notesのエイリアス）
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->notes;
    }

    /**
     * 利用可能開始日時のアクセサ（登録スケジュールの最早上限）
     */
    public function getAvailableFromAttribute(): ?Carbon
    {
        $min = $this->relationLoaded('schedules')
            ? $this->schedules->min('start_at')
            : $this->schedules()->min('start_at');

        return $min ? Carbon::parse($min) : null;
    }

    /**
     * 利用可能終了日時のアクセサ（登録スケジュールの最遅上限）
     */
    public function getAvailableToAttribute(): ?Carbon
    {
        $max = $this->relationLoaded('schedules')
            ? $this->schedules->max('end_at')
            : $this->schedules()->max('end_at');

        return $max ? Carbon::parse($max) : null;
    }

    /**
     * 指定日時にこの拠点・駐車場が利用（貸切）可能か判定
     */
    public function isAvailableAt(?DateTimeInterface $dateTime = null): bool
    {
        if (! $this->is_active) {
            return false;
        }

        if ($this->is_all_day) {
            return true;
        }

        $dateTime = $dateTime
            ? Carbon::parse($dateTime)->timezone('Asia/Tokyo')
            : Carbon::now('Asia/Tokyo');

        $schedules = $this->relationLoaded('schedules')
            ? $this->schedules
            : $this->schedules()->get();

        if ($schedules->isEmpty()) {
            return false;
        }

        return $schedules->contains(function (LocationSchedule $schedule) use ($dateTime) {
            $inSlot = $schedule->start_at <= $dateTime && $dateTime <= $schedule->end_at;

            return $inSlot && $schedule->isAvailable();
        });
    }

    /**
     * 有効拠点のみ抽出
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 特定の運行計画に属する拠点を抽出
     */
    public function scopeForTimetable(Builder $query, Timetable|int $timetable): Builder
    {
        $id = $timetable instanceof Timetable ? $timetable->id : $timetable;

        return $query->where('timetable_id', $id);
    }

    /**
     * 表示順ソート
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 指定された日時（デフォルト: 現在時刻 Asia/Tokyo）における状態を評価
     */
    public function evaluateStatusAt(?DateTimeInterface $dateTime = null): LocationStatusEvaluation
    {
        return app(LocationAvailabilityService::class)->evaluateLocationStatus($this, $dateTime);
    }
}
