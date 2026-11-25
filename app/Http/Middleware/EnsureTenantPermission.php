<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('tenant.login');
        }

        // Owners have unrestricted access to all tenant modules and actions
        if ($user->hasRole('owner') || $user->isTenantOwner()) {
            return $next($request);
        }

        if (! $user->hasPermission($permission)) {
            abort(Response::HTTP_FORBIDDEN, 'Insufficient permissions to access this resource.');
        }

        return $next($request);
    }
}
