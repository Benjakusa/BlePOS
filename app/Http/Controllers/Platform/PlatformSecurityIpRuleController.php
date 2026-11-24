<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformIpRule;
use App\Services\PlatformPermissionAuthorizer;
use App\Services\PlatformSecurityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSecurityIpRuleController extends Controller
{
    public function index(
        Request $request,
        PlatformPermissionAuthorizer $authorizer
    ): View {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        $rules = PlatformIpRule::query()
            ->with('creator:id,name,email')
            ->orderByDesc('id')
            ->paginate(30);

        return view('platform.security.ip_rules.index', [
            'rules' => $rules,
        ]);
    }

    public function store(
        Request $request,
        PlatformPermissionAuthorizer $authorizer,
        PlatformSecurityService $securityService
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        $validated = $request->validate([
            'ip_or_cidr' => ['required', 'string', 'max:50'],
            'rule_type' => ['required', 'string', 'in:allowlist,denylist'],
            'scope' => ['required', 'string', 'in:platform_console,tenant_api,global'],
            'reason' => ['required', 'string', 'max:255'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:525600'],
        ]);

        $rule = $securityService->createIpRule($validated, $request->user('platform'));

        return redirect()->route('platform.security.ip-rules.index')
            ->with('notice', "IP Rule for {$rule->ip_or_cidr} created successfully.");
    }

    public function toggle(
        Request $request,
        PlatformIpRule $rule,
        PlatformPermissionAuthorizer $authorizer,
        PlatformSecurityService $securityService
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        $updated = $securityService->toggleIpRule($rule, $request->user('platform'));
        $status = $updated->is_active ? 'activated' : 'deactivated';

        return redirect()->route('platform.security.ip-rules.index')
            ->with('notice', "IP rule for {$rule->ip_or_cidr} has been {$status}.");
    }

    public function destroy(
        Request $request,
        PlatformIpRule $rule,
        PlatformPermissionAuthorizer $authorizer,
        PlatformSecurityService $securityService
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.security.view');

        $securityService->deleteIpRule($rule, $request->user('platform'));

        return redirect()->route('platform.security.ip-rules.index')
            ->with('notice', "IP rule for {$rule->ip_or_cidr} deleted.");
    }
}
