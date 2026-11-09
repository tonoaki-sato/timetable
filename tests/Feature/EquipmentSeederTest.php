<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPlan;
use Database\Seeders\EquipmentSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\LocationTypeSeeder;
use Database\Seeders\StaffSeeder;
use Database\Seeders\TimetableSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->seed(StaffSeeder::class);
        $this->seed(TimetableSeeder::class);
        $this->seed(LocationTypeSeeder::class);
        $this->seed(LocationSeeder::class);
    }

    /**
     * EquipmentSeederが機材および搬出入計画を正常に投入できること
     */
    public function test_equipment_seeder_creates_equipment_and_logistics_plans(): void
    {
        $this->seed(EquipmentSeeder::class);

        $this->assertSame(2, Equipment::count());
        $this->assertSame(7, EquipmentLogisticsPlan::count());

        $cone = Equipment::where('name', 'カラーコーン')->first();
        $this->assertNotNull($cone);
        $this->assertSame('committee', $cone->type);
        $this->assertSame(100, $cone->quantity);
        $this->assertSame('個', $cone->unit);
        $this->assertSame('番所地下倉庫', $cone->storage_location);
        $this->assertSame(10, $cone->display_order);

        $equipment = Equipment::with('plans')->where('name', '足踏み式給水設備')->first();
        $this->assertNotNull($equipment);
        $this->assertSame('rental', $equipment->type);
        $this->assertSame(5, $equipment->quantity);
        $this->assertSame('台', $equipment->unit);
        $this->assertSame('給水設備', $equipment->lane?->name);

        $this->assertCount(7, $equipment->plans);

        // 立会必須ステップが2件（vendor_receive, vendor_pickup）あること
        $witnessSteps = $equipment->plans->where('witness_required', true);
        $this->assertCount(2, $witnessSteps);
        foreach ($witnessSteps as $step) {
            $this->assertSame('佐藤 外亮', $step->witnessStaff?->name);
        }
    }
}
