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
        $this->assertStringContainsString("script-src 'self' https://static.line-scdn.net", $csp);
        $this->assertStringContainsString("style-src 'self' https://fonts.bunny.net", $csp);
        $this->assertStringNotContainsString('unsafe-inline', $csp);
        $this->assertStringNotContainsString('unsafe-eval', $csp);

        // 各種セキュリティヘッダーの検証
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }
}
