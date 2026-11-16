<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'contact_person',
    'contact',
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
     * アサインされた配置コマ（ブース）群とのリレーション (1:N)
     */
    public function booths(): HasMany
    {
        return $this->hasMany(LayoutBooth::class)->orderBy('booth_code', 'asc');
    }

    /**
     * 詳細モーダル表示用のブース情報配列
     *
     * @return array<int, array<string, string>>
     */
    public function getBoothsDetailListAttribute(): array
    {
        return $this->booths->map(fn (LayoutBooth $b) => [
            'code' => $b->booth_code,
            'name' => $b->displayName,
            'area' => $b->area?->name ?? '通り',
            'category_label' => $b->category?->label() ?? '',
            'category_badge' => $b->category?->badgeClasses() ?? '',
            'type_label' => $b->booth_type?->label() ?? '',
            'status_label' => $b->status?->label() ?? '',
            'items' => $b->product_items ?? '',
        ])->values()->all();
    }

    /**
     * キーワード検索スコープ（名称・担当者・連絡先）
     */
    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (! $keyword) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
                ->orWhere('contact_person', 'like', "%{$keyword}%")
                ->orWhere('contact', 'like', "%{$keyword}%");
        });
    }
}
