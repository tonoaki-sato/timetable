<?php

namespace Tests\Feature;

use App\Models\Timetable;
use App\Models\TimetableLane;
use App\Models\User;
use App\Repositories\Contracts\TimetableRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableSelectorDefaultTest extends TestCase
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

    protected function tearDown(): void
    {
        Carbon::setTestNow(null);
        parent::tearDown();
    }

    /**
     * セレクタの選択肢は「準備中」「運行中」のみが時系列順で表示され、「運行完了」は除外されること
     */
    public function test_selector_displays_draft_and_active_timetables_in_chronological_order_and_excludes_completed(): void
    {
        $this->actingAs($this->operator);

        $d1 = Timetable::factory()->create([
            'name' => '事前準備日',
            'target_date' => '2026-10-10',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'draft',
        ]);

        $d2 = Timetable::factory()->create([
            'name' => '本祭1日目',
            'target_date' => '2026-10-11',
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'status' => 'active',
        ]);

        $d3 = Timetable::factory()->create([
            'name' => '本祭2日目',
            'target_date' => '2026-10-12',
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'status' => 'draft',
        ]);

        $completed = Timetable::factory()->create([
            'name' => '前月プレイベント',
            'target_date' => '2026-09-15',
            'start_time' => '10:00:00',
            'end_time' => '16:00:00',
            'status' => 'completed',
        ]);

        $response = $this->get(route('timetables.staff-timeline', $d2));
        $response->assertOk();

        $content = $response->getContent();

        // 完了した運行情報は選択肢に含まれないこと
        $response->assertDontSee('前月プレイベント');

        // 準備中・運行中の運行情報が表示されていること
        $response->assertSee('事前準備日');
        $response->assertSee('本祭1日目');
        $response->assertSee('本祭2日目');

        // 時系列昇順（10/10 -> 10/11 -> 10/12）にセレクタの選択肢が並んでいることを検証
        $selectStart = strpos($content, '<select data-action="navigate-on-change"');
        $this->assertNotFalse($selectStart);
        $selectEnd = strpos($content, '</select>', $selectStart);
        $this->assertNotFalse($selectEnd);
        $selectHtml = substr($content, $selectStart, $selectEnd - $selectStart);

        $posD1 = strpos($selectHtml, '事前準備日');
        $posD2 = strpos($selectHtml, '本祭1日目');
        $posD3 = strpos($selectHtml, '本祭2日目');

        $this->assertNotFalse($posD1);
        $this->assertNotFalse($posD2);
        $this->assertNotFalse($posD3);
        $this->assertTrue($posD1 < $posD2, '事前準備日は本祭1日目より前に表示されること');
        $this->assertTrue($posD2 < $posD3, '本祭1日目は本祭2日目より前に表示されること');

        // 厳格なCSP検証
        $this->assertStringNotContainsString('style="', $content);
        $this->assertStringNotContainsString('onclick="', $content);
    }

    /**
     * 未指定アクセス時（マトリクスや各種リダイレクト）は現在日時に最も近い運行情報がデフォルト選択されること
     */
    public function test_default_resolution_picks_closest_timetable_when_no_id_specified(): void
    {
        $this->actingAs($this->operator);

        $d1 = Timetable::factory()->create([
            'name' => '事前準備日',
            'target_date' => '2026-10-10',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'draft',
        ]);

        $d2 = Timetable::factory()->create([
            'name' => '本祭1日目',
            'target_date' => '2026-10-11',
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'status' => 'active',
        ]);

        $d3 = Timetable::factory()->create([
            'name' => '本祭2日目',
            'target_date' => '2026-10-12',
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'status' => 'draft',
        ]);

        TimetableLane::factory()->create(['timetable_id' => $d2->id]);

        // 現在日時を本祭1日目（2026-10-11 11:00:00）に固定
        Carbon::setTestNow('2026-10-11 11:00:00');

        // 1. /timetable （マトリクス）: 指定なしでアクセスすると本祭1日目がデフォルト表示される
        $matrixResponse = $this->get(route('timetable.index'));
        $matrixResponse->assertOk();
        $matrixResponse->assertSee('本祭1日目 進行表');

        // 2. /logistics-timeline
        $logisticsTimelineResponse = $this->get(route('logistics-timeline.index'));
        $logisticsTimelineResponse->assertRedirect(route('timetables.logistics-timeline', $d2));

        // 3. /timetable-lanes
        $lanesResponse = $this->get(route('timetable-lanes.index'));
        $lanesResponse->assertRedirect(route('timetables.lanes.index', $d2));

        // 4. /timetable-tasks
        $tasksResponse = $this->get(route('timetable-tasks.index'));
        $tasksResponse->assertRedirect(route('timetables.tasks.index', $d2));

        // 5. /assignments
        $assignmentsResponse = $this->get(route('assignments.index'));
        $assignmentsResponse->assertRedirect(route('timetables.assignments', $d2));

        // 6. /logistics
        $logisticsResponse = $this->get(route('logistics.index'));
        $logisticsResponse->assertRedirect(route('timetables.logistics.index', $d2));
    }

    /**
     * 運行中（active）がない場合でも、準備中（draft）の中で現在日時に最も近い運行情報が解決されること
     */
    public function test_default_resolution_picks_closest_draft_when_no_active_timetable_exists(): void
    {
        $d1 = Timetable::factory()->create([
            'name' => '昨日実施の準備日',
            'target_date' => '2026-10-10',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'draft',
        ]);

        $d2 = Timetable::factory()->create([
            'name' => '4日後実施の本祭',
            'target_date' => '2026-10-15',
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'status' => 'draft',
        ]);

        Timetable::factory()->create([
            'name' => '前月完了イベント',
            'target_date' => '2026-09-01',
            'start_time' => '08:00:00',
            'end_time' => '20:00:00',
            'status' => 'completed',
        ]);

        /** @var TimetableRepositoryInterface $repo */
        $repo = app(TimetableRepositoryInterface::class);

        // 2026-10-11 12:00（昨日終わったd1までの距離は19時間、4日後のd2までは約92時間）
        Carbon::setTestNow('2026-10-11 12:00:00');
        $resolved = $repo->findFirstActiveOrEarliest();
        $this->assertNotNull($resolved);
        $this->assertSame($d1->id, $resolved->id);

        // 2026-10-14 12:00（明日のd2までの距離は20時間、4日前のd1までは約91時間）
        Carbon::setTestNow('2026-10-14 12:00:00');
        $resolved2 = $repo->findFirstActiveOrEarliest();
        $this->assertNotNull($resolved2);
        $this->assertSame($d2->id, $resolved2->id);
    }

    /**
     * 過去の運行完了の運行情報画面を閲覧している場合、セレクタに閲覧専用で完了運行日が表示されること
     */
    public function test_viewing_completed_timetable_displays_read_only_completed_option_in_selector(): void
    {
        $this->actingAs($this->operator);

        $d1 = Timetable::factory()->create([
            'name' => '近未来運行日',
            'target_date' => '2026-10-15',
            'start_time' => '09:00:00',
            'end_time' => '17:00:00',
            'status' => 'draft',
        ]);

        $completed = Timetable::factory()->create([
            'name' => '完了した過去まつり',
            'target_date' => '2026-08-01',
            'start_time' => '10:00:00',
            'end_time' => '16:00:00',
            'status' => 'completed',
        ]);

        $response = $this->get(route('timetables.show', $completed));
        $response->assertOk();

        // 閲覧中の運行完了は disabled selected として描画されること
        $response->assertSee('完了した過去まつり (運行完了)');
        $response->assertSee('近未来運行日');
    }
}
