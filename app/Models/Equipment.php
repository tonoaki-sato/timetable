<?php

namespace App\Models;

use App\Enums\EquipmentCheckoutRule;
use App\Enums\EquipmentType;
use App\Presenters\EquipmentPresenter;
use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'type',
    'checkout_rule',
    'quantity',
    'is_approximate_quantity',
    'unit',
    'storage_location_id',
    'description',
    'display_order',
    'created_by',
])]
class Equipment extends Model
{
    /** @use HasFactory<EquipmentFactory> */
    use HasFactory;

    /**
     * 資材テーブル名明示
     *
     * @var string
     */
    protected $table = 'equipments';

    protected ?EquipmentPresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): EquipmentPresenter
    {
        return $this->presenterInstance ??= new EquipmentPresenter($this);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'is_approximate_quantity' => 'boolean',
            'storage_location_id' => 'integer',
            'display_order' => 'integer',
        ];
    }

    /**
     * タイプEnumアクセサ
     */
    public function getTypeEnumAttribute(): EquipmentType
    {
        return EquipmentType::tryFrom((string) $this->type) ?? EquipmentType::Committee;
    }

    /**
     * 持出ルールEnumアクセサ
     */
    public function getCheckoutRuleEnumAttribute(): EquipmentCheckoutRule
    {
        return EquipmentCheckoutRule::tryFrom((string) $this->checkout_rule) ?? EquipmentCheckoutRule::Free;
    }

    /**
     * 区分ミューテータ（Enum または文字列を受け入れ）
     */
    public function setTypeAttribute(EquipmentType|string|null $value): void
    {
        $this->attributes['type'] = $value instanceof EquipmentType ? $value->value : (string) $value;
    }

    /**
     * 持出ルールミューテータ（Enum または文字列を受け入れ）
     */
    public function setCheckoutRuleAttribute(EquipmentCheckoutRule|string|null $value): void
    {
        $this->attributes['checkout_rule'] = $value instanceof EquipmentCheckoutRule ? $value->value : (string) $value;
    }

    /**
     * イベント会社レンタル機材か判定
     */
    public function isRental(): bool
    {
        return $this->type_enum === EquipmentType::Rental;
    }

    /**
     * 私物（個人持ち込み）か判定
     */
    public function isPersonal(): bool
    {
        return $this->type_enum === EquipmentType::Personal;
    }

    /**
     * 搬出入計画ステップ一覧とのリレーション
     */
    public function plans(): HasMany
    {
        return $this->hasMany(EquipmentLogisticsPlan::class, 'equipment_id')->ordered();
    }

    /**
     * 定常保管場所（拠点マスター）とのリレーション
     */
    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'storage_location_id');
    }

    /**
     * 作成者ユーザーとのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 委員会管理機材のみ抽出するスコープ
     */
    public function scopeCommittee(Builder $query): Builder
    {
        return $query->where('type', 'committee');
    }

    /**
     * イベント会社レンタル機材のみ抽出するスコープ
     */
    public function scopeRental(Builder $query): Builder
    {
        return $query->where('type', 'rental');
    }

    /**
     * 私物資材のみ抽出するスコープ
     */
    public function scopePersonal(Builder $query): Builder
    {
        return $query->where('type', 'personal');
    }

    /**
     * 表示順でソートするスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }
}
