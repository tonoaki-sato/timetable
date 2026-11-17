<?php

namespace Database\Seeders;

use App\Models\SiteMapIconDefinition;
use Illuminate\Database\Seeder;

class SiteMapIconDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * 添付PDF（保土ヶ谷宿場まつり 食品衛生対応 平面図 A3 1/400）実測寸法準拠
     * 1m ≈ 7.0866pt (2.5mm)
     */
    public function run(): void
    {
        $icons = [
            [
                'icon_key' => 'booth_standard',
                'name' => '通り標準ブース (2m×2m)',
                'category' => 'booth',
                'render_style' => 'cad_tent',
                'default_width' => 13.86,
                'default_height' => 13.86,
                'is_resizable' => false,
                'is_rotatable' => true,
                'rotation_step' => 45,
                'supported_colors' => ['red', 'green', 'black', 'cyan', 'orange'],
                'default_color' => 'red',
                'has_hygiene_circle' => false,
                'hygiene_radius' => 138.00,
                'is_hygiene_target' => true,
                'icon_asset_path' => null,
            ],
            [
                'icon_key' => 'tent_2x15_horiz',
                'name' => 'パイプテント横 (2間×1.5間)',
                'category' => 'tent',
                'render_style' => 'cad_tent',
                'default_width' => 25.02,
                'default_height' => 19.14,
                'is_resizable' => false,
                'is_rotatable' => true,
                'rotation_step' => 45,
                'supported_colors' => ['red', 'green', 'black', 'cyan', 'orange'],
                'default_color' => 'black',
                'has_hygiene_circle' => false,
                'hygiene_radius' => 138.00,
                'is_hygiene_target' => true,
                'icon_asset_path' => null,
            ],
            [
                'icon_key' => 'tent_2x15_vert',
                'name' => 'パイプテント縦 (1.5間×2間)',
                'category' => 'tent',
                'render_style' => 'cad_tent',
                'default_width' => 19.14,
                'default_height' => 25.02,
                'is_resizable' => false,
                'is_rotatable' => true,
                'rotation_step' => 45,
                'supported_colors' => ['red', 'green', 'black', 'cyan', 'orange'],
                'default_color' => 'black',
                'has_hygiene_circle' => false,
                'hygiene_radius' => 138.00,
                'is_hygiene_target' => true,
                'icon_asset_path' => null,
            ],
            [
                'icon_key' => 'tent_3m',
                'name' => '持参テント (3m×3m)',
                'category' => 'tent',
                'render_style' => 'cad_tent',
                'default_width' => 21.24,
                'default_height' => 20.88,
                'is_resizable' => false,
                'is_rotatable' => true,
                'rotation_step' => 45,
                'supported_colors' => ['red', 'green', 'black', 'cyan', 'orange'],
                'default_color' => 'black',
                'has_hygiene_circle' => false,
                'hygiene_radius' => 138.00,
                'is_hygiene_target' => true,
                'icon_asset_path' => null,
            ],
            [
                'icon_key' => 'tent_1x15_vert',
                'name' => 'スリムテント縦 (1間×1.5間)',
                'category' => 'tent',
                'render_style' => 'cad_tent',
                'default_width' => 12.96,
                'default_height' => 18.84,
                'is_resizable' => false,
                'is_rotatable' => true,
                'rotation_step' => 45,
                'supported_colors' => ['cyan', 'red', 'green', 'black', 'orange'],
                'default_color' => 'cyan',
                'has_hygiene_circle' => false,
                'hygiene_radius' => 138.00,
                'is_hygiene_target' => false,
                'icon_asset_path' => null,
            ],
            [
                'icon_key' => 'kitchen_car',
                'name' => 'キッチンカー / 車両',
                'category' => 'vehicle',
                'render_style' => 'cad_car',
                'default_width' => 25.00,
                'default_height' => 13.00,
                'is_resizable' => true,
                'is_rotatable' => true,
                'rotation_step' => 45,
                'supported_colors' => ['orange', 'black', 'red', 'green', 'cyan'],
                'default_color' => 'orange',
                'has_hygiene_circle' => false,
                'hygiene_radius' => 138.00,
                'is_hygiene_target' => true,
                'icon_asset_path' => null,
            ],
            [
                'icon_key' => 'free_area',
                'name' => 'フリーエリア (可変)',
                'category' => 'area',
                'render_style' => 'cad_hatch',
                'default_width' => 45.00,
                'default_height' => 30.00,
                'is_resizable' => true,
                'is_rotatable' => true,
                'rotation_step' => 45,
                'supported_colors' => ['black', 'red', 'green', 'cyan', 'orange'],
                'default_color' => 'black',
                'has_hygiene_circle' => false,
                'hygiene_radius' => 138.00,
                'is_hygiene_target' => false,
                'icon_asset_path' => null,
            ],
            [
                'icon_key' => 'water_supply',
                'name' => '洗浄・手洗い設備 (20m圏)',
                'category' => 'facility',
                'render_style' => 'cad_water',
                'default_width' => 6.24,
                'default_height' => 6.24,
                'is_resizable' => false,
                'is_rotatable' => false,
                'rotation_step' => 0,
                'supported_colors' => ['blue'],
                'default_color' => 'blue',
                'has_hygiene_circle' => true,
                'hygiene_radius' => 138.00,
                'is_hygiene_target' => false,
                'icon_asset_path' => null,
            ],
            [
                'icon_key' => 'area_note',
                'name' => 'テキスト注記',
                'category' => 'note',
                'render_style' => 'cad_note',
                'default_width' => 80.00,
                'default_height' => 14.00,
                'is_resizable' => true,
                'is_rotatable' => false,
                'rotation_step' => 0,
                'supported_colors' => ['black', 'red', 'blue'],
                'default_color' => 'black',
                'has_hygiene_circle' => false,
                'hygiene_radius' => 138.00,
                'is_hygiene_target' => false,
                'icon_asset_path' => null,
            ],
        ];

        $validKeys = array_column($icons, 'icon_key');
        SiteMapIconDefinition::whereNotIn('icon_key', $validKeys)->delete();

        foreach ($icons as $icon) {
            SiteMapIconDefinition::updateOrCreate(
                ['icon_key' => $icon['icon_key']],
                $icon
            );
        }
    }
}
