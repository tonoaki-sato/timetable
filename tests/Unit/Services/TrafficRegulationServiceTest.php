<?php

namespace Tests\Unit\Services;

use App\Enums\TrafficRegulationStatus;
use App\Models\Timetable;
use App\Services\TrafficRegulationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrafficRegulationServiceTest extends TestCase
{
    use RefreshDatabase;

    private TrafficRegulationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new TrafficRegulationService;
    }

    public function test_returns_inactive_when_no_timetable_exists_for_today(): void
    {
        $now = Carbon::create(2026, 10, 28, 12, 0, 0, 'Asia/Tokyo');
        $context = $this->service->getCurrentContext($now);

        $this->assertSame(TrafficRegulationStatus::Inactive, $context->status);
        $this->assertFalse($context->isRoadClosed);
        $this->assertFalse($context->isAlertActive);
    }

    public function test_transitions_through_all_time_phases_on_main_event_day(): void
    {
        Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'status' => 'active',
            'road_periods' => [
                ['start_time' => '10:00', 'end_time' => '18:00', 'status' => 'closed', 'label' => '通行止め'],
            ],
        ]);

        // 1. 05:30 (早朝待機)
        $earlyContext = $this->service->getCurrentContext(Carbon::create(2026, 10, 11, 5, 30, 0, 'Asia/Tokyo'));
        $this->assertSame(TrafficRegulationStatus::PreRegulation, $earlyContext->status);
        $this->assertSame('早朝待機中', $earlyContext->headline);
        $this->assertFalse($earlyContext->isRoadClosed);

        // 2. 09:40 (規制前・仮設営中)
        $preContext = $this->service->getCurrentContext(Carbon::create(2026, 10, 11, 9, 40, 0, 'Asia/Tokyo'));
        $this->assertSame(TrafficRegulationStatus::PreRegulation, $preContext->status);
        $this->assertStringContainsString('あと20分', $preContext->subStatus);
        $this->assertFalse($preContext->isRoadClosed);

        // 3. 10:15 (路上本設置集中)
        $setupContext = $this->service->getCurrentContext(Carbon::create(2026, 10, 11, 10, 15, 0, 'Asia/Tokyo'));
        $this->assertSame(TrafficRegulationStatus::RoadSetup, $setupContext->status);
        $this->assertStringContainsString('11:00本番まであと45分', $setupContext->subStatus);
        $this->assertTrue($setupContext->isRoadClosed);
        $this->assertTrue($setupContext->isAlertActive);

        // 4. 14:30 (まつり本番開催中)
        $mainContext = $this->service->getCurrentContext(Carbon::create(2026, 10, 11, 14, 30, 0, 'Asia/Tokyo'));
        $this->assertSame(TrafficRegulationStatus::MainEvent, $mainContext->status);
        $this->assertStringContainsString('解除まであと3時間30分', $mainContext->subStatus);
        $this->assertTrue($mainContext->isRoadClosed);

        // 5. 17:20 (撤収ピーク)
        $teardownContext = $this->service->getCurrentContext(Carbon::create(2026, 10, 11, 17, 20, 0, 'Asia/Tokyo'));
        $this->assertSame(TrafficRegulationStatus::PeakTeardown, $teardownContext->status);
        $this->assertStringContainsString('18:00解除まであと40分', $teardownContext->subStatus);
        $this->assertTrue($teardownContext->isRoadClosed);
        $this->assertTrue($teardownContext->isAlertActive);

        // 6. 18:10 (通行止め解除済)
        $postContext = $this->service->getCurrentContext(Carbon::create(2026, 10, 11, 18, 10, 0, 'Asia/Tokyo'));
        $this->assertSame(TrafficRegulationStatus::PostRegulation, $postContext->status);
        $this->assertFalse($postContext->isRoadClosed);
        $this->assertFalse($postContext->isAlertActive);
    }
}

