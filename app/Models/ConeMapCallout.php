<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 吹き出し（注意事項）モデル
 *
 * @property int $id
 * @property int $timetable_id
 * @property int $site_base_map_id
 * @property int|null $target_cone_id
 * @property int|null $target_barricade_id
 * @property numeric-string|float $anchor_x
 * @property numeric-string|float $anchor_y
 * @property numeric-string|float $box_x
 * @property numeric-string|float $box_y
 * @property numeric-string|float $box_width
 * @property numeric-string|float $box_height
 * @property string|null $title
 * @property string $body
 * @property string $color_theme
 * @property numeric-string|float|null $font_size
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Timetable $timetable
 * @property-read SiteBaseMap $siteBaseMap
 * @property-read ConeMapCone|null $targetCone
 * @property-read ConeMapBarricade|null $targetBarricade
 */
#[Fillable([
    'timetable_id',
    'site_base_map_id',
    'target_cone_id',
    'target_barricade_id',
    'anchor_x',
    'anchor_y',
    'box_x',
    'box_y',
    'box_width',
    'box_height',
    'title',
    'body',
    'color_theme',
    'font_size',
])]
class ConeMapCallout extends Model
{
    /**
     * @var string
     */
    protected $table = 'cone_map_callouts';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timetable_id' => 'integer',
            'site_base_map_id' => 'integer',
            'target_cone_id' => 'integer',
            'target_barricade_id' => 'integer',
            'anchor_x' => 'decimal:2',
            'anchor_y' => 'decimal:2',
            'box_x' => 'decimal:2',
            'box_y' => 'decimal:2',
            'box_width' => 'decimal:2',
            'box_height' => 'decimal:2',
            'font_size' => 'decimal:2',
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
     * 紐づけ対象カラーコーン
     *
     * @return BelongsTo<ConeMapCone, $this>
     */
    public function targetCone(): BelongsTo
    {
        return $this->belongsTo(ConeMapCone::class, 'target_cone_id');
    }

    /**
     * 紐づけ対象バリケード
     *
     * @return BelongsTo<ConeMapBarricade, $this>
     */
    public function targetBarricade(): BelongsTo
    {
        return $this->belongsTo(ConeMapBarricade::class, 'target_barricade_id');
    }
}
