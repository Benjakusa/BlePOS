<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\PlatformAuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PlatformSubscriptionController extends Controller
{
    public function __construct(
        protected PlatformAuditRecorder $audit
    ) {}

    public function assign(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.subscriptions.manage'), 403);

        $validated = $request->validate([
            'plan_id' => ['required', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['required', 'string', 'in:monthly,annual'],
            'status' => ['required', 'string', 'in:trial,active,grace_period,past_due,suspended'],
        ]);

        $plan = Plan::query()->findOrFail($validated['plan_id']);
        abort_unless($plan->is_active, 422, 'Cannot assign an inactive plan.');

        $currentSub = Subscription::query()->where('tenant_id', $tenant->id)->latest()->first();

        $trialEndsAt = ($validated['status'] === 'trial' && $plan->trial_days > 0)
            ? now()->addDays($plan->trial_days)
            : null;

        $renewsAt = $validated['billing_cycle'] === 'annual'
            ? now()->addYear()
            : now()->addMonth();

        if ($currentSub) {
            $currentSub->update([
                'plan_id' => $plan->id,
                'status' => $validated['status'],
                'billing_cycle' => $validated['billing_cycle'],
                'renews_at' => $renewsAt,
                'trial_ends_at' => $trialEndsAt,
                'cancelled_at' => null,
            ]);
            $subscription = $currentSub;
            $action = 'subscription.change_plan';
        } else {
            $subscription = Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => $validated['status'],
                'billing_cycle' => $validated['billing_cycle'],
                'starts_at' => now(),
                'trial_ends_at' => $trialEndsAt,
                'renews_at' => $renewsAt,
            ]);
            $action = 'subscription.create';
        }

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: $action,
            targetType: Subscription::class,
            targetId: (string) $subscription->id,
            details: [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'status' => $validated['status'],
                'billing_cycle' => $validated['billing_cycle'],
            ],
        );

        return redirect()->route('platform.tenants.show', $tenant)
            ->with('notice', "Subscription updated to '{$plan->name}' ({$validated['status']}).");
    }

    public function transition(Request $request, Tenant $tenant, Subscription $subscription): RedirectResponse
    {
        abort_unless($request->user('platform')?->hasPermission('platform.billing.subscriptions.manage'), 403);
        abort_if((int) $subscription->tenant_id !== (int) $tenant->id, 403);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,grace_period,past_due,suspended,cancelled'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $newStatus = $validated['status'];

        $subscription->status = $newStatus;
        if ($newStatus === 'cancelled') {
            $subscription->cancelled_at = now();
        } elseif ($newStatus === 'grace_period') {
            $subscription->grace_ends_at = now()->addDays(7);
        }

        $subscription->save();

        $this->audit->record(
            actorId: $request->user('platform')?->id,
            action: 'subscription.status_transition',
            targetType: Subscription::class,
            targetId: (string) $subscription->id,
            details: [
                'tenant_id' => $tenant->id,
                'status' => $newStatus,
                'reason' => $validated['reason'],
            ],
        );

        return redirect()->route('platform.tenants.show', $tenant)
            ->with('notice', "Subscription status changed to {$newStatus}.");
    }
}
