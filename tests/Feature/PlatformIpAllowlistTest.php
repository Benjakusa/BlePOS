<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PlatformIpAllowlistTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        config([
            'platform.security.ip_allowlist_enabled' => true,
            'platform.security.ip_allowlist' => ['203.0.113.0/24'],
        ]);
    }

    #[Test]
    public function platform_console_allows_a_client_inside_the_configured_network(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.42'])
            ->get(route('platform.login'))
            ->assertOk();
    }

    #[Test]
    public function platform_console_rejects_a_client_outside_the_configured_network_and_records_it(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.15'])
            ->get(route('platform.login'))
            ->assertForbidden();

        $this->assertDatabaseHas('platform_security_events', [
            'event_type' => 'platform.network.ip_denied',
            'severity' => 'warning',
            'ip_address' => '198.51.100.15',
        ]);
    }

    #[Test]
    public function server_cli_can_temporarily_override_the_allowlist_with_audited_expiry(): void
    {
        $this->artisan('platform:ip-allowlist-emergency-override', [
            '--minutes' => 20,
            '--reason' => 'Emergency administrator access during network incident',
        ])->assertSuccessful();

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.15'])
            ->get(route('platform.login'))
            ->assertOk();

        $this->assertDatabaseHas('platform_security_events', [
            'event_type' => 'platform.network.ip_allowlist_emergency_override',
            'severity' => 'critical',
        ]);
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.network.ip_allowlist_emergency_override',
        ]);
        $this->assertTrue(Cache::get(config('platform.security.ip_allowlist_override_cache_key')));
    }

    #[Test]
    public function emergency_override_requires_a_reason_and_is_limited_to_one_hour(): void
    {
        $exitCode = Artisan::call('platform:ip-allowlist-emergency-override', [
            '--minutes' => 120,
            '--reason' => 'short',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertFalse(Cache::has(config('platform.security.ip_allowlist_override_cache_key')));
        $this->assertDatabaseMissing('platform_audit_log', [
            'action' => 'platform.network.ip_allowlist_emergency_override',
        ]);
    }

    #[Test]
    public function allowlist_is_inactive_by_default_and_does_not_block_the_login_page(): void
    {
        config(['platform.security.ip_allowlist_enabled' => false]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.15'])
            ->get(route('platform.login'))
            ->assertOk();

        $this->assertDatabaseMissing('platform_security_events', ['event_type' => 'platform.network.ip_denied']);
    }
}
