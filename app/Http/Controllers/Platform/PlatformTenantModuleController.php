<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModuleEntitlement;
use App\Services\ModuleService;
use App\Services\PlatformAuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlatformTenantModuleController extends Controller
{
    public function __construct(
        protected ModuleService $moduleService,
        protected PlatformAuditRecorder $audit
    ) {}

    public function enable(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.modules.manage'), 403);

        $validated = $request->validate([
            'module_id' => ['required', 'integer', 'exists:modules,id'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $module = Module::query()->findOrFail($validated['module_id']);

        $entitlement = $this->moduleService->enableModule(
            tenant: $tenant,
            module: $module,
            notes: $validated['notes'] ?? 'Enabled by platform administrator'
        );

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'tenant.module.enable',
            targetType: TenantModuleEntitlement::class,
            targetId: (string) $entitlement->id,
            details: [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'module_slug' => $module->slug,
            ],
        );

        return redirect()->route('platform.tenants.show', $tenant)
            ->with('notice', "Module '{$module->name}' successfully enabled for {$tenant->name}.");
    }

    public function disable(Request $request, Tenant $tenant, Module $module): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.modules.manage'), 403);

        $entitlement = $this->moduleService->disableModule(
            tenant: $tenant,
            module: $module,
            notes: 'Disabled by platform administrator'
        );

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'tenant.module.disable',
            targetType: TenantModuleEntitlement::class,
            targetId: (string) $entitlement->id,
            details: [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'module_slug' => $module->slug,
            ],
        );

        return redirect()->route('platform.tenants.show', $tenant)
            ->with('notice', "Module '{$module->name}' disabled for {$tenant->name}.");
    }
}
