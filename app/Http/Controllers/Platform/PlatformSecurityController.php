<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\PlatformPermissionAuthorizer;
use App\Services\PlatformSecurityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSecurityController extends Controller
{
    public function index(
        Request $request,
        PlatformPermissionAuthorizer $authorizer,
        PlatformSecurityService $securityService
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        $overview = $securityService->getSecurityOverview();

        return view('platform.security.index', [
            'metrics' => $overview['metrics'],
            'recentIncidents' => $overview['recent_incidents'],
            'recentEvents' => $overview['recent_events'],
            'keys' => $overview['keys'],
        ]);
    }
}
