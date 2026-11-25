<?php

namespace App\Support;

use Illuminate\Foundation\Vite;

/**
 * Use the development server only while it can actually serve Vite's client.
 * Laravel's default check trusts the hot file, which may remain after a hard
 * server restart and otherwise prevents the built manifest from being used.
 */
class ResilientVite extends Vite
{
    public function isRunningHot(): bool
    {
        // A development server can be reachable from PHP while still being
        // unreachable to browsers (for example, localhost inside a container).
        // Production and staging must always render versioned build assets.
        if (! app()->environment('local')) {
            return false;
        }

        $hotFile = $this->hotFile();

        if (! is_file($hotFile)) {
            return false;
        }

        $url = trim((string) @file_get_contents($hotFile));
        $parts = parse_url($url);
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));

        if (! in_array($scheme, ['http', 'https'], true) || $host === '') {
            return false;
        }

        // Vite HMR is a developer tool. Refuse arbitrary hot-file URLs so a
        // stale or modified file cannot make page rendering perform SSRF.
        if (! in_array($host, ['localhost', '127.0.0.1', '::1'], true)) {
            return false;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 0.25,
                'ignore_errors' => true,
                'header' => "Connection: close\r\nAccept: */*\r\n",
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
            ],
        ]);
        $response = @file_get_contents(rtrim($url, '/').'/@vite/client', false, $context, 0, 1);
        $status = $http_response_header[0] ?? '';

        return $response !== false && preg_match('/\s200\s/', $status) === 1;
    }
}
