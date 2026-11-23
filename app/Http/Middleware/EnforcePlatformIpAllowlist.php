<?php

namespace App\Http\Middleware;

use App\Services\PlatformSecurityEvents;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlatformIpAllowlist
{
    public function __construct(private readonly PlatformSecurityEvents $events) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('platform.security.ip_allowlist_enabled')) {
            return $next($request);
        }

        $overrideKey = config('platform.security.ip_allowlist_override_cache_key');
        if (Cache::get($overrideKey) === true) {
            return $next($request);
        }

        $clientIp = $request->ip();
        $allowedNetworks = config('platform.security.ip_allowlist', []);
        $isAllowed = is_string($clientIp)
            && filter_var($clientIp, FILTER_VALIDATE_IP) !== false
            && collect($allowedNetworks)->contains(fn (string $network): bool => IpUtils::checkIp($clientIp, $network));

        if (! $isAllowed) {
            $this->events->record($request, 'platform.network.ip_denied', 'warning', null, [
                'policy' => 'ip_allowlist',
            ]);

            abort(Response::HTTP_FORBIDDEN, 'Access to the platform console is restricted by network policy.');
        }

        return $next($request);
    }
}
