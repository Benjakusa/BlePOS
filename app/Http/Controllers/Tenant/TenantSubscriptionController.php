<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\Subscription;
use App\Services\MpesaStkPushService;
use App\Services\PlatformBillingService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantSubscriptionController extends Controller
{
    public function index(): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $subscription = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->with('plan')
            ->latest('id')
            ->first();

        $plans = Plan::query()
            ->where('is_active', true)
            ->where('monthly_price', '>', 0)
            ->orderBy('monthly_price')
            ->get();

        $invoices = PlatformInvoice::query()
            ->where('tenant_id', $tenant->id)
            ->with('items')
            ->latest('id')
            ->get();

        $payments = PlatformPayment::query()
            ->where('tenant_id', $tenant->id)
            ->with('invoice')
            ->latest('paid_at')
            ->limit(20)
            ->get();

        return view('tenant.subscription.index', compact(
            'tenant',
            'subscription',
            'plans',
            'invoices',
            'payments'
        ));
    }

    public function pay(
        Request $request,
        PlatformBillingService $billing,
        MpesaStkPushService $stkPush
    ): RedirectResponse {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403);

        $validated = $request->validate([
            'invoice_id' => ['nullable', 'integer', 'exists:platform_invoices,id'],
            'plan_id' => ['nullable', 'required_without:invoice_id', 'integer', 'exists:plans,id'],
            'billing_cycle' => ['nullable', Rule::in(['monthly', 'annual'])],
            'payment_method' => ['required', Rule::in(['mpesa_stk', 'mpesa_paybill', 'card', 'bank_transfer'])],
            'phone_number' => ['nullable', 'required_if:payment_method,mpesa_stk', 'string', 'min:9', 'max:20'],
            'gateway_reference' => ['nullable', 'string', 'max:100'],
        ]);

        $subscription = Subscription::withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->latest('id')
            ->first();

        $cycle = $validated['billing_cycle'] ?? ($subscription?->billing_cycle ?? 'monthly');
        $selectedPlan = null;

        if (! empty($validated['invoice_id'])) {
            $invoice = PlatformInvoice::query()->findOrFail($validated['invoice_id']);
            abort_unless((int) $invoice->tenant_id === (int) $tenant->id, 403);
            abort_if($invoice->isPaid(), 422, 'This invoice is already paid.');
        } else {
            $selectedPlan = Plan::query()->findOrFail($validated['plan_id']);
            $price = $cycle === 'annual' ? (float) $selectedPlan->annual_price : (float) $selectedPlan->monthly_price;
            abort_if($price <= 0, 422, 'Please select a paid subscription plan.');

            $invoice = $billing->generateInvoice(
                tenant: $tenant,
                subscription: $subscription,
                items: [[
                    'description' => "{$selectedPlan->name} ({$cycle} subscription)",
                    'quantity' => 1,
                    'unit_price' => $price,
                ]],
                dueDate: now()->addDays(7),
                currency: $selectedPlan->currency ?? $tenant->currency ?? 'KES',
                notes: 'Self-service subscription payment from tenant dashboard.',
            );
        }

        $amountDue = (string) $invoice->amount_due;
        $methodForRecord = $validated['payment_method'] === 'mpesa_stk' || $validated['payment_method'] === 'mpesa_paybill'
            ? 'mpesa'
            : $validated['payment_method'];
        $gatewayRef = $validated['gateway_reference'] ?? null;

        if ($validated['payment_method'] === 'mpesa_stk') {
            try {
                // Platform subscription payments route through the Platform M-Pesa credential (tenantId = null, branchId = null)
                $stkResult = $stkPush->initiate(
                    tenantId: null,
                    branchId: null,
                    phoneNumber: $validated['phone_number'],
                    amount: (float) $amountDue,
                    accountReference: $invoice->invoice_number,
                    description: 'Sub Pay',
                );

                if (! $stkResult['success']) {
                    return back()->withErrors(['payment' => $stkResult['customer_message']]);
                }

                $gatewayRef = $stkResult['checkout_request_id'] ?? $gatewayRef;
            } catch (\Throwable $e) {
                return back()->withErrors(['payment' => 'M-Pesa STK Push failed: '.$e->getMessage()]);
            }
        }

        $billing->recordPayment(
            invoice: $invoice,
            amount: $amountDue,
            paymentMethod: $methodForRecord,
            gatewayReference: $gatewayRef,
            notes: 'Paid via tenant self-service subscription portal',
        );

        // Activate / renew subscription & tenant status
        $renewsAt = $cycle === 'annual' ? now()->addYear() : now()->addMonth();
        if ($subscription) {
            $subscription->update([
                'plan_id' => $selectedPlan?->id ?? $subscription->plan_id,
                'billing_cycle' => $cycle,
                'status' => 'active',
                'renews_at' => $renewsAt,
            ]);
        } elseif ($selectedPlan) {
            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $selectedPlan->id,
                'status' => 'active',
                'billing_cycle' => $cycle,
                'starts_at' => now(),
                'renews_at' => $renewsAt,
            ]);
        }

        if (in_array($tenant->status, ['trial', 'suspended'], true)) {
            $tenant->update([
                'status' => 'active',
                'suspended_at' => null,
                'suspension_reason' => null,
            ]);
        }

        return redirect()->route('tenant.subscription.index')
            ->with('success', "Subscription payment of {$invoice->currency} {$amountDue} recorded ({$invoice->invoice_number}). Your account is active.");
    }
}
