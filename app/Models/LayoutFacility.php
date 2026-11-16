<?php

namespace App\Models;

use App\Enums\FacilityStatus;
use App\Enums\FacilityType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'layout_map_id',
    'layout_area_id',
    'facility_code',
    'name',
    'facility_type',
    'location_description',
    'status',
    'notes',
    'display_order',
])]
class LayoutFacility extends Model
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
            'facility_type' => FacilityType::class,
            'status' => FacilityStatus::class,
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
     * 状態（未定/確定）のバッジCSSクラスを取得
     */
    public function getStatusBadgeClassesAttribute(): string
    {
        if ($this->status instanceof FacilityStatus) {
            return $this->status->badgeClasses();
        }

        return match ($this->status) {
            'confirmed', '確定' => 'bg-emerald-50 text-emerald-700 border-emerald-300 font-bold',
            default => 'bg-amber-50 text-amber-700 border-amber-300 font-medium',
        };
    }
}
