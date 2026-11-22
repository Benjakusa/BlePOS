<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class PlatformSessionCookieTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_and_tenant_routes_issue_distinct_session_cookies(): void
    {
        $platformCookie = config('session.platform.cookie');
        $tenantCookie = config('session.cookie');
        $this->assertNotSame($platformCookie, $tenantCookie);
        config(['session.platform.secure' => true]);

        $platformResponse = $this->get(route('platform.login'));
        $platformResponse->assertOk();
        $platformCookies = collect($platformResponse->headers->getCookies());
        $platformCookieNames = $platformCookies->map(fn (Cookie $cookie): string => $cookie->getName());
        $platformSessionCookie = $platformCookies->first(fn (Cookie $cookie): bool => $cookie->getName() === $platformCookie);

        $this->assertTrue($platformCookieNames->contains($platformCookie));
        $this->assertFalse($platformCookieNames->contains($tenantCookie));
        $this->assertNotNull($platformSessionCookie);
        $this->assertTrue($platformSessionCookie->isSecure());

        Route::middleware('web')->get('/__blepos_tenant_session_cookie_probe', fn () => response('ok'));
        $tenantResponse = $this->get('/__blepos_tenant_session_cookie_probe');
        $tenantResponse->assertOk();
        $tenantCookieNames = collect($tenantResponse->headers->getCookies())
            ->map(fn (Cookie $cookie): string => $cookie->getName());

        $this->assertTrue($tenantCookieNames->contains($tenantCookie));
        $this->assertFalse($tenantCookieNames->contains($platformCookie));
    }
}
