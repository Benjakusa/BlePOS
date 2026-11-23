<?php

return [
    'security' => [
        'force_https' => env('PLATFORM_FORCE_HTTPS', env('APP_ENV') === 'production'),
        'ip_allowlist_enabled' => (bool) env('PLATFORM_IP_ALLOWLIST_ENABLED', false),
        'ip_allowlist' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('PLATFORM_IP_ALLOWLIST', '')),
        ))),
        'ip_allowlist_override_cache_key' => 'platform.security.ip_allowlist.emergency_override',
        'ip_allowlist_override_max_minutes' => 60,
    ],
];
