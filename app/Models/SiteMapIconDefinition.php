<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * CADシンボル種別定義マスターモデル
 *
 * @property int $id
 * @property string $icon_key
 * @property string $name
 * @property string $category
 * @property string $render_style
 * @property float $default_width
 * @property float $default_height
 * @property bool $is_resizable
 * @property bool $is_rotatable
 * @property int $rotation_step
 * @property array<string>|null $supported_colors
 * @property string $default_color
 * @property bool $has_hygiene_circle
 * @property float $hygiene_radius
 * @property bool $is_hygiene_target
 * @property bool $is_preset
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
        'render_style',
        'default_width',
        'default_height',
        'is_resizable',
        'is_rotatable',
        'rotation_step',
        'supported_colors',
        'default_color',
        'has_hygiene_circle',
        'hygiene_radius',
        'is_hygiene_target',
        'is_preset',
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
            'hygiene_radius' => 'decimal:2',
            'is_hygiene_target' => 'boolean',
            'is_preset' => 'boolean',
        ];
    }

    /**
     * パレット表示の優先順位定義（数値が小さい順に表示）
     *
     * @var array<string, int>
     */
    public const PALETTE_ORDER = [
        'booth_standard' => 1,      // 通り標準ブース (最優先)
        'booth_with_tent' => 2,     // 通りテントブース (2番目)
        'tent_2x15_horiz' => 10,    // パイプテント横
        'tent_2x15_vert' => 11,     // パイプテント縦
        'tent_3m' => 12,            // 持参テント
        'tent_1x15_vert' => 13,     // スリムテント縦
        'kitchen_car' => 20,        // キッチンカー
        'free_area' => 30,          // フリーエリア
        'water_supply' => 40,       // 洗浄・手洗い設備
        'area_note' => 50,          // テキスト注記
    ];

    /**
     * ユーザーが追加したカスタムアイコンかどうかを判定
     */
    public function isCustom(): bool
    {
        return ! $this->is_preset;
    }

    /**
     * パレット表示順の重みを取得（SQL非依存ソート用）
     */
    public function getPaletteOrderWeight(): int
    {
        if (isset(self::PALETTE_ORDER[$this->icon_key])) {
            return self::PALETTE_ORDER[$this->icon_key];
        }

        // 未定義プリセットは 90、カスタムアイコンは 100
        return $this->is_preset ? 90 : 100;
    }
}
