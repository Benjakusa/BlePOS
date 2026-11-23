<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformTransportSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_pages_include_security_headers_and_a_nonce_bound_content_policy(): void
    {
        $response = $this->get('/platform-console/login');

        $response->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'DENY')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()')
            ->assertHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $this->assertMatchesRegularExpression(
            "/script-src 'self' 'nonce-[A-Za-z0-9+\\/=]+'/",
            (string) $response->headers->get('Content-Security-Policy'),
        );
    }

    public function test_platform_console_redirects_http_to_https_when_enforcement_is_enabled(): void
    {
        config(['platform.security.force_https' => true]);

        $response = $this->get('/platform-console/login');

        $response->assertPermanentRedirect('https://localhost/platform-console/login');
    }

    public function test_untrusted_forwarded_proto_does_not_bypass_https_enforcement(): void
    {
        config(['platform.security.force_https' => true]);

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.50',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get('/platform-console/login');

        $response->assertPermanentRedirect('https://localhost/platform-console/login');
    }

    public function test_trusted_proxy_https_gets_production_hsts_without_redirecting(): void
    {
        config([
            'platform.security.force_https' => true,
            'trustedproxy.proxies' => ['203.0.113.50'],
        ]);
        app()->detectEnvironment(fn (): string => 'production');

        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '203.0.113.50',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get('/platform-console/login');
        app()->detectEnvironment(fn (): string => 'testing');

        $response->assertOk()
            ->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }
}
