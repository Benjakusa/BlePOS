<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\ProvisioningRun;
use App\Services\PlatformAuditRecorder;
use App\Services\PlatformPermissionAuthorizer;
use App\Services\ProvisioningEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformProvisioningController extends Controller
{
    public function index(Request $request, PlatformPermissionAuthorizer $authorizer): View
    {
        $authorizer->ensure($request->user('platform'), 'platform.provisioning.view');

        $runs = ProvisioningRun::query()
            ->with(['tenant', 'steps'])
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('platform.provisioning.index', [
            'runs' => $runs,
        ]);
    }

    public function show(Request $request, ProvisioningRun $provisioningRun, PlatformPermissionAuthorizer $authorizer): View
    {
        $authorizer->ensure($request->user('platform'), 'platform.provisioning.view');

        $provisioningRun->load(['tenant', 'steps']);

        return view('platform.provisioning.show', [
            'run' => $provisioningRun,
        ]);
    }

    public function retry(
        Request $request,
        ProvisioningRun $provisioningRun,
        ProvisioningEngine $engine,
        PlatformAuditRecorder $audit,
        PlatformPermissionAuthorizer $authorizer
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.provisioning.retry');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        abort_if($provisioningRun->isCompleted(), 422, 'Completed provisioning runs cannot be retried.');

        $success = $engine->retry($provisioningRun);

        $audit->record(
            actorId: $request->user('platform')->id,
            action: 'platform.provisioning.retry',
            targetType: ProvisioningRun::class,
            targetId: (string) $provisioningRun->id,
            details: [
                'run_identifier' => $provisioningRun->run_identifier,
                'tenant_id' => $provisioningRun->tenant_id,
                'result' => $success ? 'completed' : 'failed',
                'reason' => $validated['reason'],
            ],
        );

        $message = $success
            ? 'Provisioning retry succeeded and tenant is now active.'
            : 'Provisioning retry failed. Inspect step logs.';

        return back()->with('status', $message);
    }
}
