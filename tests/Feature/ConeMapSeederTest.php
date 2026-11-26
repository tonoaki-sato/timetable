<?php

namespace Tests\Feature;

use App\Models\ConeMapBar;
use App\Models\ConeMapBarricade;
use App\Models\ConeMapCone;
use App\Models\SiteBaseMap;
use App\Models\Timetable;
use Database\Seeders\ConeMapSeeder;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LaneSeeder;
use Database\Seeders\SiteBaseMapSeeder;
use Database\Seeders\StaffSeeder;
use Database\Seeders\TimetableSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConeMapSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ConeMapSeeder が全カラーコーン・コーンバー・バリケード配置データを正しく登録すること
     */
    public function test_cone_map_seeder_seeds_all_records_correctly(): void
    {
        $this->seed([
            UserSeeder::class,
            StaffSeeder::class,
            LaneSeeder::class,
            TimetableSeeder::class,
            SiteBaseMapSeeder::class,
            ConeMapSeeder::class,
        ]);

        $this->assertSame(88, ConeMapCone::count());
        $this->assertSame(46, ConeMapBar::count());
        $this->assertSame(2, ConeMapBarricade::count());

        $timetable = Timetable::where('target_date', '2027-10-10')
            ->where('name', '第35回保土ケ谷宿場まつり（1日目）')
            ->firstOrFail();
        $baseMap = SiteBaseMap::where('cad_version', '260813')
            ->where('svg_path', 'maps/shopping_street_base_map_260813.svg')
            ->firstOrFail();

        $this->assertSame(88, ConeMapCone::where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $baseMap->id)
            ->count());
        $this->assertSame(46, ConeMapBar::where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $baseMap->id)
            ->count());
        $this->assertSame(2, ConeMapBarricade::where('timetable_id', $timetable->id)
            ->where('site_base_map_id', $baseMap->id)
            ->count());

        $coneIds = ConeMapCone::pluck('id')->all();
        foreach (ConeMapBar::all() as $bar) {
            $this->assertContains($bar->start_cone_id, $coneIds);
            $this->assertContains($bar->end_cone_id, $coneIds);
        }

        $this->assertDatabaseHas('cone_map_cones', [
            'id' => 31,
            'color' => 'red',
            'group_key' => 'ea96dafd-62d4-416b-9988-abe64cc8e72f',
        ]);
        $this->assertDatabaseHas('cone_map_cones', [
            'id' => 163,
            'color' => 'red',
            'group_key' => null,
        ]);
        $this->assertDatabaseHas('cone_map_bars', [
            'id' => 21,
            'start_cone_id' => 31,
            'end_cone_id' => 32,
            'bar_style' => 'yellow_black',
        ]);
        $this->assertDatabaseHas('cone_map_bars', [
            'id' => 92,
            'start_cone_id' => 159,
            'end_cone_id' => 160,
            'bar_style' => 'yellow_black',
        ]);
        $this->assertDatabaseHas('cone_map_barricades', [
            'id' => 5,
            'barricade_type' => 'resin_pipe',
            'rotation' => 0,
        ]);
        $this->assertDatabaseHas('cone_map_barricades', [
            'id' => 7,
            'barricade_type' => 'resin_pipe',
            'rotation' => 0,
        ]);
    }

    /**
     * ConeMapSeeder を複数回実行しても冪等性が保たれること
     */
    public function test_cone_map_seeder_is_idempotent(): void
    {
        $this->seed([
            UserSeeder::class,
            StaffSeeder::class,
            LaneSeeder::class,
            TimetableSeeder::class,
            SiteBaseMapSeeder::class,
            ConeMapSeeder::class,
        ]);

        $this->assertSame(88, ConeMapCone::count());
        $this->assertSame(46, ConeMapBar::count());
        $this->assertSame(2, ConeMapBarricade::count());

        $this->seed(ConeMapSeeder::class);

        $this->assertSame(88, ConeMapCone::count());
        $this->assertSame(46, ConeMapBar::count());
        $this->assertSame(2, ConeMapBarricade::count());
    }

    /**
     * DatabaseSeeder 全体を実行した際にカラーコーン配置データが正しくシードされること
     */
    public function test_database_seeder_runs_cone_map_seeder_successfully(): void
    {
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(88, ConeMapCone::count());
        $this->assertSame(46, ConeMapBar::count());
        $this->assertSame(2, ConeMapBarricade::count());
    }
}
