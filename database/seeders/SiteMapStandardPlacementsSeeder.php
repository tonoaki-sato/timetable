<?php

namespace Database\Seeders;

use App\Actions\SiteMap\ApplyStandardSiteMapPresetAction;
use App\Models\SiteBaseMap;
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
        return app(ApplyStandardSiteMapPresetAction::class)->execute($baseMap, $items);
    }
}
