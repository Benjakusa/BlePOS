<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\MpesaTransaction;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Services\MpesaStkPushService;
use App\Services\PlatformBillingService;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            [$subscription, $invoice] = DB::transaction(function () use ($tenant, $validated, $cycle, $billing): array {
                // Serialize plan-invoice creation per tenant, including the first subscription.
                Tenant::query()->lockForUpdate()->findOrFail($tenant->id);
                $subscription = Subscription::withoutGlobalScopes()
                    ->where('tenant_id', $tenant->id)
                    ->latest('id')
                    ->lockForUpdate()
                    ->first();
                $plan = Plan::query()->lockForUpdate()->findOrFail($validated['plan_id']);
                abort_unless($plan->is_active, 422, 'This subscription plan is no longer available.');
                $price = $cycle === 'annual' ? (float) $plan->annual_price : (float) $plan->monthly_price;
                abort_if($price <= 0, 422, 'Please select a paid subscription plan.');
                abort_if($subscription !== null && PlatformInvoice::query()
                    ->where('subscription_id', $subscription->id)
                    ->whereNotNull('subscription_plan_name')
                    ->whereNotIn('status', ['paid', 'voided', 'cancelled'])
                    ->exists(), 409, 'A subscription plan invoice is already awaiting payment. Pay or cancel it before requesting another plan change.');

                if ($subscription === null) {
                    $subscription = Subscription::create([
                        'tenant_id' => $tenant->id,
                        'plan_id' => $plan->id,
                        'status' => 'awaiting_payment',
                        'billing_cycle' => $cycle,
                        'starts_at' => now(),
                    ]);
                } else {
                    $subscription->forceFill([
                        'pending_plan_id' => $plan->id,
                        'pending_billing_cycle' => $cycle,
                    ])->save();
                }

                $invoice = $billing->generateInvoice(
                    tenant: $tenant,
                    subscription: $subscription,
                    items: [[
                        'description' => "{$plan->name} ({$cycle} subscription)",
                        'quantity' => 1,
                        'unit_price' => $price,
                    ]],
                    dueDate: now()->addDays(7),
                    currency: $plan->currency ?? $tenant->currency ?? 'KES',
                    notes: 'Self-service subscription payment from tenant dashboard.',
                );
                $invoice->forceFill([
                    'subscription_plan_id' => $plan->id,
                    'subscription_plan_name' => $plan->name,
                    'subscription_billing_cycle' => $cycle,
                ])->save();

                return [$subscription, $invoice];
            });
        }

        $amountDue = (string) $invoice->amount_due;
        if ($validated['payment_method'] === 'mpesa_stk') {
            abort_if(MpesaTransaction::query()
                ->where('status', 'pending')
                ->whereHas('platformPayment', fn ($query) => $query->where('platform_invoice_id', $invoice->id))
                ->exists(), 409, 'An M-Pesa prompt is already pending for this invoice. Check its status before retrying.');
        }
        $methodForRecord = $validated['payment_method'] === 'mpesa_stk'
            ? 'mpesa'
            : $validated['payment_method'];
        $gatewayRef = $validated['gateway_reference'] ?? null;
        $payment = $billing->createPaymentAttempt(
            invoice: $invoice,
            amount: $amountDue,
            paymentMethod: $methodForRecord,
            gatewayReference: null,
            notes: 'Payment attempt started through tenant self-service; settlement is unverified.',
        );
        if ($invoice->subscription_id) {
            $linkedSubscription = Subscription::withoutGlobalScopes()->where('tenant_id', $tenant->id)->find($invoice->subscription_id);
            if ($linkedSubscription && in_array($linkedSubscription->status, ['trial', 'trialing', 'awaiting_payment'], true)) {
                $linkedSubscription->update(['status' => 'awaiting_payment']);
            }
        }

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
                    paymentAttempt: $payment,
                );

                if ($stkResult['status'] !== 'initiated') {
                    $payment->forceFill(['status' => 'failed'])->save();

                    return back()->withErrors(['payment' => $stkResult['customer_message']]);
                }

                $gatewayRef = $stkResult['checkout_request_id'] ?? $gatewayRef;
                $payment->forceFill(['gateway_reference' => $gatewayRef])->save();
            } catch (\Throwable $e) {
                $payment->forceFill(['status' => 'failed'])->save();

                return back()->withErrors(['payment' => 'M-Pesa STK Push failed: '.$e->getMessage()]);
            }
        }

        if ($validated['payment_method'] !== 'mpesa_stk' && $gatewayRef !== null) {
            $payment->forceFill(['gateway_reference' => $gatewayRef])->save();
        }

        return redirect()->route('tenant.subscription.index')
            ->with('success', "Payment attempt {$payment->payment_reference} for {$invoice->currency} {$amountDue} is pending confirmation ({$invoice->invoice_number}). Subscription access changes only after settlement is verified.");
    }
}
