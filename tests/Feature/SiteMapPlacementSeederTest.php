<?php

namespace Tests\Feature;

use App\Models\SiteBaseMap;
use App\Models\SiteMapPlacement;
use App\Models\Timetable;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LaneSeeder;
use Database\Seeders\SiteBaseMapSeeder;
use Database\Seeders\SiteMapPlacementSeeder;
use Database\Seeders\StaffSeeder;
use Database\Seeders\TimetableSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteMapPlacementSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * SiteMapPlacementSeeder が全107件の配置データを正しく登録すること
     */
    public function test_site_map_placement_seeder_seeds_all_placements_correctly(): void
    {
        $this->seed([
            UserSeeder::class,
            StaffSeeder::class,
            LaneSeeder::class,
            TimetableSeeder::class,
            SiteBaseMapSeeder::class,
            SiteMapPlacementSeeder::class,
        ]);

        $this->assertSame(107, SiteMapPlacement::count());

        $timetable = Timetable::where('target_date', '2027-10-10')
            ->where('name', '第35回保土ケ谷宿場まつり（1日目）')
            ->firstOrFail();
        $baseMap = SiteBaseMap::where('cad_version', '260813')
            ->where('svg_path', 'maps/shopping_street_base_map_260813.svg')
            ->firstOrFail();

        $this->assertSame(107, SiteMapPlacement::where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $baseMap->id)
            ->count());

        $expectedShapeCounts = [
            'booth_with_tent' => 44,
            'booth_standard' => 37,
            'tent_2x15_horiz' => 10,
            'water_supply' => 5,
            'free_area' => 5,
            'tent_1x15_vert' => 2,
            'tent_2x15_vert' => 2,
            'kitchen_car' => 2,
        ];
        foreach ($expectedShapeCounts as $shapeType => $count) {
            $this->assertSame(
                $count,
                SiteMapPlacement::where('shape_type', $shapeType)->count(),
                "Unexpected count for shape_type: {$shapeType}"
            );
        }

        $this->assertDatabaseHas('site_map_placements', [
            'id' => 2,
            'display_text' => 'A51',
            'shape_type' => 'booth_with_tent',
            'color_theme' => 'red',
        ]);
        $this->assertDatabaseHas('site_map_placements', [
            'id' => 102,
            'display_text' => '本陣詰所',
            'shape_type' => 'tent_2x15_horiz',
            'color_theme' => 'cyan',
        ]);
        $this->assertDatabaseHas('site_map_placements', [
            'id' => 106,
            'display_text' => 'PAブース',
            'shape_type' => 'tent_2x15_horiz',
            'color_theme' => 'black',
        ]);
        $this->assertDatabaseHas('site_map_placements', [
            'id' => 107,
            'display_text' => '猿回し',
            'shape_type' => 'free_area',
            'color_theme' => 'black',
        ]);
        $this->assertDatabaseHas('site_map_placements', [
            'id' => 108,
            'display_text' => 'ごみ集積場',
            'shape_type' => 'free_area',
            'color_theme' => 'black',
        ]);
        $this->assertDatabaseHas('site_map_placements', [
            'id' => 109,
            'display_text' => "人・まち・文化振興会\nゲーム・クイズ",
            'shape_type' => 'tent_2x15_horiz',
            'color_theme' => 'black',
        ]);
        $this->assertDatabaseHas('site_map_placements', [
            'id' => 110,
            'display_text' => "人・まち・文化振興会\n歴史展示",
            'shape_type' => 'free_area',
            'color_theme' => 'black',
        ]);
    }

    /**
     * SiteMapPlacementSeeder を複数回実行しても冪等性が保たれること
     */
    public function test_site_map_placement_seeder_is_idempotent(): void
    {
        $this->seed([
            UserSeeder::class,
            StaffSeeder::class,
            LaneSeeder::class,
            TimetableSeeder::class,
            SiteBaseMapSeeder::class,
            SiteMapPlacementSeeder::class,
        ]);
        $this->assertSame(107, SiteMapPlacement::count());

        $this->seed(SiteMapPlacementSeeder::class);
        $this->assertSame(107, SiteMapPlacement::count());
    }

    /**
     * DatabaseSeeder 全体を実行した際に白地図配置データが正しくシードされること
     */
    public function test_database_seeder_runs_site_map_placement_seeder_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(107, SiteMapPlacement::count());
    }
}
