<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * コーン間連結コーンバーモデル（単独配置不可・必ず2本のコーン間を連結）
 *
 * @property int $id
 * @property int $timetable_id
 * @property int $site_base_map_id
 * @property int $start_cone_id
 * @property int $end_cone_id
 * @property string $bar_style
 * @property numeric-string|float $length_cm
 * @property numeric-string|float $diameter_cm
 * @property string|null $group_key
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Timetable $timetable
 * @property-read SiteBaseMap $siteBaseMap
 * @property-read ConeMapCone $startCone
 * @property-read ConeMapCone $endCone
 */
#[Fillable([
    'timetable_id',
    'site_base_map_id',
    'start_cone_id',
    'end_cone_id',
    'bar_style',
    'length_cm',
    'diameter_cm',
    'group_key',
    'notes',
])]
class ConeMapBar extends Model
{
    /**
     * コーンバー物理長さ (cm)
     */
    public const float DEFAULT_LENGTH_CM = 200.0;

    /**
     * コーンバー物理太さ (cm)
     */
    public const float DEFAULT_DIAMETER_CM = 3.4;

    /**
     * @var string
     */
    protected $table = 'cone_map_bars';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timetable_id' => 'integer',
            'site_base_map_id' => 'integer',
            'start_cone_id' => 'integer',
            'end_cone_id' => 'integer',
            'length_cm' => 'decimal:2',
            'diameter_cm' => 'decimal:2',
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
     * 始点カラーコーン
     *
     * @return BelongsTo<ConeMapCone, $this>
     */
    public function startCone(): BelongsTo
    {
        return $this->belongsTo(ConeMapCone::class, 'start_cone_id');
    }

    /**
     * 終点カラーコーン
     *
     * @return BelongsTo<ConeMapCone, $this>
     */
    public function endCone(): BelongsTo
    {
        return $this->belongsTo(ConeMapCone::class, 'end_cone_id');
    }
}
