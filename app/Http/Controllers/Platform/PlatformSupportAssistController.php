<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\PlatformAuditRecorder;
use App\Services\SupportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformSupportAssistController extends Controller
{
    public function __construct(
        protected SupportService $support,
        protected PlatformAuditRecorder $audit
    ) {}

    /**
     * Non-impersonating tenant overview and assist diagnostics (Annex A 13.2, 13.3).
     */
    public function overview(Request $request, Tenant $tenant): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        $tenant->load([
            'domains',
            'shops.registers',
            'warehouses',
            'subscriptions.plan',
            'entitlements.module',
            'users' => fn ($q) => $q->take(10),
        ]);

        $integrity = $this->support->runTenantIntegrityCheck($tenant);

        return view('platform.support.assist.overview', [
            'tenant' => $tenant,
            'integrity' => $integrity,
        ]);
    }

    /**
     * Run on-demand permitted data-integrity check (Annex A 13.2).
     */
    public function integrityCheck(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.support.view'), 403);

        $integrity = $this->support->runTenantIntegrityCheck($tenant);

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'support.assist.integrity_check',
            targetType: Tenant::class,
            targetId: (string) $tenant->id,
            details: [
                'tenant_id' => $tenant->id,
                'pass_count' => $integrity['pass_count'],
                'fail_count' => $integrity['fail_count'],
            ],
        );

        return redirect()->route('platform.support.assist.overview', $tenant)
            ->with('notice', "Integrity check executed: {$integrity['pass_count']} checks passed, {$integrity['fail_count']} failed.");
    }
}
