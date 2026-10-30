<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\LogisticsLocation;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentPrintTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->operator = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        Timetable::factory()->create(['target_date' => '2026-10-11', 'name' => '1日目']);
        Timetable::factory()->create(['target_date' => '2026-10-12', 'name' => '2日目']);
        LogisticsLocation::factory()->create(['name' => '税務署前駐車場']);
    }

    /**
     * 印刷用画面が正常に表示されること
     */
    public function test_print_page_displays_correctly(): void
    {
        $this->actingAs($this->operator);

        Equipment::factory()->committee()->create([
            'name' => 'カラーコーン（印刷検証）',
            'quantity' => 30,
        ]);

        $response = $this->get(route('equipments.print'));
        $response->assertStatus(200);
        $response->assertSee('資材・機材 搬出入・中継・夜間保管計画書');
        $response->assertSee('カラーコーン（印刷検証）');
        $response->assertSee('税務署前駐車場');
    }
}
