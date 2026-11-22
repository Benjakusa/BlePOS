<?php

namespace App\Http\Middleware;

use App\Services\PlatformPermissionAuthorizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlatformPermission
{
    public function __construct(private PlatformPermissionAuthorizer $authorizer) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        if (! $this->authorizer->allows($request->user('platform'), $permission)) {
            return response('Forbidden', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
