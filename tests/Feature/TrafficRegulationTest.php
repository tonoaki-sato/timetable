<?php

namespace Tests\Feature;

use App\Enums\TrafficRegulationStatus;
use App\Models\Timetable;
use App\Models\User;
use App\Services\TrafficRegulationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrafficRegulationTest extends TestCase
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
    }

    /**
     * 本番日以外は通常交通（規制なし）と判定されること
     */
    public function test_traffic_regulation_inactive_on_non_festival_day(): void
    {
        $nonFestivalDate = Carbon::parse('2026-06-15 12:00:00', 'Asia/Tokyo');

        $service = app(TrafficRegulationService::class);
        $context = $service->getCurrentContext($nonFestivalDate);

        $this->assertSame(TrafficRegulationStatus::Inactive, $context->status);
        $this->assertFalse($context->isRoadClosed);
        $this->assertSame('通常交通（規制なし）', $context->headline);
        $this->assertStringContainsString('一般車両通行中', $context->subStatus);
    }

    /**
     * 06:00〜09:59は規制前・機材仮設置中（一般車通行中）と判定されること
     */
    public function test_traffic_regulation_pre_regulation_period_0600_to_1000(): void
    {
        $eventDate = Carbon::parse('2026-10-11 08:30:00', 'Asia/Tokyo');

        Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $service = app(TrafficRegulationService::class);
        $context = $service->getCurrentContext($eventDate);

        $this->assertSame(TrafficRegulationStatus::PreRegulation, $context->status);
        $this->assertFalse($context->isRoadClosed);
        $this->assertSame('機材仮設置・設営準備中', $context->headline);
        $this->assertStringContainsString('10:00より車両通行止め', $context->subStatus);
        $this->assertStringContainsString('あと90分', $context->subStatus);
    }

    /**
     * 10:00〜10:59は路上への本設置集中中（車両通行止め）と判定されること
     */
    public function test_traffic_regulation_road_setup_period_1000_to_1100(): void
    {
        $eventDate = Carbon::parse('2026-10-11 10:20:00', 'Asia/Tokyo');

        Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $service = app(TrafficRegulationService::class);
        $context = $service->getCurrentContext($eventDate);

        $this->assertSame(TrafficRegulationStatus::RoadSetup, $context->status);
        $this->assertTrue($context->isRoadClosed);
        $this->assertSame('路上への本設置集中中', $context->headline);
        $this->assertStringContainsString('ござ市テント・機材展開中', $context->subStatus);
        $this->assertStringContainsString('あと40分', $context->subStatus);
    }

    /**
     * 11:00〜16:29はまつり本番運行中（車両通行止め）と判定されること
     */
    public function test_traffic_regulation_main_event_period_1100_to_1700(): void
    {
        $eventDate = Carbon::parse('2026-10-11 13:00:00', 'Asia/Tokyo');

        Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $service = app(TrafficRegulationService::class);
        $context = $service->getCurrentContext($eventDate);

        $this->assertSame(TrafficRegulationStatus::MainEvent, $context->status);
        $this->assertTrue($context->isRoadClosed);
        $this->assertSame('まつり本番開催中', $context->headline);
        $this->assertStringContainsString('10:00〜18:00通行止め', $context->subStatus);
        $this->assertStringContainsString('解除まであと5時間0分', $context->subStatus);
    }

    /**
     * 16:30〜16:59は一部先行仮撤去中の注記が含まれること
     */
    public function test_traffic_regulation_early_clearing_period_1630_to_1700(): void
    {
        $eventDate = Carbon::parse('2026-10-11 16:45:00', 'Asia/Tokyo');

        Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $service = app(TrafficRegulationService::class);
        $context = $service->getCurrentContext($eventDate);

        $this->assertSame(TrafficRegulationStatus::MainEvent, $context->status);
        $this->assertStringContainsString('一部先行仮撤収中', $context->subStatus);
    }

    /**
     * 17:00〜17:59は撤収ピーク（18:00解除必須）と判定されること
     */
    public function test_traffic_regulation_peak_teardown_period_1700_to_1800(): void
    {
        $eventDate = Carbon::parse('2026-10-11 17:15:00', 'Asia/Tokyo');

        Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $service = app(TrafficRegulationService::class);
        $context = $service->getCurrentContext($eventDate);

        $this->assertSame(TrafficRegulationStatus::PeakTeardown, $context->status);
        $this->assertTrue($context->isRoadClosed);
        $this->assertSame('路上資材の撤収ピーク', $context->headline);
        $this->assertStringContainsString('車道資材・ゴミ箱完全回収中', $context->subStatus);
        $this->assertStringContainsString('あと45分', $context->subStatus);
    }

    /**
     * 18:00以降は通行止め解除済（車両注意・残作業中）と判定されること
     */
    public function test_traffic_regulation_post_regulation_period_after_1800(): void
    {
        $eventDate = Carbon::parse('2026-10-11 18:30:00', 'Asia/Tokyo');

        Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
        ]);

        $service = app(TrafficRegulationService::class);
        $context = $service->getCurrentContext($eventDate);

        $this->assertSame(TrafficRegulationStatus::PostRegulation, $context->status);
        $this->assertFalse($context->isRoadClosed);
        $this->assertSame('車両注意・残作業中', $context->headline);
        $this->assertStringContainsString('18:00に通行止め解除済', $context->subStatus);
    }

    /**
     * ダッシュボード画面で道路交通規制カードが正常に描画されること
     */
    public function test_dashboard_renders_traffic_regulation_card(): void
    {
        $response = $this->actingAs($this->operator)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('道路交通状況');
        $response->assertSee('通常交通（規制なし）');
    }
}
