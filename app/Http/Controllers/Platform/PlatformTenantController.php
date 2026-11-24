<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\IndustryTemplate;
use App\Models\Plan;
use App\Models\ProvisioningRun;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\PlatformAuditRecorder;
use App\Services\PlatformPermissionAuthorizer;
use App\Services\ProvisioningEngine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformTenantController extends Controller
{
    public function index(Request $request, PlatformPermissionAuthorizer $authorizer): View
    {
        $authorizer->ensure($request->user('platform'), 'platform.tenants.view');

        $query = Tenant::query()->withCount(['shops', 'users'])->with(['domains']);

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('code', 'like', $search)
                    ->orWhere('owner_email', 'like', $search);
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('industry')) {
            $query->where('industry', $request->input('industry'));
        }

        $tenants = $query->orderByDesc('created_at')->paginate(20)->withQueryString();

        return view('platform.tenants.index', [
            'tenants' => $tenants,
            'statusCounts' => [
                'all' => Tenant::query()->count(),
                'active' => Tenant::query()->where('status', 'active')->count(),
                'trial' => Tenant::query()->where('status', 'trial')->count(),
                'suspended' => Tenant::query()->where('status', 'suspended')->count(),
            ],
        ]);
    }

    public function create(Request $request, PlatformPermissionAuthorizer $authorizer): View
    {
        $authorizer->ensure($request->user('platform'), 'platform.tenants.create');

        return view('platform.tenants.create', [
            'plans' => Plan::query()->where('is_active', true)->orderBy('monthly_price')->get(),
            'industries' => IndustryTemplate::query()->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(
        Request $request,
        ProvisioningEngine $engine,
        PlatformAuditRecorder $audit,
        PlatformPermissionAuthorizer $authorizer
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.tenants.create');

        $validated = $request->validate([
            'legal_name' => ['required', 'string', 'max:255'],
            'trading_name' => ['nullable', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:32', 'unique:tenants,code'],
            'slug' => ['required', 'string', 'max:64', 'unique:tenants,slug'],
            'kra_pin' => ['nullable', 'string', 'max:32', 'unique:tenants,kra_pin'],
            'industry' => ['required', 'string', 'max:64'],
            'plan_code' => ['required', 'string', 'exists:plans,code'],
            'owner_name' => ['required', 'string', 'max:255'],
            'owner_email' => ['required', 'email:rfc', 'max:255'],
            'owner_phone' => ['nullable', 'string', 'max:32'],
            'domain' => ['required', 'string', 'max:120', 'unique:tenant_domains,domain'],
            'initial_shop_name' => ['required', 'string', 'max:255'],
            'initial_shop_code' => ['required', 'string', 'max:32'],
        ]);

        $run = $engine->initiate($validated);
        $engine->execute($run);

        $audit->record(
            actorId: $request->user('platform')->id,
            action: 'platform.tenants.create',
            targetType: Tenant::class,
            targetId: (string) $run->tenant_id,
            details: [
                'tenant_code' => $validated['code'],
                'legal_name' => $validated['legal_name'],
                'run_identifier' => $run->run_identifier,
                'status' => $run->fresh()->status,
            ],
        );

        return redirect()->route('platform.tenants.show', $run->tenant_id)
            ->with('status', 'Tenant registration and provisioning completed.');
    }

    public function show(Request $request, Tenant $tenant, PlatformPermissionAuthorizer $authorizer): View
    {
        $authorizer->ensure($request->user('platform'), 'platform.tenants.view');

        $tenant->load(['domains', 'shops.registers', 'users.roles', 'warehouses']);
        $provisioningRuns = $tenant->hasMany(ProvisioningRun::class)->with('steps')->orderByDesc('created_at')->get();
        $subscription = Subscription::where('tenant_id', $tenant->id)->with('plan')->latest()->first();
        $availablePlans = Plan::where('is_active', true)->orderBy('name')->get();
        $industryTemplates = IndustryTemplate::where('is_active', true)->orderBy('name')->get();

        return view('platform.tenants.show', [
            'tenant' => $tenant,
            'provisioningRuns' => $provisioningRuns,
            'subscription' => $subscription,
            'availablePlans' => $availablePlans,
            'industryTemplates' => $industryTemplates,
        ]);
    }

    public function suspend(
        Request $request,
        Tenant $tenant,
        PlatformAuditRecorder $audit,
        PlatformPermissionAuthorizer $authorizer
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.tenants.suspend');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        abort_if($tenant->status === 'suspended', 422, 'Tenant is already suspended.');

        $previousStatus = $tenant->status;
        $tenant->update(['status' => 'suspended']);

        $audit->record(
            actorId: $request->user('platform')->id,
            action: 'platform.tenants.suspend',
            targetType: Tenant::class,
            targetId: (string) $tenant->id,
            details: [
                'previous_status' => $previousStatus,
                'new_status' => 'suspended',
                'reason' => $validated['reason'],
            ],
        );

        return back()->with('status', "Tenant {$tenant->name} has been suspended.");
    }

    public function reactivate(
        Request $request,
        Tenant $tenant,
        PlatformAuditRecorder $audit,
        PlatformPermissionAuthorizer $authorizer
    ): RedirectResponse {
        $authorizer->ensure($request->user('platform'), 'platform.tenants.reactivate');

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);

        abort_unless($tenant->status === 'suspended', 422, 'Only suspended tenants can be reactivated.');

        $tenant->update(['status' => 'active']);

        $audit->record(
            actorId: $request->user('platform')->id,
            action: 'platform.tenants.reactivate',
            targetType: Tenant::class,
            targetId: (string) $tenant->id,
            details: [
                'previous_status' => 'suspended',
                'new_status' => 'active',
                'reason' => $validated['reason'],
            ],
        );

        return back()->with('status', "Tenant {$tenant->name} has been reactivated.");
    }
}
