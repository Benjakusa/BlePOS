<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Vite;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformSecurityHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(18));
        $request->attributes->set('csp_nonce', $nonce);
        $response = $next($request);

        $styleSrc = "'self' 'unsafe-inline'";
        $scriptSrc = "'self' 'nonce-{$nonce}'";
        $fontSrc = "'self' data:";
        $connectSrc = "'self'";

        if (app()->environment('local') && app(Vite::class)->isRunningHot()) {
            $hotOrigin = rtrim(trim((string) @file_get_contents(app(Vite::class)->hotFile())), '/');
            $parts = parse_url($hotOrigin);
            $host = $parts['host'] ?? '127.0.0.1';
            $port = isset($parts['port']) ? ':'.$parts['port'] : '';
            $devOrigins = "http://{$host}{$port} http://localhost{$port} http://127.0.0.1{$port}";
            $wsOrigins = "ws://{$host}{$port} ws://localhost{$port} ws://127.0.0.1{$port}";

            $styleSrc .= " {$devOrigins}";
            $scriptSrc .= " {$devOrigins}";
            $fontSrc .= " {$devOrigins}";
            $connectSrc .= " {$devOrigins} {$wsOrigins}";
        }

        $response->headers->set(
            'Content-Security-Policy',
            "default-src 'self'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'; object-src 'none'; img-src 'self' data:; font-src {$fontSrc}; connect-src {$connectSrc}; style-src {$styleSrc}; script-src {$scriptSrc}"
        );
        $response->headers->set('Referrer-Policy', 'no-referrer');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if ($request->isSecure() && app()->isProduction()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000');
        }

        return $response;
    }
}
