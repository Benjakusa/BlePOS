<?php

namespace App\Providers;

use App\Services\MpesaCredentialResolver;
use App\Services\MpesaEncryptionService;
use App\Support\ResilientVite;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Vite;
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
        $this->app->singleton(Vite::class, ResilientVite::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->isProduction()) {
            $problems = [];
            if ((bool) config('app.debug')) {
                $problems[] = 'APP_DEBUG must be false';
            }
            if (! config('app.key')) {
                $problems[] = 'APP_KEY must be configured';
            }
            if (! config('session.secure')) {
                $problems[] = 'SESSION_SECURE_COOKIE must be true';
            }
            if (config('database.default') === 'sqlite') {
                $problems[] = 'SQLite is not supported for production';
            }
            $uploadDisk = (string) config('filesystems.default');
            if (config('filesystems.disks.'.$uploadDisk.'.driver') !== 's3') {
                $problems[] = 'production uploads require durable object storage';
            }
            $backupDisk = (string) config('platform.backup_disk');
            if (config('filesystems.disks.'.$backupDisk.'.driver') !== 's3') {
                $problems[] = 'production backups require off-host object storage';
            }
            if (config('trustedproxy.proxies') === []) {
                $problems[] = 'TRUSTED_PROXIES must list the reverse proxy addresses';
            }
            $viteManifestPath = public_path('build/manifest.json');
            $viteManifest = is_file($viteManifestPath)
                ? json_decode((string) file_get_contents($viteManifestPath), true)
                : null;
            foreach (['resources/css/app.css', 'resources/js/app.js'] as $entrypoint) {
                $compiledFile = is_array($viteManifest) ? data_get($viteManifest, $entrypoint.'.file') : null;

                if (! is_string($compiledFile) || ! is_file(public_path('build/'.$compiledFile))) {
                    $problems[] = 'the Vite production build is missing or incomplete; run npm run build before deployment';
                    break;
                }
            }
            if ($problems !== []) {
                throw new \RuntimeException('Unsafe production configuration: '.implode('; ', $problems).'.');
            }
        }

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
