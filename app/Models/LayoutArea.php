<?php

namespace App\Models;

use App\Presenters\LayoutAreaPresenter;
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

    protected ?LayoutAreaPresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): LayoutAreaPresenter
    {
        return $this->presenterInstance ??= new LayoutAreaPresenter($this);
    }

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
}
