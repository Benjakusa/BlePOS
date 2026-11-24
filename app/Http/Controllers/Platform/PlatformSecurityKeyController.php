<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformSecurityKey;
use App\Services\PlatformPermissionAuthorizer;
use App\Services\PlatformSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSecurityKeyController extends Controller
{
    public function index(
        Request $request,
        PlatformPermissionAuthorizer $authorizer,
        PlatformSecurityService $securityService
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        $securityService->ensureDefaultKeys();

        $keys = PlatformSecurityKey::query()
            ->orderBy('id')
            ->get();

        return view('platform.security.keys.index', [
            'keys' => $keys,
        ]);
    }

    public function rotate(
        Request $request,
        PlatformSecurityKey $key,
        PlatformPermissionAuthorizer $authorizer,
        PlatformSecurityService $securityService
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $securityService->rotateKey($key, $request->user('platform'), $validated['reason']);

        return redirect()->route('platform.security.keys.index')
            ->with('notice', "Key {$key->key_id} rotated to version {$key->version} successfully.");
    }
}
