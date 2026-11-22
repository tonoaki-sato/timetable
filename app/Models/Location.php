<?php

namespace App\Models;

use App\Presenters\LocationPresenter;
use Carbon\Carbon;
use Database\Factories\LocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
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

    protected ?LocationPresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): LocationPresenter
    {
        return $this->presenterInstance ??= new LocationPresenter($this);
    }

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
     * この拠点に紐づく配置エリア一覧とのリレーション
     */
    public function layoutAreas(): HasMany
    {
        return $this->hasMany(LayoutArea::class, 'location_id')->ordered();
    }

    /**
     * この拠点を定常保管場所とする機材・備品一覧
     */
    public function storedEquipments(): HasMany
    {
        return $this->hasMany(Equipment::class, 'storage_location_id')->ordered();
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
     * 有効拠点のみ抽出
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 表示順ソート
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }
}
