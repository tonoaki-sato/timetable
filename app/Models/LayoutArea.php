<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'layout_map_id',
    'location_id',
    'zone_code',
    'name',
    'display_order',
])]
class LayoutArea extends Model
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
     * 既存拠点（Location）とのリレーション
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * このエリアに属するコマ割り（ブース）一覧
     */
    public function booths(): HasMany
    {
        return $this->hasMany(LayoutBooth::class)->orderBy('display_order')->orderBy('booth_code');
    }

    /**
     * このエリアに属するインフラ設備一覧
     */
    public function facilities(): HasMany
    {
        return $this->hasMany(LayoutFacility::class)->orderBy('display_order')->orderBy('facility_code');
    }
}
