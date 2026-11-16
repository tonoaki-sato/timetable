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
     * アサインされた配置アイテム（ブース）群とのリレーション (1:N)
     */
    public function items(): HasMany
    {
        return $this->hasMany(LayoutItem::class)->where('item_type', 'booth')->orderBy('code', 'asc');
    }

    /**
     * 詳細モーダル表示用のブース情報配列
     *
     * @return array<int, array<string, string>>
     */
    public function getBoothsDetailListAttribute(): array
    {
        return $this->items->map(function (LayoutItem $b) {
            $categoryLabel = $b->boothCategory()?->label() ?? '';
            $categoryBadge = $b->boothCategory()?->badgeClasses() ?? 'bg-slate-100 text-slate-700 border-slate-300';
            $typeLabel = $b->boothType()?->label() ?? '';
            $statusLabel = $b->status?->label() ?? '';

            return [
                'code' => $b->code,
                'name' => $b->displayName,
                'area' => $b->area?->name ?? '通り',
                'category_label' => $categoryLabel,
                'category_badge' => $categoryBadge,
                'type_label' => $typeLabel,
                'status_label' => $statusLabel,
                'notes' => $b->notes ?? '',
            ];
        })->values()->all();
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
