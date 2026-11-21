<?php

namespace App\Actions\SiteMap;

use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use App\Repositories\Contracts\SiteMapRepositoryInterface;
use Illuminate\Support\Facades\DB;

class ApplyStandardSiteMapPresetAction
{
    public function __construct(
        protected SiteMapRepositoryInterface $siteMapRepository
    ) {}

    /**
     * 指定運行情報および白地図に対して標準プリセット配置を一括適用
     *
     * @param  array<int, array<string, mixed>>|null  $items
     */
    public function execute(Timetable $timetable, SiteBaseMap $baseMap, ?array $items = null): int
    {
        if ($items === null) {
            $jsonPath = storage_path('app/site_map_standard_placements.json');
            if (! file_exists($jsonPath)) {
                return 0;
            }

            $decoded = json_decode((string) file_get_contents($jsonPath), true);
            $items = is_array($decoded) ? $decoded : [];
        }

        return DB::transaction(function () use ($timetable, $baseMap, $items): int {
            $this->siteMapRepository->clearPlacements($timetable, $baseMap);

            $layoutItemsByCode = $this->siteMapRepository
                ->getLayoutItemsForTimetable($timetable)
                ->keyBy('code')
                ->all();

            $now = now();
            $rows = [];

            foreach ($items as $item) {
                $code = trim((string) ($item['display_text'] ?? ''));
                $matchedLayoutItem = $layoutItemsByCode[$code] ?? null;

                $zIndex = match ($item['shape_type'] ?? '') {
                    'free_area' => 5,
                    'area_note' => 8,
                    'water_supply' => 30,
                    default => 10,
                };

                $rows[] = [
                    'timetable_id' => $timetable->id,
                    'site_base_map_id' => $baseMap->id,
                    'layout_item_id' => $matchedLayoutItem?->id,
                    'shape_type' => (string) ($item['shape_type'] ?? 'booth_standard'),
                    'display_text' => (string) ($item['display_text'] ?? ''),
                    'color_theme' => (string) ($item['color_theme'] ?? 'red'),
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
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($rows, 200) as $chunk) {
                SiteMapPlacement::insert($chunk);
            }

            return count($rows);
        });
    }
}
