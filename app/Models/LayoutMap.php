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
    'version_tag',
    'notes',
    'created_by',
])]
class LayoutMap extends Model
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
     * 作成者ユーザーとのリレーション
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 特設エリア一覧
     */
    public function areas(): HasMany
    {
        return $this->hasMany(LayoutArea::class)->orderBy('display_order', 'asc');
    }

    /**
     * コマ割り（ブース）一覧
     */
    public function booths(): HasMany
    {
        return $this->hasMany(LayoutBooth::class)->orderBy('display_order', 'asc');
    }

    /**
     * 現場設備・インフラ一覧
     */
    public function facilities(): HasMany
    {
        return $this->hasMany(LayoutFacility::class)->orderBy('display_order', 'asc');
    }
}
