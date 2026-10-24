<?php

namespace Tests\Unit;

use App\DTOs\PhaseContext;
use App\Enums\AnnualPhase;
use App\Services\PhaseService;
use App\View\Components\Dashboard\PhaseCard;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class DashboardPhaseCardTest extends TestCase
{
    use RefreshDatabase;

    /**
     * サービスコンテナ経由で引数なしでコンポーネントが自動解決・初期化できること
     */
    public function test_component_can_be_resolved_from_service_container(): void
    {
        $component = app(PhaseCard::class);

        $this->assertInstanceOf(PhaseCard::class, $component);
        $this->assertInstanceOf(PhaseContext::class, $component->context);
        $this->assertNotEmpty($component->displayName());
        $this->assertNotEmpty($component->actionUrl());
    }

    /**
     * 本番運行日（Main Event Day）モード時の各種表示プロパティの判定が正しいこと
     */
    public function test_main_event_day_mode_presentation(): void
    {
        $context = new PhaseContext(
            phase: AnnualPhase::Execution,
            displayName: '1日目 (Day 1) 本祭運行',
            subStatus: '午前セッション進行中',
            isMainEventDay: true,
            pendingTaskCount: 5,
            actionUrl: 'http://localhost/timetable'
        );

        $phaseService = Mockery::mock(PhaseService::class);
        $component = new PhaseCard($phaseService, phaseContext: $context);

        $this->assertSame('運行フェーズ', $component->typeLabel());
        $this->assertSame('🏮', $component->icon());
        $this->assertSame('text-emerald-600', $component->subStatusColorClass());
        $this->assertFalse($component->shouldShowPendingBadge());
        $this->assertSame('1日目 (Day 1) 本祭運行', $component->displayName());
        $this->assertSame('午前セッション進行中', $component->subStatus());
        $this->assertSame('http://localhost/timetable', $component->actionUrl());
    }

    /**
     * 準備フェーズで未完了タスクがある場合の各種表示プロパティの判定が正しいこと
     */
    public function test_preparation_phase_with_pending_tasks_presentation(): void
    {
        $context = new PhaseContext(
            phase: AnnualPhase::Preparation,
            displayName: 'フェーズ1【準備段階】',
            subStatus: '第1回総会 あと5日',
            isMainEventDay: false,
            pendingTaskCount: 3,
            actionUrl: 'http://localhost/annual-schedules'
        );

        $phaseService = Mockery::mock(PhaseService::class);
        $component = new PhaseCard($phaseService, phaseContext: $context);

        $this->assertSame('準備フェーズ', $component->typeLabel());
        $this->assertSame('📋', $component->icon());
        $this->assertSame('text-indigo-600', $component->subStatusColorClass());
        $this->assertTrue($component->shouldShowPendingBadge());
        $this->assertSame(3, $component->pendingTaskCount());
        $this->assertSame('フェーズ1【準備段階】', $component->displayName());
        $this->assertSame('第1回総会 あと5日', $component->subStatus());
    }

    /**
     * 準備フェーズで未完了タスクが0件の場合に残タスクバッジが表示されないこと
     */
    public function test_preparation_phase_without_pending_tasks_hides_badge(): void
    {
        $context = new PhaseContext(
            phase: AnnualPhase::Planning,
            displayName: 'フェーズ2【計画段階】',
            subStatus: '直近の年間行事予定なし',
            isMainEventDay: false,
            pendingTaskCount: 0
        );

        $phaseService = Mockery::mock(PhaseService::class);
        $component = new PhaseCard($phaseService, phaseContext: $context);

        $this->assertFalse($component->shouldShowPendingBadge());
        $this->assertSame(0, $component->pendingTaskCount());
    }

    /**
     * 日時パラメータを指定してコンポーネントがサービスを呼び出すこと
     */
    public function test_component_delegates_to_phase_service_with_custom_now(): void
    {
        $simulatedNow = Carbon::parse('2026-08-10 10:00:00', 'Asia/Tokyo');
        $expectedContext = new PhaseContext(
            phase: AnnualPhase::Arrangement,
            displayName: 'フェーズ3【手配段階】',
            subStatus: '看板・横断幕発注',
            isMainEventDay: false
        );

        $phaseService = Mockery::mock(PhaseService::class);
        $phaseService->shouldReceive('getCurrentContext')
            ->once()
            ->with($simulatedNow)
            ->andReturn($expectedContext);

        $component = new PhaseCard($phaseService, now: $simulatedNow);

        $this->assertSame('フェーズ3【手配段階】', $component->displayName());
    }

    /**
     * Bladeテンプレートのレンダリングが正常に行われること
     */
    public function test_component_renders_blade_view_cleanly(): void
    {
        $context = new PhaseContext(
            phase: AnnualPhase::Arrangement,
            displayName: 'フェーズ3【手配段階】',
            subStatus: '看板・横断幕発注',
            isMainEventDay: false,
            pendingTaskCount: 2
        );

        $view = $this->component(PhaseCard::class, ['phaseContext' => $context]);

        $view->assertSee('準備フェーズ');
        $view->assertSee('フェーズ3【手配段階】');
        $view->assertSee('看板・横断幕発注');
        $view->assertSee('残2件');
        $view->assertSee('text-indigo-600');
    }
}
