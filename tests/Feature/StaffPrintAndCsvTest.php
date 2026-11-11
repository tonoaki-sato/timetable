<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\StaffCheckIn;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffPrintAndCsvTest extends TestCase
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
     * オペレーターがスタッフ名札一括PDFダウンロードおよびプレビュー画面を表示できること
     */
    public function test_can_render_staff_cards_print_view(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create(['name' => '本祭']);

        // 一般スタッフ（所属あり）
        $staffA = Staff::factory()->create([
            'name' => '神輿 太郎',
            'kana' => 'ミコシ タロウ',
            'affiliation' => '神輿保存会',
            'is_active' => true,
        ]);

        // 班長スタッフ（2つの班の班長）
        $leaderStaff = Staff::factory()->create([
            'name' => '警備 隊長',
            'kana' => 'ケイビ タイチョウ',
            'affiliation' => '防犯部',
            'is_active' => true,
        ]);
        Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '誘導警備班',
            'lead_staff_id' => $leaderStaff->id,
        ]);
        Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '巡回班',
            'lead_staff_id' => $leaderStaff->id,
        ]);

        // 1. デフォルト: PDFダウンロード
        $response = $this->get(route('staff.print-cards'));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('staff_cards_', (string) $response->headers->get('Content-Disposition'));

        // 2. プレビュー指定時: HTML表示
        $previewResponse = $this->get(route('staff.print-cards', ['preview' => 1]));
        $previewResponse->assertStatus(200);

        // 新ヘッダーとフッターの検証
        $previewResponse->assertSee('保土ケ谷宿場まつり STAFF');
        $previewResponse->assertSee('発行: 保土ケ谷宿場まつり実行委員会');

        // 所属が削除され表示されないことの検証
        $previewResponse->assertDontSeeText('神輿保存会');
        $previewResponse->assertDontSeeText('防犯部');

        // 氏名・カナの検証
        $previewResponse->assertSee('神輿 太郎');
        $previewResponse->assertSee('ミコシ タロウ');
        $previewResponse->assertSee('警備 隊長');
        $previewResponse->assertSee('ケイビ タイチョウ');

        // QRコード画像 (PNG Data URI) の出力検証
        $previewResponse->assertSee('data:image/png;base64,', false);
        $previewResponse->assertSee('マイシフト確認');

        // 班名バッジ・班長色分けクラスは削除され表示されないことの検証
        $previewResponse->assertDontSee('header-leader');
        $previewResponse->assertDontSeeText('誘導警備班');
        $previewResponse->assertDontSeeText('巡回班');

        // 8名のスタッフ作成時にPDFが1ページ、16名作成時にPDFが2ページで正しく収まること（意図しない改ページがないこと）
        $pdfContent = $response->getContent();
        $this->assertNotEmpty($pdfContent);
    }

    /**
     * 8名で1ページ、16名で2ページに収まること（カードサイズ拡大による意図しない改ページが発生しないこと）
     */
    public function test_staff_cards_pdf_page_count(): void
    {
        $this->actingAs($this->operator);

        Staff::query()->delete();
        Staff::factory()->count(8)->create(['is_active' => true]);

        $response8 = $this->get(route('staff.print-cards'));
        $response8->assertStatus(200);
        $pdf8 = $response8->getContent();
        // PDF内の /Type /Page の数をカウント（ページオブジェクト）
        $pageCount8 = preg_match_all('/\/Type\s*\/Page\b/', $pdf8);
        $this->assertSame(1, $pageCount8, '8名のスタッフカードは1ページに収まる必要があります。');

        Staff::factory()->count(8)->create(['is_active' => true]);
        $response16 = $this->get(route('staff.print-cards'));
        $response16->assertStatus(200);
        $pdf16 = $response16->getContent();
        $pageCount16 = preg_match_all('/\/Type\s*\/Page\b/', $pdf16);
        $this->assertSame(2, $pageCount16, '16名のスタッフカードは2ページに収まる必要があります。');

        // 34名のスタッフ（複数班長・長名を含む）で5ページに綺麗に収まること
        Staff::factory()->count(18)->create(['is_active' => true]);
        $timetable = Timetable::first() ?? Timetable::factory()->create();
        $leadStaff = Staff::first();
        Lane::factory()->create(['timetable_id' => $timetable->id, 'name' => '宿場くん/宿場ちゃん', 'lead_staff_id' => $leadStaff->id]);
        Lane::factory()->create(['timetable_id' => $timetable->id, 'name' => 'ステカン', 'lead_staff_id' => $leadStaff->id]);

        $response34 = $this->get(route('staff.print-cards'));
        $response34->assertStatus(200);
        $pdf34 = $response34->getContent();
        $pageCount34 = preg_match_all('/\/Type\s*\/Page\b/', $pdf34);
        $this->assertSame(5, $pageCount34, '34名のスタッフカードは5ページに収まる必要があります。');
    }

    /**
     * 単一スタッフの名札PDFダウンロードおよびプレビュー画面が表示できること
     */
    public function test_can_render_single_staff_card_print_view(): void
    {
        $this->actingAs($this->operator);

        $staff = Staff::factory()->create([
            'name' => '個別 花子',
            'kana' => 'コベツ ハナコ',
            'affiliation' => 'ボランティア連盟',
        ]);

        // 1. デフォルト: PDFダウンロード
        $response = $this->get(route('staff.print-card', $staff));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('staff_card_', (string) $response->headers->get('Content-Disposition'));

        // 2. プレビュー指定時: HTML表示
        $previewResponse = $this->get(route('staff.print-card', ['staff' => $staff, 'preview' => 1]));
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('個別 花子');
        $previewResponse->assertSee('保土ケ谷宿場まつり STAFF');
        $previewResponse->assertSee('発行: 保土ケ谷宿場まつり実行委員会');
        $previewResponse->assertDontSeeText('ボランティア連盟');
        $previewResponse->assertSee('data:image/png;base64,', false);
    }

    /**
     * 運行日別人員配置一覧表のPDFダウンロードおよびプレビュー画面が表示できること
     */
    public function test_can_render_timetable_assignments_print_view(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create(['name' => '本祭 昼運行']);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '誘導警備班',
        ]);
        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '交差点A 交通遮断',
        ]);
        $staff = Staff::factory()->create(['name' => '警備 隊長']);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $staff->id,
            'role_description' => '合図担当',
        ]);

        // 1. デフォルト: PDFダウンロード
        $response = $this->get(route('timetables.print-assignments', $timetable));
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('assignments_', (string) $response->headers->get('Content-Disposition'));

        // 2. プレビュー指定時: HTML表示
        $previewResponse = $this->get(route('timetables.print-assignments', ['timetable' => $timetable, 'preview' => 1]));
        $previewResponse->assertStatus(200);
        $previewResponse->assertSee('本祭 昼運行');
        $previewResponse->assertSee('誘導警備班');
        $previewResponse->assertSee('交差点A 交通遮断');
        $previewResponse->assertSee('警備 隊長');
        $previewResponse->assertSee('合図担当');
    }

    /**
     * スタッフ名簿CSVをエクスポートできること
     */
    public function test_can_export_staff_csv(): void
    {
        $this->actingAs($this->operator);

        Staff::factory()->create([
            'name' => '名簿 太郎',
            'kana' => 'メイボ タロウ',
            'affiliation' => '青年部',
        ]);

        $response = $this->get(route('staff.export-csv'));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        // ストリーム出力の内容を検証
        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString('名簿 太郎', $content);
        $this->assertStringContainsString('メイボ タロウ', $content);
        $this->assertStringContainsString('青年部', $content);
    }

    /**
     * CSVエクスポート時に数式インジェクション文字がシングルクォートでエスケープされること
     */
    public function test_export_staff_csv_escapes_formula_injection(): void
    {
        $this->actingAs($this->operator);

        $staffWithFormula = Staff::factory()->create([
            'name' => '=SUM(1+1)',
            'kana' => '+KANA',
            'affiliation' => '@Admin',
        ]);
        $timetable = Timetable::factory()->create([
            'name' => '本祭1日目',
        ]);
        $checkedInAt = now()->setTime(8, 15, 0);
        StaffCheckIn::create([
            'timetable_id' => $timetable->id,
            'staff_id' => $staffWithFormula->id,
            'checked_in_at' => $checkedInAt,
        ]);

        $response = $this->get(route('staff.export-csv'));
        $response->assertStatus(200);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString("'=SUM(1+1)", $content);
        $this->assertStringContainsString("'+KANA", $content);
        $this->assertStringContainsString("'@Admin", $content);
        $this->assertStringContainsString('本祭1日目: '.$checkedInAt->format('Y-m-d H:i:s'), $content);
    }

    /**
     * 未ログインユーザーは印刷・CSVルートにアクセスできないこと
     */
    public function test_guest_cannot_access_print_or_csv_routes(): void
    {
        $staff = Staff::factory()->create();
        $timetable = Timetable::factory()->create();

        $this->get(route('staff.print-cards'))->assertRedirect(route('login'));
        $this->get(route('staff.print-card', $staff))->assertRedirect(route('login'));
        $this->get(route('timetables.print-assignments', $timetable))->assertRedirect(route('login'));
        $this->get(route('staff.export-csv'))->assertRedirect(route('login'));
    }
}
