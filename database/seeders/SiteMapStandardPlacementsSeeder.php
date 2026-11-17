<?php

namespace Database\Seeders;

use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use Illuminate\Database\Seeder;

class SiteMapStandardPlacementsSeeder extends Seeder
{
    /**
     * 添付PDF（ござ市配置20260927.pdf）の標準配置データをシードする
     */
    public function run(): void
    {
        $jsonPath = storage_path('app/site_map_standard_placements.json');
        if (! file_exists($jsonPath)) {
            return;
        }

        $items = json_decode((string) file_get_contents($jsonPath), true);
        if (! is_array($items)) {
            return;
        }

        $baseMaps = SiteBaseMap::where('is_active', true)->get();

        foreach ($baseMaps as $baseMap) {
            self::seedForBaseMap($baseMap, $items);
        }
    }

    /**
     * 指定した白地図に対して標準配置データを適用する
     *
     * @param  array<int, array<string, mixed>>|null  $items
     */
    public static function seedForBaseMap(SiteBaseMap $baseMap, ?array $items = null): int
    {
        if ($items === null) {
            $jsonPath = storage_path('app/site_map_standard_placements.json');
            if (! file_exists($jsonPath)) {
                return 0;
            }
            $items = json_decode((string) file_get_contents($jsonPath), true) ?? [];
        }

        // 既存配置をクリアしてクリーンに再現
        $baseMap->placements()->delete();

        // 運行情報の layout_items をコードでインデックス化（自動紐づけ用）
        $layoutItemsByCode = [];
        if ($baseMap->timetable) {
            $layoutItemsByCode = $baseMap->timetable->layoutItems()
                ->get()
                ->keyBy('code')
                ->all();
        }

        $count = 0;
        foreach ($items as $index => $item) {
            $code = trim((string) ($item['display_text'] ?? ''));
            $matchedLayoutItem = $layoutItemsByCode[$code] ?? null;

            $zIndex = match ($item['shape_type'] ?? '') {
                'free_area' => 5,
                'area_note' => 8,
                'water_supply' => 30,
                default => 10,
            };

            SiteMapPlacement::create([
                'site_base_map_id' => $baseMap->id,
                'layout_item_id' => $matchedLayoutItem?->id,
                'shape_type' => $item['shape_type'] ?? 'booth_standard',
                'display_text' => $item['display_text'] ?? '',
                'color_theme' => $item['color_theme'] ?? 'red',
                'pos_x' => (float) ($item['pos_x'] ?? 0),
                'pos_y' => (float) ($item['pos_y'] ?? 0),
                'width' => (float) ($item['width'] ?? 13.86),
                'height' => (float) ($item['height'] ?? 13.86),
                'rotation' => (int) ($item['rotation'] ?? 0),
                'is_rotation_locked' => (bool) ($item['is_rotation_locked'] ?? false),
                'show_hygiene_circle' => (bool) ($item['show_hygiene_circle'] ?? true),
                'arc_start_angle' => (int) ($item['arc_start_angle'] ?? 0),
                'arc_end_angle' => (int) ($item['arc_end_angle'] ?? 360),
                'z_index' => $zIndex,
            ]);
            $count++;
        }

        return $count;
    }
}
