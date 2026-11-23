<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Services\PlatformPermissionAuthorizer;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, PlatformPermissionAuthorizer $authorizer): View
    {
        $authorizer->ensure($request->user('platform'), 'platform.dashboard.view');

        return view('platform.dashboard', ['user' => $request->user('platform')]);
    }
}
