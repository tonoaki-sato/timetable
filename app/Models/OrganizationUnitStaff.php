<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'organization_unit_id',
    'staff_id',
    'role',
    'display_order',
])]
class OrganizationUnitStaff extends Model
{
    use HasFactory;

    /**
     * テーブル名の明示指定
     *
     * @var string
     */
    protected $table = 'organization_unit_staff';

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
     * 所属組織ユニットとのリレーション
     */
    public function organizationUnit(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnit::class);
    }

    /**
     * 担当スタッフとのリレーション
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }
}
