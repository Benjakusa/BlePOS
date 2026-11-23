<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Services\PlatformAuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PlatformPlanController extends Controller
{
    public function __construct(
        protected PlatformAuditRecorder $audit
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.view'), 403);

        $plans = Plan::query()
            ->withCount(['subscriptions as active_subscribers_count' => function ($q): void {
                $q->whereIn('status', ['trial', 'active']);
            }])
            ->orderBy('id')
            ->paginate(20);

        return view('platform.plans.index', [
            'plans' => $plans,
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.plans.manage'), 403);

        return view('platform.plans.create');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.plans.manage'), 403);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:32', 'unique:plans,code', 'alpha_dash'],
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'currency' => ['required', 'string', 'size:3'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['required', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'max_shops' => ['required', 'integer', 'min:1'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_registers' => ['required', 'integer', 'min:1'],
            'max_warehouses' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $plan = Plan::create($validated);

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'plan.create',
            targetType: Plan::class,
            targetId: (string) $plan->id,
            details: ['code' => $plan->code, 'name' => $plan->name],
        );

        return redirect()->route('platform.plans.index')
            ->with('notice', "Plan '{$plan->name}' created successfully.");
    }

    public function edit(Request $request, Plan $plan): View
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.plans.manage'), 403);

        return view('platform.plans.edit', [
            'plan' => $plan,
        ]);
    }

    public function update(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.plans.manage'), 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'monthly_price' => ['required', 'numeric', 'min:0'],
            'annual_price' => ['required', 'numeric', 'min:0'],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'max_shops' => ['required', 'integer', 'min:1'],
            'max_users' => ['required', 'integer', 'min:1'],
            'max_registers' => ['required', 'integer', 'min:1'],
            'max_warehouses' => ['required', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);

        $plan->update($validated);

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'plan.update',
            targetType: Plan::class,
            targetId: (string) $plan->id,
            details: ['name' => $plan->name, 'monthly_price' => $plan->monthly_price],
        );

        return redirect()->route('platform.plans.index')
            ->with('notice', "Plan '{$plan->name}' updated successfully.");
    }

    public function toggleStatus(Request $request, Plan $plan): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.plans.manage'), 403);

        $plan->is_active = ! $plan->is_active;
        $plan->save();

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'plan.toggle_status',
            targetType: Plan::class,
            targetId: (string) $plan->id,
            details: ['is_active' => $plan->is_active],
        );

        $statusStr = $plan->is_active ? 'activated' : 'deactivated';

        return redirect()->route('platform.plans.index')
            ->with('notice', "Plan '{$plan->name}' has been {$statusStr}.");
    }
}
