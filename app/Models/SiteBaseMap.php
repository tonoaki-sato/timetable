<?php

namespace App\Models;

use App\Presenters\SiteBaseMapPresenter;
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

    protected ?SiteBaseMapPresenter $presenterInstance = null;

    /**
     * 表示用 Presenter インスタンスを取得
     */
    public function presenter(): SiteBaseMapPresenter
    {
        return $this->presenterInstance ??= new SiteBaseMapPresenter($this);
    }

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
     * 白地図上に配置されたカラーコーン群
     *
     * @return HasMany<ConeMapCone, $this>
     */
    public function coneMapCones(): HasMany
    {
        return $this->hasMany(ConeMapCone::class, 'site_base_map_id');
    }

    /**
     * 白地図上に配置されたコーンバー群
     *
     * @return HasMany<ConeMapBar, $this>
     */
    public function coneMapBars(): HasMany
    {
        return $this->hasMany(ConeMapBar::class, 'site_base_map_id');
    }

    /**
     * 白地図上に配置されたバリケード群
     *
     * @return HasMany<ConeMapBarricade, $this>
     */
    public function coneMapBarricades(): HasMany
    {
        return $this->hasMany(ConeMapBarricade::class, 'site_base_map_id');
    }

    /**
     * 白地図上に配置された吹き出し（注意事項）群
     *
     * @return HasMany<ConeMapCallout, $this>
     */
    public function coneMapCallouts(): HasMany
    {
        return $this->hasMany(ConeMapCallout::class, 'site_base_map_id');
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
}
