<?php

namespace Tests\Feature;

use App\Models\Lane;
use App\Models\LaneRequest;
use App\Models\Location;
use App\Models\Staff;
use App\Models\TaskStaffAssignment;
use App\Models\Timetable;
use App\Models\TimetableTask;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * WebリクエストのレスポンスにCSPヘッダーが付与され、セキュアなNonceを含み、'unsafe-inline' や 'unsafe-eval' が含まれず厳格に保護されていること
     */
    public function test_csp_header_is_strictly_enforced_without_unsafe_inline_or_eval(): void
    {
        $user = User::factory()->create([
            'role' => 'operator',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertStatus(200);
        $csp = $response->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("script-src 'self' 'nonce-", $csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[a-zA-Z0-9_\-]+'/", $csp);
        $this->assertStringNotContainsString('static.line-scdn.net', $csp);
        $this->assertStringNotContainsString('access.line.me', $csp);
        $this->assertStringContainsString("style-src 'self' https://fonts.bunny.net", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);

        // 各種セキュリティヘッダーの検証
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    /**
     * 主要Web画面およびマイシフト画面のHTML本文にCSP違反となるインラインイベントハンドラやスタイルが含まれていないこと
     */
    public function test_web_routes_have_no_inline_event_handlers_violating_csp(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $staff = Staff::factory()->create([
            'is_active' => true,
        ]);

        $timetable = Timetable::factory()->create();
        $lane = Lane::factory()->create(['timetable_id' => $timetable->id]);
        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'required_people_count' => 2,
        ]);
        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $staff->id,
        ]);
        $location = Location::factory()->create([
            'timetable_id' => $timetable->id,
        ]);

        $routesToTest = [
            route('dashboard'),
            route('manual.index'),
            route('calendar.index'),
            route('schedules.index'),
            route('timetables.index'),
            route('timetables.assignments', $timetable),
            route('timetable.index', ['timetable_id' => $timetable->id]),
            route('timetables.tasks.index', $timetable),
            route('timetables.lanes.index', $timetable),
            route('timetables.lanes.create', $timetable),
            route('timetables.lanes.show', [$timetable, $lane]),
            route('timetables.lanes.edit', [$timetable, $lane]),
            route('timetables.locations.index', $timetable),
            route('timetables.locations.create', $timetable),
            route('timetables.locations.show', [$timetable, $location]),
            route('timetables.locations.edit', [$timetable, $location]),
            route('timetables.logistics.index', $timetable),
            route('timetables.staff-timeline', $timetable),
            route('staff.index'),
            route('staff.create'),
            route('staff.show', $staff),
            route('staff.myshift', ['token' => $staff->access_token]),
            route('staff.join'),
            route('users.index'),
            route('presentation.index'),
            route('equipments.index'),
            route('organizations.index'),
            route('organizations.print'),
        ];

        foreach ($routesToTest as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertStatus(200);

            $content = $response->getContent();

            // <script> (src属性またはnonce属性のない未保護インラインスクリプト) が含まれていないこと
            $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*(?:\bsrc=|\bnonce=))[^>]*>/i', $content, "Route {$url} has un-nonced inline script tags");

            // インラインイベントハンドラ (onclick, onchange, onload等) が含まれていないこと
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $content, "Route {$url} has inline event handler");

            // インラインスタイル (style="...") が含まれていないこと
            $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $content, "Route {$url} has inline style attribute");

            // javascript: 疑似プロトコルが含まれていないこと
            $this->assertDoesNotMatchRegularExpression('/href=["\']javascript:/i', $content, "Route {$url} has javascript: pseudo protocol");
        }

        // セルフ登録完了画面（セッション保持時）のCSP検証
        $completedResponse = $this->withSession(['registered_staff_id' => $staff->id])->get(route('staff.join.completed'));
        $completedResponse->assertStatus(200);
        $completedContent = $completedResponse->getContent();
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*(?:\bsrc=|\bnonce=))[^>]*>/i', $completedContent);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $completedContent);
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $completedContent);
    }

    /**
     * マイシフト画面がすべての状態（チェックイン前後・班長コンソール・タスク配置時）においてCSPに完全準拠していること
     */
    public function test_myshift_page_strictly_complies_with_csp_under_all_states(): void
    {
        $timetable = Timetable::factory()->create([
            'target_date' => now()->format('Y-m-d'),
        ]);

        $leadStaff = Staff::factory()->create([
            'name' => '班長 スタッフ',
            'is_active' => true,
        ]);

        $lane = Lane::factory()->create([
            'timetable_id' => $timetable->id,
            'lead_staff_id' => $leadStaff->id,
            'name' => '設営第一班',
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'title' => '本部テント設営',
            'start_time' => now()->subMinute()->format('H:i:s'),
            'end_time' => now()->addHour()->format('H:i:s'),
            'content' => 'テントフレーム組み立ておよびウェイト固定。',
        ]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $leadStaff->id,
            'attendance_status' => 'not_started',
        ]);

        LaneRequest::create([
            'timetable_id' => $timetable->id,
            'lane_id' => $lane->id,
            'timetable_task_id' => $task->id,
            'staff_id' => $leadStaff->id,
            'request_type' => 'staffing',
            'requested_count' => 2,
            'status' => 'pending',
            'reason' => '急遽人員が必要',
        ]);

        $response = $this->get(route('staff.myshift', ['token' => $leadStaff->access_token]));
        $response->assertStatus(200);

        $csp = $response->headers->get('Content-Security-Policy');
        $this->assertNotNull($csp);
        $this->assertMatchesRegularExpression("/script-src 'self' 'nonce-[a-zA-Z0-9_\-]+'/", $csp);

        $content = $response->getContent();

        // CSP違反要素の不在検証
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*(?:\bsrc=|\bnonce=))[^>]*>/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/href=["\']javascript:/i', $content);

        // connect-src 'self' 遵守のため、非同期エンドポイント属性がルート相対パスであること
        $this->assertStringContainsString('data-endpoint="/my/shift/', $content);
        $this->assertStringNotContainsString('data-endpoint="http://', $content);
        $this->assertStringNotContainsString('data-endpoint="https://', $content);
    }

    /**
     * 運行日前の閲覧専用モード時におけるマイシフト画面もCSPに準拠していること
     */
    public function test_myshift_read_only_before_operation_day_complies_with_csp(): void
    {
        $futureTimetable = Timetable::factory()->create([
            'target_date' => now()->addDays(5)->format('Y-m-d'),
            'status' => 'draft',
        ]);

        $leadStaff = Staff::factory()->create([
            'is_active' => true,
        ]);

        $lane = Lane::factory()->create([
            'timetable_id' => $futureTimetable->id,
            'lead_staff_id' => $leadStaff->id,
        ]);

        $task = TimetableTask::factory()->create([
            'timetable_id' => $futureTimetable->id,
            'lane_id' => $lane->id,
        ]);

        TaskStaffAssignment::factory()->create([
            'timetable_task_id' => $task->id,
            'staff_id' => $leadStaff->id,
        ]);

        $response = $this->get(route('staff.myshift', ['token' => $leadStaff->access_token]));
        $response->assertStatus(200);
        $response->assertSee('id="readOnlyHeaderBadge"', false);
        $response->assertSee('id="myshiftReadOnlyBanner"', false);

        $content = $response->getContent();
        $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*(?:\bsrc=|\bnonce=))[^>]*>/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/\sstyle=/i', $content);
        $this->assertDoesNotMatchRegularExpression('/href=["\']javascript:/i', $content);
    }
}
