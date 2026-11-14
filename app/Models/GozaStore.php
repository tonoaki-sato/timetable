<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'timetable_id',
    'store_name',
    'representative_name',
    'emergency_contact',
    'product_items',
    'status',
    'notes',
])]
class GozaStore extends Model
{
    use HasFactory;

    public const array STATUSES = [
        'applied' => '申込受付',
        'confirmed' => '確定',
        'checked_in' => '当日受付済',
        'cancelled' => '辞退・キャンセル',
    ];

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
     * アサインされた配置コマ（ブース）とのリレーション
     */
    public function booth(): HasOne
    {
        return $this->hasOne(LayoutBooth::class);
    }

    /**
     * ステータスの日本語表示
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * ステータスバッジのCSSクラス
     */
    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'confirmed' => 'bg-emerald-50 text-emerald-800 border-emerald-300 font-bold',
            'checked_in' => 'bg-indigo-50 text-indigo-800 border-indigo-300 font-bold',
            'applied' => 'bg-amber-50 text-amber-800 border-amber-300',
            'cancelled' => 'bg-slate-100 text-slate-600 border-slate-300',
            default => 'bg-slate-100 text-slate-700 border-slate-300',
        };
    }
}
