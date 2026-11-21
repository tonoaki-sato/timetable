<?php

namespace App\Models;

use App\Enums\EquipmentCheckoutRule;
use App\Enums\EquipmentType;
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
     * 区分ラベルアクセサ
     */
    public function getTypeLabelAttribute(): string
    {
        return $this->type_enum->label();
    }

    /**
     * 持出ルールラベルアクセサ
     */
    public function getCheckoutRuleLabelAttribute(): string
    {
        return $this->checkout_rule_enum->label();
    }

    /**
     * フォーマット済み数量表示アクセサ（例: "約 60 本" または "5 台"）
     */
    public function getFormattedQuantityAttribute(): string
    {
        $prefix = $this->is_approximate_quantity ? '約 ' : '';

        return $prefix.number_format((int) $this->quantity).' '.$this->unit;
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
     * 定常保管場所の拠点名アクセサ
     */
    public function getStorageLocationNameAttribute(): ?string
    {
        return $this->storageLocation?->name;
    }

    /**
     * 画面表示用の解決済み保管・調達ラベル
     */
    public function getResolvedStorageLocationLabelAttribute(): string
    {
        if ($this->isRental()) {
            return 'イベント会社手配';
        }

        if ($this->storageLocation !== null) {
            return $this->storageLocation->name;
        }

        if ($this->isPersonal()) {
            return '個人保管（持ち込み）';
        }

        return '未指定';
    }

    /**
     * モーダル表示・編集用のJSONペイロード配列を取得
     *
     * @return array<string, mixed>
     */
    public function toModalPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'type_label' => $this->type_enum->label(),
            'type_short_label' => $this->type_enum->shortLabel(),
            'type_icon' => $this->type_enum->icon(),
            'type_badge_classes' => $this->type_enum->badgeClasses(),
            'checkout_rule' => $this->checkout_rule,
            'checkout_rule_label' => $this->checkout_rule_enum->label(),
            'checkout_rule_short_label' => $this->checkout_rule_enum->shortLabel(),
            'checkout_rule_icon' => $this->checkout_rule_enum->icon() ?? '',
            'checkout_rule_badge_classes' => $this->checkout_rule_enum->badgeClasses(),
            'quantity' => (int) $this->quantity,
            'is_approximate_quantity' => (bool) $this->is_approximate_quantity,
            'unit' => $this->unit,
            'formatted_quantity' => $this->formatted_quantity,
            'storage_location_id' => $this->storage_location_id,
            'storage_location_name' => $this->storage_location_name ?? '',
            'storage_location_is_active' => $this->storageLocation ? (bool) $this->storageLocation->is_active : true,
            'resolved_storage_location_label' => $this->resolved_storage_location_label,
            'description' => $this->description ?? '',
            'update_url' => route('equipments.update', $this),
        ];
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
