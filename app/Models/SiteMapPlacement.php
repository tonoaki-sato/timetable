<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 白地図マッピング配置モデル
 *
 * @property int $id
 * @property int $timetable_id
 * @property int $site_base_map_id
 * @property int|null $layout_item_id
 * @property string $shape_type
 * @property string $display_text
 * @property numeric-string|float|null $font_size
 * @property string $color_theme
 * @property numeric-string|float $pos_x
 * @property numeric-string|float $pos_y
 * @property numeric-string|float $width
 * @property numeric-string|float $height
 * @property int $rotation
 * @property bool $is_rotation_locked
 * @property bool $show_hygiene_circle
 * @property int $arc_start_angle
 * @property int $arc_end_angle
 * @property int $z_index
 * @property string|null $notes
 * @property array<string, mixed>|null $meta_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Timetable $timetable
 * @property-read SiteBaseMap $siteBaseMap
 * @property-read LayoutItem|null $layoutItem
 */
#[Fillable([
    'timetable_id',
    'site_base_map_id',
    'layout_item_id',
    'shape_type',
    'display_text',
    'font_size',
    'color_theme',
    'pos_x',
    'pos_y',
    'width',
    'height',
    'rotation',
    'is_rotation_locked',
    'show_hygiene_circle',
    'arc_start_angle',
    'arc_end_angle',
    'z_index',
    'notes',
    'meta_data',
])]
class SiteMapPlacement extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'site_map_placements';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'timetable_id' => 'integer',
            'site_base_map_id' => 'integer',
            'layout_item_id' => 'integer',
            'font_size' => 'decimal:2',
            'pos_x' => 'decimal:2',
            'pos_y' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'rotation' => 'integer',
            'is_rotation_locked' => 'boolean',
            'show_hygiene_circle' => 'boolean',
            'arc_start_angle' => 'integer',
            'arc_end_angle' => 'integer',
            'z_index' => 'integer',
            'meta_data' => 'array',
        ];
    }

    /**
     * 所属する運行情報 (Timetable)
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
     * 紐づく現場ブース・設備レコード (layout_items)
     *
     * @return BelongsTo<LayoutItem, $this>
     */
    public function layoutItem(): BelongsTo
    {
        return $this->belongsTo(LayoutItem::class, 'layout_item_id');
    }
}
