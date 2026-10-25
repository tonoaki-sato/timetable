<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
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
     * オペレーターがスタッフ名札一括印刷画面を表示できること
     */
    public function test_can_render_staff_cards_print_view(): void
    {
        $this->actingAs($this->operator);

        $staffA = Staff::factory()->create([
            'name' => '神輿 太郎',
            'kana' => 'ミコシ タロウ',
            'affiliation' => '神輿保存会',
            'is_active' => true,
        ]);

        $response = $this->get(route('staff.print-cards'));

        $response->assertStatus(200);
        $response->assertSee('神輿 太郎');
        $response->assertSee('ミコシ タロウ');
        $response->assertSee('神輿保存会');
        $response->assertSee('<svg', false);
    }

    /**
     * 単一スタッフの名札印刷画面が表示できること
     */
    public function test_can_render_single_staff_card_print_view(): void
    {
        $this->actingAs($this->operator);

        $staff = Staff::factory()->create([
            'name' => '個別 花子',
            'kana' => 'コベツ ハナコ',
        ]);

        $response = $this->get(route('staff.print-card', $staff));

        $response->assertStatus(200);
        $response->assertSee('個別 花子');
        $response->assertSee('<svg', false);
    }

    /**
     * 運行日別人員配置一覧表の印刷画面が表示できること
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

        $response = $this->get(route('timetables.print-assignments', $timetable));

        $response->assertStatus(200);
        $response->assertSee('本祭 昼運行');
        $response->assertSee('誘導警備班');
        $response->assertSee('交差点A 交通遮断');
        $response->assertSee('警備 隊長');
        $response->assertSee('合図担当');
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

        Staff::factory()->create([
            'name' => '=SUM(1+1)',
            'kana' => '+KANA',
            'affiliation' => '@Admin',
            'line_display_name' => '-Minus',
        ]);

        $response = $this->get(route('staff.export-csv'));
        $response->assertStatus(200);

        ob_start();
        $response->sendContent();
        $content = ob_get_clean();

        $this->assertStringContainsString("'=SUM(1+1)", $content);
        $this->assertStringContainsString("'+KANA", $content);
        $this->assertStringContainsString("'@Admin", $content);
        $this->assertStringContainsString("'-Minus", $content);
    }

    /**
     * CSVファイルをアップロードしてスタッフを一括インポートできること
     */
    public function test_can_import_staff_csv(): void
    {
        $this->actingAs($this->operator);

        // 既存スタッフ（所属が更新されることを検証）
        $existingStaff = Staff::factory()->create([
            'name' => '既存 太郎',
            'kana' => 'キゾン タロウ',
            'affiliation' => '旧所属',
        ]);

        $csvContent = implode("\n", [
            '氏名,フリガナ,所属,LINE表示名',
            '新着 次郎,シンチャク ジロウ,新町子供会,しんちゃん',
            '既存 太郎,キゾン タロウ,新町青年部,きぞん',
        ]);

        $file = UploadedFile::fake()->createWithContent('staff.csv', $csvContent);

        $response = $this->post(route('staff.import-csv'), [
            'file' => $file,
        ]);

        $response->assertRedirect(route('staff.index'));
        $response->assertSessionHas('success');

        // 新規登録確認
        $this->assertDatabaseHas('staff', [
            'name' => '新着 次郎',
            'kana' => 'シンチャク ジロウ',
            'affiliation' => '新町子供会',
            'line_display_name' => 'しんちゃん',
            'registered_via' => 'admin',
        ]);

        // 既存更新確認
        $existingStaff->refresh();
        $this->assertSame('新町青年部', $existingStaff->affiliation);
        $this->assertSame('きぞん', $existingStaff->line_display_name);
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
        $this->post(route('staff.import-csv'))->assertRedirect(route('login'));
    }
}
