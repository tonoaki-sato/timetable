<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentLogisticsPattern;
use App\Models\Timetable;
use App\Models\User;
use App\Services\EquipmentLogisticsService;
use Database\Seeders\EquipmentLogisticsPatternSeeder;
use Database\Seeders\LocationSeeder;
use Database\Seeders\LocationTypeSeeder;
use Database\Seeders\StaffSeeder;
use Database\Seeders\TimetableSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EquipmentLogisticsPatternSeederTest extends TestCase
{
    use RefreshDatabase;

    private User $operator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(UserSeeder::class);
        $this->seed(StaffSeeder::class);
        $this->seed(TimetableSeeder::class);
        $this->seed(LocationTypeSeeder::class);
        $this->seed(LocationSeeder::class);

        $this->operator = User::where('role', 'operator')->first()
            ?? User::factory()->create(['role' => 'operator', 'is_active' => true]);
    }

    /**
     * Seederが全7パターンおよびステップを正常に投入できること
     */
    public function test_seeder_creates_all_patterns_with_steps(): void
    {
        $this->seed(EquipmentLogisticsPatternSeeder::class);

        $this->assertSame(7, EquipmentLogisticsPattern::count());

        $expectedPatterns = [
            'カラーコーン型（交通規制連動・9ステップ）' => ['steps' => 9, 'target_type' => 'committee'],
            '一般自前資材型（2日間設置サイクル）' => ['steps' => 6, 'target_type' => 'committee'],
            '私物持ち込み・持ち帰り型' => ['steps' => 4, 'target_type' => 'personal'],
            'レンタル機材・委員会設置（受取・回収立会要）' => ['steps' => 7, 'target_type' => 'rental'],
            'イベント会社一任型（設営・解体搬出）' => ['steps' => 2, 'target_type' => 'rental'],
            '放送設備型（事前配線・現地テント内保管）' => ['steps' => 5, 'target_type' => 'rental'],
            '足踏み式給水設備型パターン' => ['steps' => 7, 'target_type' => 'rental'],
        ];

        foreach ($expectedPatterns as $name => $meta) {
            $pattern = EquipmentLogisticsPattern::with('steps')->where('name', $name)->first();
            $this->assertNotNull($pattern, "パターン「{$name}」が存在すること");
            $this->assertSame($meta['target_type'], $pattern->target_type);
            $this->assertCount($meta['steps'], $pattern->steps);
        }

        // レンタル機材パターンは受取・回収時のみ立会必須であること
        $rentalPattern = EquipmentLogisticsPattern::with('steps')->where('name', 'レンタル機材・委員会設置（受取・回収立会要）')->first();
        $witnessSteps = $rentalPattern->steps->where('witness_required', true);
        $this->assertCount(2, $witnessSteps);
        $this->assertTrue($witnessSteps->pluck('action_type')->contains('vendor_receive'));
        $this->assertTrue($witnessSteps->pluck('action_type')->contains('vendor_pickup'));
    }

    /**
     * 搬出入管理画面で初期パターンが表示されること
     */
    public function test_logistics_index_shows_seeded_patterns(): void
    {
        $this->seed(EquipmentLogisticsPatternSeeder::class);

        $this->actingAs($this->operator);

        $timetable = Timetable::first();

        $response = $this->get(route('equipment-patterns.index'));

        $response->assertStatus(200);
        $response->assertSee('カラーコーン型（交通規制連動・9ステップ）');
        $response->assertSee('レンタル機材・委員会設置（受取・回収立会要）');
        $response->assertSee('放送設備型（事前配線・現地テント内保管）');
    }

    /**
     * シードされたパターンを資材に適用できること
     */
    public function test_seeded_pattern_can_be_applied_to_equipment(): void
    {
        $this->seed(EquipmentLogisticsPatternSeeder::class);

        $equipment = Equipment::factory()->create([
            'name' => 'カラーコーン（約60本）',
            'type' => 'committee',
            'quantity' => 60,
            'storage_location' => '番所地下倉庫',
        ]);

        $pattern = EquipmentLogisticsPattern::where('name', 'カラーコーン型（交通規制連動・9ステップ）')->first();

        $service = app(EquipmentLogisticsService::class);
        $count = $service->applyPattern($equipment, $pattern);

        $this->assertSame(9, $count);
        $this->assertCount(9, $equipment->fresh()->plans);
    }
}
