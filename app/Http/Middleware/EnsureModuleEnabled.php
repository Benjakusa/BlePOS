<?php

namespace App\Http\Middleware;

use App\Services\ModuleService;
use App\Services\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    public function __construct(private readonly ModuleService $modules) {}

    /**
     * Ensure the current tenant has an active entitlement for the requested module slug.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $moduleSlug): Response
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $hasEntitlement = $this->modules->isModuleEnabled($tenant, $moduleSlug);
        $industryMatches = ($tenant->industry === $moduleSlug) || str_starts_with($tenant->industry ?? '', $moduleSlug);

        if (! $hasEntitlement && ! $industryMatches) {
            abort(403, "Module '{$moduleSlug}' is not enabled for this workspace.");
        }

        return $next($request);
    }
}
