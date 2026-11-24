<?php

namespace App\Http\Middleware;

use App\Models\PlatformIpRule;
use App\Services\PlatformSecurityEvents;
use App\Services\PlatformSecurityService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlatformIpAllowlist
{
    public function __construct(
        private readonly PlatformSecurityEvents $events,
        private readonly PlatformSecurityService $securityService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientIp = $request->ip();

        if (is_string($clientIp) && filter_var($clientIp, FILTER_VALIDATE_IP) !== false) {
            // 1. Dynamic denylist check
            if ($this->securityService->isIpBlocked($clientIp, 'platform_console')) {
                $this->events->record($request, 'platform.network.ip_denied', 'critical', null, [
                    'policy' => 'ip_denylist',
                ]);

                abort(Response::HTTP_FORBIDDEN, 'Access to the platform console is blocked by security denylist.');
            }
        }

        // 2. Allowlist check (if enabled)
        if (! config('platform.security.ip_allowlist_enabled')) {
            return $next($request);
        }

        $overrideKey = config('platform.security.ip_allowlist_override_cache_key');
        if (Cache::get($overrideKey) === true) {
            return $next($request);
        }

        $allowedNetworks = config('platform.security.ip_allowlist', []);

        // Also merge dynamic active database allowlist rules
        $dynamicAllowlist = PlatformIpRule::query()
            ->where('rule_type', 'allowlist')
            ->where('is_active', true)
            ->where(function ($query): void {
                $query->where('scope', 'global')
                    ->orWhere('scope', 'platform_console');
            })
            ->pluck('ip_or_cidr')
            ->toArray();

        $allAllowed = array_merge($allowedNetworks, $dynamicAllowlist);

        $isAllowed = is_string($clientIp)
            && filter_var($clientIp, FILTER_VALIDATE_IP) !== false
            && collect($allAllowed)->contains(fn (string $network): bool => IpUtils::checkIp($clientIp, $network));

        if (! $isAllowed) {
            $this->events->record($request, 'platform.network.ip_denied', 'warning', null, [
                'policy' => 'ip_allowlist',
            ]);

            abort(Response::HTTP_FORBIDDEN, 'Access to the platform console is restricted by network policy.');
        }

        return $next($request);
    }
}
