<?php

namespace App\Models;

use App\Presenters\VendorPresenter;
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

    protected ?VendorPresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): VendorPresenter
    {
        return $this->presenterInstance ??= new VendorPresenter($this);
    }

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
