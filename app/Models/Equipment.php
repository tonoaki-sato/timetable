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
    'type',
    'checkout_rule',
    'quantity',
    'is_approximate_quantity',
    'unit',
    'storage_location',
    'storage_detail',
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
        'personal' => '私物（個人持ち込み）',
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
        '番所倉庫' => '番所倉庫',
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
            'is_approximate_quantity' => 'boolean',
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
     * 持出ルールラベルアクセサ
     */
    public function getCheckoutRuleLabelAttribute(): string
    {
        return self::CHECKOUT_RULES[$this->checkout_rule] ?? $this->checkout_rule ?? '';
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
     * 私物（個人持ち込み）か判定
     */
    public function isPersonal(): bool
    {
        return $this->type === 'personal';
    }

    /**
     * 搬出入計画ステップ一覧とのリレーション
     */
    public function plans(): HasMany
    {
        return $this->hasMany(EquipmentLogisticsPlan::class, 'equipment_id')->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 主担当班とのリレーション
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
