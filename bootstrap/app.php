<?php

use App\Http\Middleware\EnforcePlatformHttps;
use App\Http\Middleware\EnforcePlatformIpAllowlist;
use App\Http\Middleware\EnforceShopIsolation;
use App\Http\Middleware\EnforceTenantIsolation;
use App\Http\Middleware\EnsurePlatformMfa;
use App\Http\Middleware\EnsurePlatformPermission;
use App\Http\Middleware\EnsurePlatformStepUp;
use App\Http\Middleware\PlatformSecurityHeaders;
use App\Http\Middleware\ResolveTenantContext;
use App\Http\Middleware\StartBleposSession;
use App\Http\Middleware\TrackPlatformSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(replace: [
            StartSession::class => StartBleposSession::class,
        ]);
        $middleware->trustProxies(
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_HOST
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO
                | Request::HEADER_X_FORWARDED_PREFIX,
        );
        $middleware->redirectGuestsTo(fn () => route('platform.login'));
        $middleware->alias([
            'platform.mfa' => EnsurePlatformMfa::class,
            'platform.session' => TrackPlatformSession::class,
            'platform.permission' => EnsurePlatformPermission::class,
            'platform.step-up' => EnsurePlatformStepUp::class,
            'platform.headers' => PlatformSecurityHeaders::class,
            'platform.https' => EnforcePlatformHttps::class,
            'platform.ip-allowlist' => EnforcePlatformIpAllowlist::class,
            'tenant.resolve' => ResolveTenantContext::class,
            'tenant.isolate' => EnforceTenantIsolation::class,
            'shop.isolate' => EnforceShopIsolation::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
