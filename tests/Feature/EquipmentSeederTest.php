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
        $this->assertSame(23, EquipmentLogisticsPlan::count());

        $cone = Equipment::with('plans')->where('name', 'カラーコーン')->first();
        $this->assertNotNull($cone);
        $this->assertSame('committee', $cone->type);
        $this->assertSame(100, $cone->quantity);
        $this->assertSame('個', $cone->unit);
        $this->assertSame('番所地下倉庫', $cone->storage_location);
        $this->assertSame(10, $cone->display_order);
        $this->assertCount(9, $cone->plans);

        $firstConePlan = $cone->plans->firstWhere('title', '前日倉庫出し');
        $this->assertNotNull($firstConePlan);
        $this->assertSame('2027-10-09', $firstConePlan->target_date?->format('Y-m-d'));
        $this->assertSame('13:00', $firstConePlan->start_time);
        $this->assertSame('15:00', $firstConePlan->end_time);

        $lastConePlan = $cone->plans->firstWhere('title', '番所地下倉庫格納完了');
        $this->assertNotNull($lastConePlan);
        $this->assertSame('2027-10-10', $lastConePlan->target_date?->format('Y-m-d'));
        $this->assertSame('18:00', $lastConePlan->start_time);
        $this->assertSame('19:00', $lastConePlan->end_time);

        $equipment = Equipment::with('plans')->where('name', '足踏み式給水設備')->first();
        $this->assertNotNull($equipment);
        $this->assertSame('rental', $equipment->type);
        $this->assertSame(5, $equipment->quantity);
        $this->assertSame('台', $equipment->unit);

        $this->assertCount(14, $equipment->plans);

        // 立会必須ステップが4件（TT1/2で2件、TT4で2件）あること
        $witnessSteps = $equipment->plans->where('witness_required', true);
        $this->assertCount(4, $witnessSteps);
        $witnessWithStaff = $witnessSteps->whereNotNull('witness_staff_id');
        $this->assertCount(2, $witnessWithStaff);
        foreach ($witnessWithStaff as $step) {
            $this->assertSame('佐藤 外亮', $step->witnessStaff?->name);
        }
    }
}
