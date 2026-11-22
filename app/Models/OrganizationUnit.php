<?php

namespace App\Models;

use App\Presenters\OrganizationUnitPresenter;
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
        return $this->children()->with(['allChildren', 'staff']);
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
     * プレゼンターインスタンスを取得
     */
    public function presenter(): OrganizationUnitPresenter
    {
        return new OrganizationUnitPresenter($this);
    }

    /**
     * 表示順でソートするスコープ
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('id');
    }
}
