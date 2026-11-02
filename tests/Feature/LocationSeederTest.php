<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\User;
use Database\Seeders\LocationSeeder;
use Database\Seeders\LocationTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationSeederTest extends TestCase
{
    use RefreshDatabase;

    /**
     * LocationTypeSeeder および LocationSeeder が正常に実行され、拠点データが投入されること
     */
    public function test_location_seeders_run_successfully(): void
    {
        User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->seed([LocationTypeSeeder::class, LocationSeeder::class]);

        $this->assertDatabaseHas('location_types', ['name' => 'parking']);
        $this->assertDatabaseHas('location_types', ['name' => 'storage']);
        $this->assertDatabaseHas('location_types', ['name' => 'community_space']);
        $this->assertDatabaseHas('location_types', ['name' => 'open_space']);

        $this->assertDatabaseHas('locations', ['name' => '番所地下倉庫', 'is_all_day' => 1]);
        $this->assertDatabaseHas('locations', ['name' => '番所倉庫', 'is_all_day' => 1]);
        $this->assertDatabaseHas('locations', ['name' => '番所前スペース', 'is_all_day' => 0]);
        $this->assertDatabaseHas('locations', ['name' => 'コミュニティルーム', 'is_all_day' => 1]);
        $this->assertDatabaseHas('locations', ['name' => '税務署前駐車場', 'is_all_day' => 0]);
        $this->assertDatabaseHas('locations', ['name' => '長島駐車場（旧北川駐車場）', 'is_all_day' => 0]);

        $taxOffice = Location::where('name', '税務署前駐車場')->first();
        $this->assertNotNull($taxOffice);
        $this->assertTrue($taxOffice->schedules()->exists());
        $this->assertEquals('available', $taxOffice->schedules()->first()->availability_status);

        $banshoSpace = Location::where('name', '番所前スペース')->first();
        $this->assertNotNull($banshoSpace);
        $this->assertCount(2, $banshoSpace->schedules);
    }

    /**
     * Seeder が複数回実行されても一意制約エラー等が発生しないこと（冪等性）
     */
    public function test_location_seeders_are_idempotent(): void
    {
        User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->seed([LocationTypeSeeder::class, LocationSeeder::class]);
        $firstCount = Location::count();

        // 2回目の実行
        $this->seed([LocationTypeSeeder::class, LocationSeeder::class]);
        $this->assertEquals($firstCount, Location::count());
    }
}
