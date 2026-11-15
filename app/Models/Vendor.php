<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'timetable_id',
    'name',
    'representative_name',
    'emergency_contact',
    'notes',
])]
class Vendor extends Model
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
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    /**
     * 運行計画（タイムテーブル）とのリレーション
     */
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    /**
     * アサインされた配置コマ（ブース）群とのリレーション (1:N)
     */
    public function booths(): HasMany
    {
        return $this->hasMany(LayoutBooth::class)->orderBy('booth_code', 'asc');
    }

    /**
     * 確保しているコマ数
     */
    public function getBoothsCountAttribute(): int
    {
        return $this->booths->count();
    }
}
