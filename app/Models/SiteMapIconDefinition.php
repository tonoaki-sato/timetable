<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * アイコン種別定義マスターモデル
 *
 * @property int $id
 * @property string $icon_key
 * @property string $name
 * @property string $category
 * @property float $default_width
 * @property float $default_height
 * @property bool $is_resizable
 * @property bool $is_rotatable
 * @property int $rotation_step
 * @property array<string>|null $supported_colors
 * @property bool $has_hygiene_circle
 * @property bool $is_hygiene_target
 * @property string|null $icon_asset_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class SiteMapIconDefinition extends Model
{
    use HasFactory;

    /**
     * @var string
     */
    protected $table = 'site_map_icon_definitions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'icon_key',
        'name',
        'category',
        'default_width',
        'default_height',
        'is_resizable',
        'is_rotatable',
        'rotation_step',
        'supported_colors',
        'has_hygiene_circle',
        'is_hygiene_target',
        'icon_asset_path',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'default_width' => 'decimal:2',
            'default_height' => 'decimal:2',
            'is_resizable' => 'boolean',
            'is_rotatable' => 'boolean',
            'rotation_step' => 'integer',
            'supported_colors' => 'array',
            'has_hygiene_circle' => 'boolean',
            'is_hygiene_target' => 'boolean',
        ];
    }
}
