<?php

namespace Tests\Unit;

use App\DTOs\PhaseContext;
use App\Enums\AnnualPhase;
use App\Models\AnnualSchedule;
use App\Models\Timetable;
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
        $this->assertNotEmpty($component->subStatus());
        $this->assertTrue(is_null($component->actionUrl()) || is_string($component->actionUrl()));
    }

    /**
     * 本番運行日モード時に生データから動的に正しい表示文字列が生成されること
     */
    public function test_main_event_day_dynamic_formatting(): void
    {
        $timetable = Timetable::factory()->make([
            'id' => 10,
            'name' => '1日目 (Day 1) 本祭運行',
        ]);

        $context = new PhaseContext(
            phase: AnnualPhase::Execution,
            isMainEventDay: true,
            activeTimetable: $timetable,
            pendingTaskCount: 0,
            actionUrl: 'http://localhost/timetable'
        );

        $phaseService = Mockery::mock(PhaseService::class);
        $component = new PhaseCard($phaseService, phaseContext: $context);

        $this->assertSame('運行フェーズ', $component->typeLabel());
        $this->assertSame('🏮', $component->icon());
        $this->assertSame('text-emerald-600', $component->subStatusColorClass());
        $this->assertFalse($component->shouldShowPendingBadge());
        $this->assertSame('1日目 (Day 1) 本祭運行', $component->displayName());
        $this->assertSame('本番運行セッション進行中', $component->subStatus());
        $this->assertSame('http://localhost/timetable', $component->actionUrl());
    }

    /**
     * 準備フェーズで本日開催タスクがある場合に「本日: ○○」とフォーマットされること
     */
    public function test_preparation_phase_today_task_formatting(): void
    {
        $now = Carbon::parse('2026-05-15 10:00:00', 'Asia/Tokyo');
        $task = AnnualSchedule::factory()->make([
            'title' => '第1回実行委員会',
            'start_date' => Carbon::parse('2026-05-15', 'Asia/Tokyo'),
        ]);

        $context = new PhaseContext(
            phase: AnnualPhase::Planning,
            isMainEventDay: false,
            nextTask: $task,
            pendingTaskCount: 2,
            actionUrl: 'http://localhost/annual-schedules'
        );

        $phaseService = Mockery::mock(PhaseService::class);
        $component = new PhaseCard($phaseService, now: $now, phaseContext: $context);

        $this->assertSame('準備フェーズ', $component->typeLabel());
        $this->assertSame('フェーズ2【計画段階】', $component->displayName());
        $this->assertSame('本日: 第1回実行委員会', $component->subStatus());
        $this->assertTrue($component->shouldShowPendingBadge());
        $this->assertSame(2, $component->pendingTaskCount());
    }

    /**
     * 準備フェーズで未来開催タスクがある場合に「直近: 5/20 ○○ (あと5日)」とフォーマットされること
     */
    public function test_preparation_phase_future_task_formatting(): void
    {
        $now = Carbon::parse('2026-05-15 10:00:00', 'Asia/Tokyo');
        $task = AnnualSchedule::factory()->make([
            'title' => '第3回幹事会・予算案確定',
            'start_date' => Carbon::parse('2026-05-20', 'Asia/Tokyo'),
        ]);

        $context = new PhaseContext(
            phase: AnnualPhase::Planning,
            isMainEventDay: false,
            nextTask: $task,
            pendingTaskCount: 3,
            actionUrl: 'http://localhost/annual-schedules'
        );

        $phaseService = Mockery::mock(PhaseService::class);
        $component = new PhaseCard($phaseService, now: $now, phaseContext: $context);

        $this->assertSame('準備フェーズ', $component->typeLabel());
        $this->assertSame('フェーズ2【計画段階】', $component->displayName());
        $this->assertSame('直近: 5/20 第3回幹事会・予算案確定 (あと5日)', $component->subStatus());
        $this->assertSame('text-indigo-600', $component->subStatusColorClass());
        $this->assertSame('📝', $component->icon());
    }

    /**
     * 準備フェーズでタスクが0件の場合にフォールバック文言が表示されること
     */
    public function test_preparation_phase_empty_tasks_formatting(): void
    {
        $context = new PhaseContext(
            phase: AnnualPhase::Arrangement,
            isMainEventDay: false,
            nextTask: null,
            pendingTaskCount: 0
        );

        $phaseService = Mockery::mock(PhaseService::class);
        $component = new PhaseCard($phaseService, phaseContext: $context);

        $this->assertSame('フェーズ3【準備段階】', $component->displayName());
        $this->assertSame('直近の年間行事予定なし', $component->subStatus());
        $this->assertFalse($component->shouldShowPendingBadge());
        $this->assertSame(0, $component->pendingTaskCount());
    }

    /**
     * DTO に明示的に文字列が渡された場合は動的フォーマットより優先されること（後方互換性）
     */
    public function test_explicit_context_strings_override_dynamic_formatting(): void
    {
        $context = new PhaseContext(
            phase: AnnualPhase::Execution,
            isMainEventDay: true,
            displayName: 'カスタム表示名',
            subStatus: 'カスタムサブステータス'
        );

        $phaseService = Mockery::mock(PhaseService::class);
        $component = new PhaseCard($phaseService, phaseContext: $context);

        $this->assertSame('カスタム表示名', $component->displayName());
        $this->assertSame('カスタムサブステータス', $component->subStatus());
    }

    /**
     * 日時パラメータを指定してコンポーネントがサービスを呼び出すこと
     */
    public function test_component_delegates_to_phase_service_with_custom_now(): void
    {
        $simulatedNow = Carbon::parse('2026-08-10 10:00:00', 'Asia/Tokyo');
        $expectedContext = new PhaseContext(
            phase: AnnualPhase::Arrangement,
            isMainEventDay: false
        );

        $phaseService = Mockery::mock(PhaseService::class);
        $phaseService->shouldReceive('getCurrentContext')
            ->once()
            ->with($simulatedNow)
            ->andReturn($expectedContext);

        $component = new PhaseCard($phaseService, now: $simulatedNow);

        $this->assertSame('フェーズ3【準備段階】', $component->displayName());
    }

    /**
     * Bladeテンプレートのレンダリングが正常に行われること
     */
    public function test_component_renders_blade_view_cleanly(): void
    {
        $now = Carbon::parse('2026-06-01 10:00:00', 'Asia/Tokyo');
        $task = AnnualSchedule::factory()->make([
            'title' => '看板・横断幕発注',
            'start_date' => Carbon::parse('2026-06-10', 'Asia/Tokyo'),
        ]);

        $context = new PhaseContext(
            phase: AnnualPhase::Arrangement,
            isMainEventDay: false,
            nextTask: $task,
            pendingTaskCount: 2
        );

        $view = $this->component(PhaseCard::class, ['now' => $now, 'phaseContext' => $context]);

        $view->assertSee('準備フェーズ');
        $view->assertSee('フェーズ3【準備段階】');
        $view->assertSee('直近: 6/10 看板・横断幕発注 (あと9日)');
        $view->assertSee('残2件');
        $view->assertSee('text-indigo-600');
    }
}
