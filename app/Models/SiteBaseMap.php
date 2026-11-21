<?php

namespace App\Models;

use Database\Factories\SiteBaseMapFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * 白地図マスターモデル
 *
 * 運行日程（Timetable）から独立した共通白地図ファイル、寸法、縮尺（スケール比率）を一元管理する。
 *
 * @property int $id
 * @property string $name
 * @property string $cad_version
 * @property string $paper_size
 * @property numeric-string|float $scale_ratio
 * @property numeric-string|float $width_meters
 * @property numeric-string|float $height_meters
 * @property string $svg_path
 * @property string|null $pdf_path
 * @property string $viewbox
 * @property bool $is_default
 * @property bool $is_active
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'name',
    'cad_version',
    'paper_size',
    'scale_ratio',
    'width_meters',
    'height_meters',
    'svg_path',
    'pdf_path',
    'viewbox',
    'is_default',
    'is_active',
    'notes',
])]
class SiteBaseMap extends Model
{
    /** @use HasFactory<SiteBaseMapFactory> */
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
            'scale_ratio' => 'decimal:4',
            'width_meters' => 'decimal:2',
            'height_meters' => 'decimal:2',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /**
     * この白地図を使用している運行情報一覧
     *
     * @return HasMany<Timetable, $this>
     */
    public function timetables(): HasMany
    {
        return $this->hasMany(Timetable::class, 'site_base_map_id');
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

    /**
     * プリセット標準アセット（削除保護対象）かどうかを判定
     */
    public function isPresetAsset(): bool
    {
        return ! Str::startsWith((string) $this->svg_path, 'maps/custom/');
    }

    /**
     * viewBox 文字列から幅・高さ（pt）を解析して返却
     *
     * @return array{width: float, height: float}
     */
    public function getViewboxDimensions(): array
    {
        $parts = preg_split('/[\s,]+/', trim((string) $this->viewbox));
        if (is_array($parts) && count($parts) === 4) {
            $width = (float) $parts[2];
            $height = (float) $parts[3];
            if ($width > 0.0 && $height > 0.0) {
                return [
                    'width' => $width,
                    'height' => $height,
                ];
            }
        }

        return [
            'width' => 842.0,
            'height' => 1191.0,
        ];
    }

    /**
     * 詳細・編集モーダル用ペイロード配列を生成
     *
     * @return array{
     *     id: int,
     *     name: string,
     *     cad_version: string,
     *     paper_size: string,
     *     scale_ratio: float,
     *     width_meters: float,
     *     height_meters: float,
     *     svg_path: string,
     *     svg_url: string,
     *     pdf_path: ?string,
     *     pdf_url: ?string,
     *     viewbox: string,
     *     is_default: bool,
     *     is_active: bool,
     *     is_preset_asset: bool,
     *     notes: ?string,
     *     timetables_count: int,
     *     placements_count: int,
     *     update_url: string,
     *     destroy_url: string
     * }
     */
    public function toModalPayload(): array
    {
        return [
            'id' => $this->id,
            'name' => (string) $this->name,
            'cad_version' => (string) $this->cad_version,
            'paper_size' => (string) $this->paper_size,
            'scale_ratio' => (float) $this->scale_ratio,
            'width_meters' => (float) $this->width_meters,
            'height_meters' => (float) $this->height_meters,
            'svg_path' => (string) $this->svg_path,
            'svg_url' => asset((string) $this->svg_path),
            'pdf_path' => $this->pdf_path ? (string) $this->pdf_path : null,
            'pdf_url' => $this->pdf_path ? asset((string) $this->pdf_path) : null,
            'viewbox' => (string) $this->viewbox,
            'is_default' => (bool) $this->is_default,
            'is_active' => (bool) $this->is_active,
            'is_preset_asset' => $this->isPresetAsset(),
            'notes' => $this->notes,
            'timetables_count' => (int) ($this->timetables_count ?? $this->timetables()->count()),
            'placements_count' => (int) ($this->placements_count ?? $this->placements()->count()),
            'update_url' => route('site-base-maps.update', $this),
            'destroy_url' => route('site-base-maps.destroy', $this),
        ];
    }
}
