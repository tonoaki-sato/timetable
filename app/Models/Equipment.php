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
    'quantity',
    'unit',
    'storage_location',
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
     * 表示順でソートするスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }
}
