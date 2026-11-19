<?php

namespace App\Models;

use App\Enums\BoothCategory;
use App\Enums\BoothType;
use App\Enums\FacilityType;
use App\Enums\LayoutItemStatus;
use App\Enums\LayoutItemType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'layout_map_id',
    'layout_area_id',
    'item_type',
    'code',
    'name',
    'status',
    'category',
    'booth_type',
    'vendor_id',
    'location_description',
    'notes',
    'display_order',
])]
class LayoutItem extends Model
{
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'item_type' => LayoutItemType::class,
            'status' => LayoutItemStatus::class,
            'display_order' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 配置図面とのリレーション
     */
    public function layoutMap(): BelongsTo
    {
        return $this->belongsTo(LayoutMap::class);
    }

    /**
     * 所属エリアとのリレーション
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(LayoutArea::class, 'layout_area_id');
    }

    /**
     * 出店者マスターとのリレーション（ブースのみ）
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * 白地図上のマッピング配置オブジェクト一覧とのリレーション
     */
    public function placements(): HasMany
    {
        return $this->hasMany(SiteMapPlacement::class, 'layout_item_id');
    }

    /**
     * 出店ブースかどうかの判定
     */
    public function isBooth(): bool
    {
        return $this->item_type === LayoutItemType::Booth;
    }

    /**
     * 現場設備かどうかの判定
     */
    public function isFacility(): bool
    {
        return $this->item_type === LayoutItemType::Facility;
    }

    /**
     * 表示用の名称（直接の名称または出店者名）
     */
    public function getDisplayNameAttribute(): string
    {
        if (! empty($this->name)) {
            return $this->name;
        }

        if ($this->isBooth() && $this->vendor !== null && ! empty($this->vendor->name)) {
            return $this->vendor->name;
        }

        return $this->isBooth() ? '(未割当)' : '現場設備';
    }

    /**
     * 出店区分 Enum
     */
    public function boothCategory(): ?BoothCategory
    {
        return $this->category ? BoothCategory::tryFrom($this->category) : null;
    }

    /**
     * ブース形態 Enum (出店・設備共通)
     */
    public function boothType(): ?BoothType
    {
        return $this->booth_type ? BoothType::tryFrom($this->booth_type) : null;
    }

    /**
     * 設備詳細種別 Enum (category カラムから取得)
     */
    public function facilityType(): ?FacilityType
    {
        return $this->category ? FacilityType::tryFrom($this->category) : null;
    }

    /**
     * ステータスバッジのCSSクラス
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return $this->status->badgeClasses();
    }

    /**
     * 区分・種別のアイコン
     */
    public function typeIcon(): string
    {
        if ($this->isBooth()) {
            return $this->boothCategory()?->icon() ?? '🎪';
        }

        return $this->facilityType()?->icon() ?? '🚰';
    }

    /**
     * 区分・種別の日本語ラベル
     */
    public function typeLabel(): string
    {
        if ($this->isBooth()) {
            return $this->boothCategory()?->label() ?? '出店';
        }

        return $this->facilityType()?->label() ?? '設備';
    }

    /**
     * 区分・種別バッジのCSSクラス
     */
    public function typeBadgeClasses(): string
    {
        if ($this->isBooth()) {
            return $this->boothCategory()?->badgeClasses() ?? 'bg-indigo-50 text-indigo-700 border-indigo-200';
        }

        return $this->facilityType()?->badgeClasses() ?? 'bg-cyan-50 text-cyan-800 border-cyan-200';
    }
}
