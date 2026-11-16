<?php

namespace App\Models;

use App\Enums\BoothCategory;
use App\Enums\BoothStatus;
use App\Enums\BoothType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'layout_map_id',
    'layout_area_id',
    'vendor_id',
    'booth_code',
    'name',
    'category',
    'booth_type',
    'status',
    'product_items',
    'adjacent_landmark',
    'notes',
    'display_order',
])]
class LayoutBooth extends Model
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
            'category' => BoothCategory::class,
            'booth_type' => BoothType::class,
            'status' => BoothStatus::class,
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
     * 特設エリアとのリレーション
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(LayoutArea::class, 'layout_area_id');
    }

    /**
     * 出店者とのリレーション
     */
    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * 後方互換用エイリアス
     */
    public function gozaStore(): BelongsTo
    {
        return $this->vendor();
    }

    /**
     * 表示用の名称（直接の名称または出店者名）
     */
    public function getDisplayNameAttribute(): string
    {
        if (! empty($this->name)) {
            return $this->name;
        }

        if ($this->vendor !== null && ! empty($this->vendor->name)) {
            return $this->vendor->name;
        }

        return '(未割当)';
    }
}
