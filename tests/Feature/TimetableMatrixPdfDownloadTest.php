<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableMatrixPdfDownloadTest extends TestCase
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
     * 未認証ユーザーは進行表PDFダウンロード時にログイン画面へリダイレクトされること
     */
    public function test_unauthenticated_user_cannot_download_matrix_pdf(): void
    {
        $timetable = Timetable::factory()->create();

        $this->get(route('timetables.print-matrix', $timetable))
            ->assertRedirect(route('login'));
    }

    /**
     * オペレーターが選択中運行日の進行表PDFをダウンロードできること
     */
    public function test_operator_can_download_matrix_pdf_for_selected_timetable(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
            'start_time' => '07:00:00',
            'end_time' => '19:30:00',
        ]);

        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '衛生',
            'display_order' => 10,
        ]);

        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => 'ゴミ箱配置',
            'start_time' => '10:00:00',
            'end_time' => '11:00:00',
            'required_people_count' => 4,
        ]);

        $response = $this->get(route('timetables.print-matrix', $timetable));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('timetable_matrix_', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /**
     * 画面で選択された表示班（lane_ids）のみがPDF内に出力され、除外された班は出力されないこと
     */
    public function test_matrix_pdf_respects_lane_ids_filter(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
        ]);

        $laneA = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '衛生',
            'display_order' => 10,
        ]);

        $laneB = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => 'カラーコーン',
            'display_order' => 20,
        ]);

        $laneC = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '除外班テスト',
            'display_order' => 30,
        ]);

        // laneA, laneB のみを指定してプレビュー確認
        $response = $this->get(route('timetables.print-matrix', [
            'timetable' => $timetable,
            'lane_ids' => "{$laneA->id},{$laneB->id}",
            'preview' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('衛生');
        $response->assertSee('カラーコーン');
        $response->assertDontSee('除外班テスト');
    }

    /**
     * 通行止めおよび開催中ステータスが正しくレンダリングされること
     */
    public function test_matrix_pdf_renders_road_and_festival_status_correctly(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
            'start_time' => '07:00:00',
            'end_time' => '12:00:00',
            'road_periods' => [
                [
                    'label' => '通行止め',
                    'status' => 'closed',
                    'start_time' => '10:00',
                    'end_time' => '12:00',
                ],
            ],
            'festival_periods' => [
                [
                    'label' => '開催中',
                    'status' => 'active',
                    'start_time' => '11:00',
                    'end_time' => '12:00',
                ],
            ],
        ]);

        $response = $this->get(route('timetables.print-matrix', [
            'timetable' => $timetable,
            'preview' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('通行止め');
        $response->assertSee('開催中');
        $response->assertSee('cell-road-closed', false);
        $response->assertSee('cell-festival-active', false);
    }

    /**
     * 複数スロットにまたがるタスクが適切な rowspan で描画されること
     */
    public function test_matrix_pdf_renders_tasks_with_proper_rowspan(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
            'start_time' => '07:00:00',
            'end_time' => '10:00:00',
        ]);

        $leadStaff = Staff::factory()->create(['name' => '有江 喜一郎']);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '衛生',
            'lead_staff_id' => $leadStaff->id,
            'display_order' => 10,
        ]);

        // 7:30〜9:00 のタスク (3スロット分: rowspan="3")
        TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '事前準備',
            'start_time' => '07:30:00',
            'end_time' => '09:00:00',
            'required_people_count' => 4,
            'is_at_least' => true,
            'content' => "① 名札設置\n② 袋設置",
        ]);

        $response = $this->get(route('timetables.print-matrix', [
            'timetable' => $timetable,
            'preview' => 1,
        ]));

        $response->assertStatus(200);
        $response->assertSee('有江さん');
        $response->assertSee('事前準備');
        $response->assertSee('【4名以上】');
        $response->assertSee('① 名札設置');
        $response->assertSee('② 袋設置');
        $response->assertSee('rowspan="3"', false);
    }

    /**
     * 進行表画面（/timetable）のヘッダーが共通 x-page-header であり、タイトルが「{タイムテーブル名} 進行表」、
     * バッジが運行日のみ、説明文が「進行表」、パンくずが「運行情報一覧」となっていること
     */
    public function test_matrix_page_has_unified_page_header_and_breadcrumb(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create([
            'name' => '宿場まつり 1日目',
            'target_date' => '2026-10-11',
            'start_time' => '07:00:00',
            'end_time' => '19:30:00',
            'status' => 'active',
        ]);

        $response = $this->get(route('timetable.index', ['timetable_id' => $timetable->id]));

        $response->assertStatus(200);

        // タイトル
        $response->assertSee('宿場まつり 1日目 進行表');

        // パンくずリスト
        $response->assertSee('運行情報一覧');
        $response->assertDontSee('運行情報管理');

        // バッジ: 運行日のみ
        $response->assertSee('2026年10月11日');

        // 説明文: 「当日タイムテーブル」を含まず「進行表」と表記
        $response->assertSee('「宿場まつり 1日目」の進行表および担当班タスク配置です。');
        $response->assertDontSee('当日タイムテーブル（進行表）のリアルタイム進行状況');

        // ボタン
        $response->assertSee('進行表PDF');
        $response->assertSee('btn-download-matrix-pdf', false);

        // 既存の黒背景ヘッダー（旧ヘッダー枠）が存在しないこと
        $response->assertDontSee('border border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between gap-2.5', false);
    }
}
