<?php

namespace App\Models;

use App\Enums\BoothCategory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'layout_map_id',
    'layout_area_id',
    'goza_store_id',
    'booth_code',
    'name',
    'category',
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
     * ござ市出店者とのリレーション
     */
    public function gozaStore(): BelongsTo
    {
        return $this->belongsTo(GozaStore::class);
    }

    /**
     * 表示用の名称（直接の名称またはござ市出店名）
     */
    public function getDisplayNameAttribute(): string
    {
        if (! empty($this->name)) {
            return $this->name;
        }

        if ($this->gozaStore !== null && ! empty($this->gozaStore->store_name)) {
            return $this->gozaStore->store_name;
        }

        return '(未割当)';
    }
}
