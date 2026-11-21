<?php

namespace App\Models;

use Database\Factories\LayoutAreaFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 会場エリアマスターモデル
 *
 * 運行日程（Timetable）・配置図面（LayoutMap）から独立し、全運行日で共有する会場エリア定義を一元管理する。
 *
 * @property int $id
 * @property int|null $location_id
 * @property string $name
 * @property string|null $notes
 * @property int $display_order
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'location_id',
    'name',
    'notes',
    'display_order',
    'is_active',
])]
class LayoutArea extends Model
{
    /** @use HasFactory<LayoutAreaFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'location_id' => 'integer',
            'display_order' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 関連拠点（Location）とのリレーション
     *
     * @return BelongsTo<Location, $this>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * このエリアに属する配置アイテム一覧（出店ブース・現場設備）
     *
     * @return HasMany<LayoutItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(LayoutItem::class)->orderBy('display_order')->orderBy('code');
    }

    /**
     * 有効エリアのみ抽出するスコープ
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * 表示順ソートスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order', 'asc')->orderBy('id', 'asc');
    }

    /**
     * 詳細・編集モーダル用ペイロード配列を生成
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     location_id: ?int,
     *     location_name: ?string,
     *     location_type_label: ?string,
     *     notes: ?string,
     *     display_order: int,
     *     is_active: bool,
     *     items_count: int,
     *     layout_maps_count: int,
     *     update_url: string,
     *     destroy_url: string
     * }
     */
    public function toModalPayload(): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'location_id' => $this->location_id !== null ? (int) $this->location_id : null,
            'location_name' => $this->location?->name,
            'location_type_label' => $this->location?->type?->label,
            'notes' => $this->notes,
            'display_order' => (int) $this->display_order,
            'is_active' => (bool) $this->is_active,
            'items_count' => (int) ($this->items_count ?? $this->items()->count()),
            'layout_maps_count' => (int) ($this->layout_maps_count ?? $this->items()->distinct()->count('layout_map_id')),
            'update_url' => route('layout-areas.update', $this),
            'destroy_url' => route('layout-areas.destroy', $this),
        ];
    }
}
