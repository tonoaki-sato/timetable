<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * カラーコーン配置モデル（ウェイトは1:1セットとして自動連動）
 *
 * @property int $id
 * @property int $timetable_id
 * @property int $site_base_map_id
 * @property numeric-string|float $pos_x
 * @property numeric-string|float $pos_y
 * @property string $color
 * @property string|null $label
 * @property string|null $group_key
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Timetable $timetable
 * @property-read SiteBaseMap $siteBaseMap
 */
#[Fillable([
    'timetable_id',
    'site_base_map_id',
    'pos_x',
    'pos_y',
    'color',
    'label',
    'group_key',
    'notes',
])]
class ConeMapCone extends Model
{
    /**
     * カラーコーン物理高さ (cm)
     */
    public const float HEIGHT_CM = 70.0;

    /**
     * カラーコーン底面正方形一辺 (cm)
     */
    public const float BASE_SIZE_CM = 38.8;

    /**
     * @var string
     */
    protected $table = 'cone_map_cones';

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
     * このコーンを始点とするコーンバー一覧
     *
     * @return HasMany<ConeMapBar, $this>
     */
    public function barsAsStart(): HasMany
    {
        return $this->hasMany(ConeMapBar::class, 'start_cone_id');
    }

    /**
     * このコーンを終点とするコーンバー一覧
     *
     * @return HasMany<ConeMapBar, $this>
     */
    public function barsAsEnd(): HasMany
    {
        return $this->hasMany(ConeMapBar::class, 'end_cone_id');
    }

    /**
     * このコーンに紐づく吹き出し一覧
     *
     * @return HasMany<ConeMapCallout, $this>
     */
    public function callouts(): HasMany
    {
        return $this->hasMany(ConeMapCallout::class, 'target_cone_id');
    }
}
