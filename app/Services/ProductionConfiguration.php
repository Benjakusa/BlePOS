<?php

namespace App\Services;

class ProductionConfiguration
{
    /** @return list<string> */
    public function violations(): array
    {
        $problems = [];
        if ((bool) config('app.debug')) {
            $problems[] = 'APP_DEBUG must be false';
        }
        if (config('app.env') !== 'production') {
            $problems[] = 'APP_ENV must be production';
        }
        if (! config('app.key')) {
            $problems[] = 'APP_KEY must be configured';
        }
        if (! config('session.secure')) {
            $problems[] = 'SESSION_SECURE_COOKIE must be true';
        }
        if (! config('platform.security.force_https')) {
            $problems[] = 'PLATFORM_FORCE_HTTPS must be true';
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
        if (config('queue.default') === 'sync') {
            $problems[] = 'QUEUE_CONNECTION must not be sync';
        }
        if (config('cache.default') === 'array') {
            $problems[] = 'CACHE_STORE must not be array';
        }
        if (! config('trustedproxy.proxies')) {
            $problems[] = 'TRUSTED_PROXIES must list the reverse proxy addresses';
        }

        return $problems;
    }
}
