<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardRealtimeTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_guest_cannot_access_dashboard_realtime_endpoint(): void
    {
        $response = $this->getJson(route('dashboard.realtime'));

        $response->assertStatus(401);
    }

    public function test_realtime_endpoint_returns_server_heartbeat_and_global_status(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 11, 9, 45, 0, 'Asia/Tokyo'));

        $user = User::factory()->create(['is_active' => true, 'role' => 'admin']);

        $response = $this->actingAs($user)->getJson(route('dashboard.realtime'));

        $response->assertStatus(200)
            ->assertJsonStructure([
                'server_time_ms',
                'updated_at_label',
                'status',
                'days_until_main_event',
                'schedule_completion_rate',
            ]);

        $this->assertSame('09:45:00', $response->json('updated_at_label'));
        $this->assertSame('ok', $response->json('status'));
    }
}
