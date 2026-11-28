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
        try {
            app(MpesaEncryptionService::class);
        } catch (\Throwable) {
            $problems[] = 'a valid 32-byte M-Pesa encryption key must be configured';
        }
        $callbackIps = (array) config('services.mpesa.callback_allowed_ips', []);
        if (! collect($callbackIps)->contains(fn ($rule): bool => is_string($rule) && $this->isValidIpRule($rule))) {
            $problems[] = 'MPESA_CALLBACK_ALLOWED_IPS must contain at least one valid Safaricom callback IP/CIDR';
        }
        if (strlen((string) config('services.mpesa.callback_secret', '')) < 32) {
            $problems[] = 'MPESA_CALLBACK_SECRET must contain at least 32 characters';
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

    private function isValidIpRule(string $rule): bool
    {
        [$address, $prefix] = array_pad(explode('/', $rule, 2), 2, null);
        $packed = inet_pton($address);
        if ($packed === false) {
            return false;
        }
        if ($prefix === null) {
            return true;
        }
        $bits = filter_var($prefix, FILTER_VALIDATE_INT);

        return $bits !== false && $bits >= 0 && $bits <= strlen($packed) * 8;
    }
}
