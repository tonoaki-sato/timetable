<?php

namespace App\Models;

use Database\Factories\EquipmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'category',
    'checkout_rule',
    'type',
    'quantity',
    'unit',
    'storage_location',
    'storage_detail',
    'event_company_name',
    'event_company_contact',
    'lane_id',
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
     * 資材区分定義一覧
     *
     * @var array<string, string>
     */
    public const array TYPES = [
        'committee' => '実行委員会管理',
        'rental' => 'イベント会社レンタル',
    ];

    /**
     * 資材・備品カテゴリ定義一覧
     *
     * @var array<string, string>
     */
    public const array CATEGORIES = [
        'heavy_equipment' => '大型機材・設備（コーン・テント・給水設備等）',
        'carrier' => '台車・運搬具（台車・ハンドトラック等）',
        'tool_consumable' => '工具・消耗品・備品（誘導棒・ニッパー・テープ等）',
    ];

    /**
     * 持ち出し・利用ルール定義一覧
     *
     * @var array<string, string>
     */
    public const array CHECKOUT_RULES = [
        'free' => '自由持出可（使用後要返却）',
        'permission_required' => '本陣許可制（勝手な持出禁止・要記帳）',
        'leader_only' => '班長・特定担当者限定',
    ];

    /**
     * 自前機材の代表保管場所一覧
     *
     * @var array<string, string>
     */
    public const array STORAGE_LOCATIONS = [
        '番所地下倉庫' => '番所地下倉庫',
        'コミュニティルーム' => 'コミュニティルーム',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'display_order' => 'integer',
        ];
    }

    /**
     * 区分ラベルアクセサ
     */
    public function getTypeLabelAttribute(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    /**
     * カテゴリラベルアクセサ
     */
    public function getCategoryLabelAttribute(): string
    {
        return self::CATEGORIES[$this->category] ?? $this->category;
    }

    /**
     * 持出ルールラベルアクセサ
     */
    public function getCheckoutRuleLabelAttribute(): string
    {
        return self::CHECKOUT_RULES[$this->checkout_rule] ?? $this->checkout_rule;
    }

    /**
     * 台車・運搬具か判定
     */
    public function isCarrier(): bool
    {
        return $this->category === 'carrier';
    }

    /**
     * 工具・消耗品か判定
     */
    public function isToolConsumable(): bool
    {
        return $this->category === 'tool_consumable';
    }

    /**
     * 委員会管理機材か判定
     */
    public function isCommittee(): bool
    {
        return $this->type === 'committee';
    }

    /**
     * イベント会社レンタル機材か判定
     */
    public function isRental(): bool
    {
        return $this->type === 'rental';
    }

    /**
     * 搬出入計画ステップ一覧とのリレーション
     */
    public function plans(): HasMany
    {
        return $this->hasMany(EquipmentLogisticsPlan::class, 'equipment_id')->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 主担当班（レーン）とのリレーション
     */
    public function lane(): BelongsTo
    {
        return $this->belongsTo(Lane::class, 'lane_id');
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
     * カテゴリで絞り込むスコープ
     */
    public function scopeCategory(Builder $query, ?string $category): Builder
    {
        if ($category) {
            return $query->where('category', $category);
        }

        return $query;
    }

    /**
     * 表示順でソートするスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }
}
