<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * 白地図マッピング配置モデル
 *
 * @property int $id
 * @property int $site_base_map_id
 * @property int|null $layout_item_id
 * @property string $shape_type
 * @property string $display_text
 * @property string $color_theme
 * @property float $pos_x
 * @property float $pos_y
 * @property float $width
 * @property float $height
 * @property int $rotation
 * @property bool $is_rotation_locked
 * @property string|null $notes
 * @property array<string, mixed>|null $meta_data
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read SiteBaseMap $siteBaseMap
 * @property-read LayoutItem|null $layoutItem
 */
class SiteMapPlacement extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'site_map_placements';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'site_base_map_id',
        'layout_item_id',
        'shape_type',
        'display_text',
        'color_theme',
        'pos_x',
        'pos_y',
        'width',
        'height',
        'rotation',
        'is_rotation_locked',
        'notes',
        'meta_data',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'site_base_map_id' => 'integer',
            'layout_item_id' => 'integer',
            'pos_x' => 'decimal:2',
            'pos_y' => 'decimal:2',
            'width' => 'decimal:2',
            'height' => 'decimal:2',
            'rotation' => 'integer',
            'is_rotation_locked' => 'boolean',
            'meta_data' => 'array',
        ];
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
