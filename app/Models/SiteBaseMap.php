<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * 白地図マスターモデル
 *
 * 運行情報（Timetable）に紐づく白地図ファイル、寸法、縮尺（スケール比率）を管理する。
 *
 * @property int $id
 * @property int $timetable_id
 * @property string $name
 * @property string $cad_version
 * @property string $paper_size
 * @property numeric-string|float $scale_ratio
 * @property numeric-string|float $width_meters
 * @property numeric-string|float $height_meters
 * @property string $svg_path
 * @property string $pdf_path
 * @property string $viewbox
 * @property bool $is_active
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Timetable $timetable
 */
#[Fillable([
    'timetable_id',
    'name',
    'cad_version',
    'paper_size',
    'scale_ratio',
    'width_meters',
    'height_meters',
    'svg_path',
    'pdf_path',
    'viewbox',
    'is_active',
    'notes',
])]
class SiteBaseMap extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'site_base_maps';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timetable_id' => 'integer',
            'scale_ratio' => 'decimal:4',
            'width_meters' => 'decimal:2',
            'height_meters' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * 親の運行情報 (Timetable)
     *
     * @return BelongsTo<Timetable, $this>
     */
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class);
    }

    /**
     * 白地図上に配置されたオブジェクト群
     *
     * @return HasMany<SiteMapPlacement, $this>
     */
    public function placements(): HasMany
    {
        return $this->hasMany(SiteMapPlacement::class, 'site_base_map_id');
    }
}
