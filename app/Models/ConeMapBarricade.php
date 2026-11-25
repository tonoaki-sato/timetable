<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * バリケード配置モデル（A型バリケード / 樹脂製単管バリケード）
 *
 * @property int $id
 * @property int $timetable_id
 * @property int $site_base_map_id
 * @property string $barricade_type
 * @property numeric-string|float $pos_x
 * @property numeric-string|float $pos_y
 * @property int $rotation
 * @property string|null $label
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Timetable $timetable
 * @property-read SiteBaseMap $siteBaseMap
 */
#[Fillable([
    'timetable_id',
    'site_base_map_id',
    'barricade_type',
    'pos_x',
    'pos_y',
    'rotation',
    'label',
    'notes',
])]
class ConeMapBarricade extends Model
{
    /**
     * @var string
     */
    protected $table = 'cone_map_barricades';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timetable_id' => 'integer',
            'site_base_map_id' => 'integer',
            'pos_x' => 'decimal:2',
            'pos_y' => 'decimal:2',
            'rotation' => 'integer',
        ];
    }

    /**
     * 所属する運行情報
     *
     * @return BelongsTo<Timetable, $this>
     */
    public function timetable(): BelongsTo
    {
        return $this->belongsTo(Timetable::class, 'timetable_id');
    }

    /**
     * 所属する白地図マスター
     *
     * @return BelongsTo<SiteBaseMap, $this>
     */
    public function siteBaseMap(): BelongsTo
    {
        return $this->belongsTo(SiteBaseMap::class, 'site_base_map_id');
    }

    /**
     * このバリケードに紐づく吹き出し一覧
     *
     * @return HasMany<ConeMapCallout, $this>
     */
    public function callouts(): HasMany
    {
        return $this->hasMany(ConeMapCallout::class, 'target_barricade_id');
    }
}
