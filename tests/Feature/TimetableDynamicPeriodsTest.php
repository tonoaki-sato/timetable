<?php

namespace Tests\Feature;

use App\Models\Timetable;
use App\Models\User;
use App\Services\TrafficRegulationService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableDynamicPeriodsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * 時間帯指定された道路状況・まつり状況がマトリクス帳票スロットに正しく反映されること
     * かつ表示文言（通行止め、ござ市受付開始、開催中）が正しいこと
     */
    public function test_matrix_displays_dynamic_road_and_festival_periods(): void
    {
        $this->actingAs($this->admin);

        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-17',
            'name' => '本祭運行テスト',
            'start_time' => '07:00:00',
            'end_time' => '19:30:00',
            'status' => 'active',
            'road_periods' => [
                ['start_time' => '10:00', 'end_time' => '18:00', 'label' => '通行止め', 'status' => 'closed'],
            ],
            'festival_periods' => [
                ['start_time' => '09:00', 'end_time' => '11:00', 'label' => 'ござ市受付開始', 'status' => 'active'],
                ['start_time' => '11:00', 'end_time' => '17:00', 'label' => '開催中', 'status' => 'active'],
            ],
        ]);

        $response = $this->get(route('timetable.index', ['timetable_id' => $timetable->id]));

        $response->assertStatus(200);

        // 道路状況の表示検証（通行止め、既存デザインのCSSクラスbg-rose-100が含まれること）
        $response->assertSee('通行止め');
        $response->assertSee('bg-rose-100 text-rose-800 font-bold', false);

        // まつり状況の表示検証（ござ市受付開始、開催中、既存デザインのCSSクラスbg-emerald-100が含まれること）
        $response->assertSee('ござ市受付開始');
        $response->assertSee('開催中');
        $response->assertSee('bg-emerald-100 text-emerald-800 font-bold', false);
    }

    /**
     * まつり当日以外（時間帯未設定）のタイムテーブルでは、道路・まつり列に「通行止め」や「開催中」が表示されないこと
     */
    public function test_matrix_renders_no_regulations_for_non_event_timetables(): void
    {
        $this->actingAs($this->admin);

        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-10',
            'name' => '前日準備テスト',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'draft',
            'road_periods' => null,
            'festival_periods' => null,
        ]);

        $response = $this->get(route('timetable.index', ['timetable_id' => $timetable->id]));

        $response->assertStatus(200);
        // 通行止め用（bg-rose-100）・開催中用（bg-emerald-100）のスタイルクラスが描画されないこと
        $response->assertDontSee('bg-rose-100 text-rose-800 font-bold', false);
        $response->assertDontSee('bg-emerald-100 text-emerald-800 font-bold', false);
        $response->assertDontSee('ござ市受付開始');
    }

    /**
     * タイムテーブル新規登録時に道路・まつりの時間帯指定が正常に保存されること
     */
    public function test_store_timetable_saves_road_and_festival_periods(): void
    {
        $this->actingAs($this->admin);

        $payload = [
            'target_date' => '2026-10-20',
            'name' => '新規登録テスト運行日',
            'start_time' => '08:00',
            'end_time' => '19:00',
            'status' => 'draft',
            'description' => 'テスト用説明',
            'road_periods' => [
                [
                    'start_time' => '10:00',
                    'end_time' => '18:00',
                    'label' => '通行止め',
                    'status' => 'closed',
                ],
            ],
            'festival_periods' => [
                [
                    'start_time' => '09:00',
                    'end_time' => '11:00',
                    'label' => 'ござ市受付開始',
                    'status' => 'active',
                ],
                [
                    'start_time' => '11:00',
                    'end_time' => '17:00',
                    'label' => '開催中',
                    'status' => 'active',
                ],
            ],
        ];

        $response = $this->post(route('timetables.store'), $payload);

        $timetable = Timetable::where('target_date', '2026-10-20')->firstOrFail();
        $response->assertRedirect(route('timetables.lanes.index', $timetable));

        $this->assertCount(1, $timetable->road_periods);
        $this->assertSame('10:00', $timetable->road_periods[0]['start_time']);
        $this->assertSame('18:00', $timetable->road_periods[0]['end_time']);
        $this->assertSame('通行止め', $timetable->road_periods[0]['label']);

        $this->assertCount(2, $timetable->festival_periods);
        $this->assertSame('ござ市受付開始', $timetable->festival_periods[0]['label']);
        $this->assertSame('開催中', $timetable->festival_periods[1]['label']);
    }

    /**
     * タイムテーブル更新時に道路・まつりの時間帯指定が更新されること
     */
    public function test_update_timetable_modifies_periods(): void
    {
        $this->actingAs($this->admin);

        $timetable = Timetable::factory()->create([
            'target_date' => '2026-10-21',
            'name' => '更新前運行日',
            'start_time' => '08:00:00',
            'end_time' => '19:00:00',
            'road_periods' => [
                ['start_time' => '10:00', 'end_time' => '18:00', 'label' => '通行止め', 'status' => 'closed'],
            ],
        ]);

        $payload = [
            'target_date' => '2026-10-21',
            'name' => '更新後運行日',
            'start_time' => '08:00',
            'end_time' => '19:00',
            'status' => 'active',
            'road_periods' => [
                ['start_time' => '12:00', 'end_time' => '16:00', 'label' => '午後のみ通行止め', 'status' => 'closed'],
            ],
            'festival_periods' => null,
        ];

        $response = $this->put(route('timetables.update', $timetable), $payload);
        $response->assertRedirect(route('timetables.show', $timetable));

        $timetable->refresh();
        $this->assertSame('更新後運行日', $timetable->name);
        $this->assertCount(1, $timetable->road_periods);
        $this->assertSame('午後のみ通行止め', $timetable->road_periods[0]['label']);
        $this->assertSame('12:00', $timetable->road_periods[0]['start_time']);
        $this->assertNull($timetable->festival_periods);
    }

    /**
     * 道路通行止め未設定の運行日の場合、ダッシュボードの道路交通規制が「一般車両通行中」となること
     */
    public function test_dashboard_traffic_regulation_service_handles_unrestricted_timetable(): void
    {
        $now = Carbon::parse('2026-10-15 12:00:00', 'Asia/Tokyo');

        // 通行止め設定なしの運行日（準備日等）
        Timetable::factory()->create([
            'target_date' => '2026-10-15',
            'name' => '前日準備日',
            'start_time' => '08:00:00',
            'end_time' => '18:00:00',
            'road_periods' => null,
        ]);

        $service = app(TrafficRegulationService::class);
        $context = $service->getCurrentContext($now);

        $this->assertFalse($context->isRoadClosed);
        $this->assertFalse($context->isAlertActive);
        $this->assertStringContainsString('一般車両通行中', $context->subStatus);
    }
}
