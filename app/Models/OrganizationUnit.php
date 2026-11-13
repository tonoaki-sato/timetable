<?php

namespace App\Models;

use Database\Factories\OrganizationUnitFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'parent_id',
    'name',
    'short_description',
    'description',
    'display_order',
])]
class OrganizationUnit extends Model
{
    /** @use HasFactory<OrganizationUnitFactory> */
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
        ];
    }

    /**
     * 親組織とのリレーション
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * 子組織一覧とのリレーション（表示順ソート）
     */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('display_order')->orderBy('id');
    }

    /**
     * 再帰的なすべての子孫組織とのリレーション
     */
    public function allChildren(): HasMany
    {
        return $this->children()->with(['allChildren', 'staffMembers']);
    }

    /**
     * 担当スタッフ中間情報一覧とのリレーション（表示順ソート）
     */
    public function staffAssignments(): HasMany
    {
        return $this->hasMany(OrganizationUnitStaff::class)->orderBy('display_order')->orderBy('id');
    }

    /**
     * 担当スタッフ一覧との多対多リレーション
     */
    public function staff(): BelongsToMany
    {
        return $this->belongsToMany(Staff::class, 'organization_unit_staff')
            ->withPivot(['id', 'role', 'display_order'])
            ->orderByPivot('display_order')
            ->orderByPivot('id')
            ->withTimestamps();
    }

    /**
     * 最上位（ルート）組織のみを取得するスコープ
     */
    public function scopeRootOnly(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * 表示順でソートするスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }

    /**
     * 担当スタッフ名のカンマ区切り文字列（一覧表示用）
     */
    public function getStaffNamesDisplayAttribute(): string
    {
        if ($this->relationLoaded('staff')) {
            return $this->staff->pluck('name')->implode(', ');
        }

        if ($this->relationLoaded('staffAssignments')) {
            return $this->staffAssignments
                ->map(fn ($assignment) => $assignment->staff?->name)
                ->filter()
                ->implode(', ');
        }

        return $this->staff()->pluck('name')->implode(', ');
    }
}
