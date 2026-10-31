<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSecurityPolicyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * WebリクエストのレスポンスにCSPヘッダーが付与され、'unsafe-inline' や 'unsafe-eval' が含まれず厳格に保護されていること
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
        $this->assertStringContainsString("script-src 'self'", $csp);
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
     * 主要Web画面のHTML本文にCSP違反となるインラインイベントハンドラが含まれていないこと
     */
    public function test_web_routes_have_no_inline_event_handlers_violating_csp(): void
    {
        $user = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $routesToTest = [
            route('dashboard'),
            route('manual.index'),
            route('calendar.index'),
            route('annual-schedules.index'),
            route('meetings.index'),
            route('timetables.index'),
            route('staff.index'),
            route('users.index'),
            route('presentation.index'),
            route('equipments.index'),
            route('locations.index'),
        ];

        foreach ($routesToTest as $url) {
            $response = $this->actingAs($user)->get($url);
            $response->assertStatus(200);

            $content = $response->getContent();

            // <script> (src属性のないインラインスクリプト) が含まれていないこと
            $this->assertDoesNotMatchRegularExpression('/<script(?![^>]*\bsrc=)[^>]*>/i', $content, "Route {$url} has inline script tags");

            // インラインイベントハンドラ (onclick, onchange, onload等) が含まれていないこと
            $this->assertDoesNotMatchRegularExpression('/\son[a-z]+=/i', $content, "Route {$url} has inline event handler");
        }
    }
}
