<?php

namespace Tests\Feature;

use App\Enums\AnnualPhase;
use App\Models\AnnualSchedule;
use App\Models\Timetable;
use App\Models\User;
use App\Services\PhaseService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseManagementTest extends TestCase
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
     * 月数値から正しい年間フェーズEnumが解決されること
     */
    public function test_annual_phase_enum_resolves_correct_months(): void
    {
        $this->assertSame(AnnualPhase::Preparation, AnnualPhase::fromMonth(1));
        $this->assertSame(AnnualPhase::Preparation, AnnualPhase::fromMonth(2));
        $this->assertSame(AnnualPhase::Planning, AnnualPhase::fromMonth(3));
        $this->assertSame(AnnualPhase::Planning, AnnualPhase::fromMonth(5));
        $this->assertSame(AnnualPhase::Arrangement, AnnualPhase::fromMonth(6));
        $this->assertSame(AnnualPhase::Arrangement, AnnualPhase::fromMonth(8));
        $this->assertSame(AnnualPhase::Execution, AnnualPhase::fromMonth(9));
        $this->assertSame(AnnualPhase::Execution, AnnualPhase::fromMonth(10));
        $this->assertSame(AnnualPhase::Followup, AnnualPhase::fromMonth(11));
        $this->assertSame(AnnualPhase::Followup, AnnualPhase::fromMonth(12));
    }

    /**
     * 通常期において直近の年間スケジュールを反映したフェーズ情報が取得できること
     */
    public function test_phase_service_resolves_preparation_phase_with_upcoming_task(): void
    {
        $simulatedDate = Carbon::parse('2026-05-15 10:00:00', 'Asia/Tokyo');

        AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '第3回幹事会・予算案確定',
            'start_date' => '2026-05-20',
            'status' => 'planned',
        ]);

        $service = app(PhaseService::class);
        $context = $service->getCurrentContext($simulatedDate);

        $this->assertFalse($context->isMainEventDay);
        $this->assertSame(AnnualPhase::Planning, $context->phase);
        $this->assertSame('フェーズ2【計画段階】', $context->displayName);
        $this->assertStringContainsString('第3回幹事会・予算案確定', $context->subStatus);
        $this->assertStringContainsString('あと5日', $context->subStatus);
        $this->assertGreaterThanOrEqual(1, $context->pendingTaskCount);
    }

    /**
     * 当日開催のタイムテーブルが存在する場合に本番運行モードへ切り替わること
     */
    public function test_phase_service_resolves_main_event_day_when_timetable_matches_today(): void
    {
        $eventDate = Carbon::parse('2026-10-11 11:30:00', 'Asia/Tokyo');

        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-11',
            'name' => '1日目 (Day 1) 本祭運行・ござ市',
            'status' => 'active',
        ]);

        $service = app(PhaseService::class);
        $context = $service->getCurrentContext($eventDate);

        $this->assertTrue($context->isMainEventDay);
        $this->assertSame('1日目 (Day 1) 本祭運行・ござ市', $context->displayName);
        $this->assertSame('本番運行セッション進行中', $context->subStatus);
        $this->assertSame($timetable->id, $context->activeTimetable?->id);
    }

    /**
     * AnnualSchedule モデルのフェーズアクセサおよびスコープが正しく動作すること
     */
    public function test_annual_schedule_model_phase_accessor_and_scope(): void
    {
        $schedule1 = AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '第1回総会',
            'start_date' => '2026-02-15',
        ]);

        $schedule2 = AnnualSchedule::factory()->create([
            'target_year' => 2026,
            'title' => '警察協議・道路使用許可申請',
            'start_date' => '2026-07-20',
        ]);

        $this->assertSame(AnnualPhase::Preparation, $schedule1->phase);
        $this->assertSame(AnnualPhase::Arrangement, $schedule2->phase);

        $arrangementCount = AnnualSchedule::phase(AnnualPhase::Arrangement)->count();
        $this->assertSame(1, $arrangementCount);
    }

    /**
     * ダッシュボード画面でフェーズ管理情報が正常に描画されること
     */
    public function test_dashboard_renders_phase_card_with_annual_schedule(): void
    {
        AnnualSchedule::factory()->create([
            'target_year' => Carbon::now('Asia/Tokyo')->year,
            'title' => '保土ケ谷宿場まつり実行委員会 総会',
            'start_date' => Carbon::now('Asia/Tokyo')->addDays(3)->toDateString(),
            'status' => 'planned',
        ]);

        $response = $this->actingAs($this->operator)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('準備フェーズ');
        $response->assertSee('保土ケ谷宿場まつり実行委員会 総会');
    }
}
