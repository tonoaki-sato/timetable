<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetablePrintPdfTest extends TestCase
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
     * オペレーターが運行日別人員配置一覧表をPDFダウンロードできること
     */
    public function test_can_download_timetable_assignments_pdf(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create(['name' => '本祭 運行']);
        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'name' => '神輿誘導班',
        ]);
        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '南門警備',
            'required_people_count' => 3,
        ]);
        $staff = Staff::factory()->create(['name' => '警備 太郎']);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $staff->id,
            'role_description' => '責任者',
        ]);

        $response = $this->get(route('timetables.print-assignments', $timetable));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('assignments_', (string) $response->headers->get('Content-Disposition'));
        $this->assertStringContainsString('.pdf', (string) $response->headers->get('Content-Disposition'));
        // PDFバイナリ先頭のマジックバイト (%PDF-)
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }

    /**
     * プレビュー指定時はHTML画面が表示されること
     */
    public function test_can_view_html_preview(): void
    {
        $this->actingAs($this->operator);

        $timetable = Timetable::factory()->create(['name' => 'プレビュー対象運行']);
        $response = $this->get(route('timetables.print-assignments', ['timetable' => $timetable, 'preview' => 1]));

        $response->assertStatus(200);
        $response->assertSee('プレビュー対象運行');
        $response->assertSee('まつり運行 人員配置一覧表');
    }

    /**
     * ゲストユーザーはPDFダウンロード時にログイン画面へリダイレクトされること
     */
    public function test_guest_is_redirected_to_login_on_download_pdf(): void
    {
        $timetable = Timetable::factory()->create();

        $this->get(route('timetables.print-assignments', $timetable))
            ->assertRedirect(route('login'));
    }
}
