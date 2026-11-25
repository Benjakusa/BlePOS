<?php

namespace App\Providers;

use App\Services\MpesaCredentialResolver;
use App\Services\MpesaEncryptionService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MpesaEncryptionService::class);
        $this->app->singleton(MpesaCredentialResolver::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('platform-login', function (Request $request): array {
            $account = Str::lower($request->string('email')->toString());

            return [
                Limit::perMinute(30)->by('platform-login-ip:'.$request->ip()),
                Limit::perMinute(8)->by('platform-login-account:'.$account),
            ];
        });
        RateLimiter::for('platform-password-reset', function (Request $request): array {
            $account = Str::lower($request->string('email')->toString());

            return [
                Limit::perMinute(20)->by('platform-password-reset-ip:'.$request->ip()),
                Limit::perMinute(5)->by('platform-password-reset-account:'.$account),
            ];
        });
        RateLimiter::for('platform-mfa', function (Request $request): array {
            $userId = $request->user('platform')?->getAuthIdentifier() ?? 'anonymous';

            return [
                Limit::perMinute(30)->by('platform-mfa-ip:'.$request->ip()),
                Limit::perMinute(6)->by('platform-mfa-user:'.$userId),
            ];
        });
        RateLimiter::for('platform-step-up', function (Request $request): array {
            $userId = $request->user('platform')?->getAuthIdentifier() ?? 'anonymous';

            return [
                Limit::perMinute(30)->by('platform-step-up-ip:'.$request->ip()),
                Limit::perMinute(5)->by('platform-step-up-user:'.$userId),
            ];
        });
    }
}
