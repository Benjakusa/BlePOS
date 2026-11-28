<?php

namespace Tests\Feature;

use App\Models\MpesaCredential;
use App\Models\MpesaTransaction;
use App\Models\Plan;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlatformBillingService;
use Database\Seeders\HotelTenantSeeder;
use Database\Seeders\MpesaSandboxSeeder;
use Database\Seeders\RestaurantTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class TenantSubscriptionPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RestaurantTenantSeeder::class);
        $this->seed(HotelTenantSeeder::class);
        $this->seed(MpesaSandboxSeeder::class);
    }

    public function test_tenant_owner_sees_subscription_card_on_dashboard(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();

        $response = $this->actingAs($owner, 'web')->get(route('tenant.dashboard'));
        $response->assertStatus(200)
            ->assertSee('Pay Subscription / Manage Plan')
            ->assertSee(route('tenant.subscription.index'));
    }

    public function test_stk_initiation_creates_pending_attempt_without_marking_invoice_paid(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $tenant = Tenant::findOrFail($owner->tenant_id);
        $plan = Plan::where('code', 'growth')->firstOrFail();
        $platformCred = MpesaCredential::where('owner_type', 'platform')->firstOrFail();

        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'platform-oauth-token',
                'expires_in' => '3599',
            ], 200),
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'MerchantRequestID' => '111-222-333',
                'CheckoutRequestID' => 'ws_CO_SUB_123456',
                'ResponseCode' => '0',
                'CustomerMessage' => 'Success. Request accepted for processing',
            ], 200),
        ]);

        $response = $this->actingAs($owner, 'web')->post(route('tenant.subscription.pay'), [
            'plan_id' => $plan->id,
            'billing_cycle' => 'monthly',
            'payment_method' => 'mpesa_stk',
            'phone_number' => '0712345678',
        ]);

        $response->assertRedirect(route('tenant.subscription.index'));

        Http::assertSent(function ($request) use ($platformCred) {
            if (! str_contains($request->url(), '/mpesa/stkpush/v1/processrequest')) {
                return false;
            }

            return $request['BusinessShortCode'] === $platformCred->shortcode
                && str_contains($request['CallBackURL'], $platformCred->callback_token);
        });

        $invoice = PlatformInvoice::query()->where('tenant_id', $tenant->id)->latest('id')->firstOrFail();
        $this->assertSame('issued', $invoice->status);
        $this->assertDatabaseHas('platform_invoices', [
            'tenant_id' => $tenant->id,
            'status' => 'issued',
        ]);

        $this->assertDatabaseHas('platform_payments', [
            'tenant_id' => $tenant->id,
            'payment_method' => 'mpesa',
            'gateway_reference' => 'ws_CO_SUB_123456',
            'status' => 'pending',
        ]);
        $attempt = PlatformPayment::query()->where('tenant_id', $tenant->id)->latest('id')->firstOrFail();
        $this->assertDatabaseHas('mpesa_transactions', [
            'platform_payment_id' => $attempt->id,
            'mpesa_credential_id' => $platformCred->id,
            'checkout_request_id' => 'ws_CO_SUB_123456',
            'merchant_request_id' => '111-222-333',
            'status' => 'pending',
        ]);
        $this->assertSame(64, strlen((string) MpesaTransaction::query()->where('platform_payment_id', $attempt->id)->value('phone_hash')));

        $sub = Subscription::withoutGlobalScopes()->where('tenant_id', $tenant->id)->latest('id')->firstOrFail();
        $this->assertNotSame($plan->id, $sub->plan_id);
        $this->assertSame('awaiting_payment', $sub->status);
        $this->assertNotSame('active', $tenant->fresh()->status);
    }

    public function test_tenant_can_pay_existing_sent_invoice_and_cannot_pay_other_tenant_invoice(): void
    {
        $ownerA = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $tenantA = Tenant::findOrFail($ownerA->tenant_id);
        $tenantB = Tenant::where('slug', 'grand-horizon-hotel')->firstOrFail();

        $billing = app(PlatformBillingService::class);
        $invoiceA = $billing->generateInvoice($tenantA, null, [['description' => 'Monthly Fee', 'quantity' => 1, 'unit_price' => 2500]], now()->addDays(5));
        $invoiceB = $billing->generateInvoice($tenantB, null, [['description' => 'Hotel Fee', 'quantity' => 1, 'unit_price' => 5000]], now()->addDays(5));

        $this->actingAs($ownerA, 'web')->post(route('tenant.subscription.pay'), [
            'invoice_id' => $invoiceA->id,
            'payment_method' => 'mpesa_paybill',
            'gateway_reference' => 'QDF12345XY',
        ])->assertRedirect(route('tenant.subscription.index'));

        $this->assertFalse($invoiceA->fresh()->isPaid());
        $this->assertDatabaseHas('platform_payments', [
            'platform_invoice_id' => $invoiceA->id,
            'status' => 'pending',
            'gateway_reference' => 'QDF12345XY',
        ]);

        $this->actingAs($ownerA, 'web')->post(route('tenant.subscription.pay'), [
            'invoice_id' => $invoiceB->id,
            'payment_method' => 'mpesa_paybill',
            'gateway_reference' => 'QDF99999ZZ',
        ])->assertStatus(403);

        $this->assertFalse(PlatformInvoice::findOrFail($invoiceB->id)->isPaid());
    }

    public function test_cashier_cannot_access_tenant_subscription_billing(): void
    {
        $cashier = User::where('email', 'restaurant.cashier@blepos.co.ke')->firstOrFail();
        $this->actingAs($cashier, 'web')->get(route('tenant.subscription.index'))->assertStatus(403);
    }

    public function test_disabled_plan_paid_after_invoice_is_flagged_and_not_applied(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $tenant = Tenant::findOrFail($owner->tenant_id);
        $currentPlan = Plan::where('code', 'trial')->firstOrFail();
        $paidPlan = Plan::where('code', 'growth')->firstOrFail();
        $subscription = Subscription::query()->create([
            'tenant_id' => $tenant->id,
            'plan_id' => $currentPlan->id,
            'pending_plan_id' => $paidPlan->id,
            'status' => 'awaiting_payment',
            'billing_cycle' => 'monthly',
            'pending_billing_cycle' => 'monthly',
            'starts_at' => now(),
        ]);
        $invoice = app(PlatformBillingService::class)->generateInvoice(
            $tenant,
            $subscription,
            [['description' => 'Growth plan', 'quantity' => 1, 'unit_price' => 6500]],
            now()->addDays(5)
        );
        $invoice->forceFill([
            'subscription_plan_id' => $paidPlan->id,
            'subscription_plan_name' => $paidPlan->name,
            'subscription_billing_cycle' => 'monthly',
        ])->save();
        $payment = app(PlatformBillingService::class)->createPaymentAttempt($invoice, (string) $invoice->amount_due, 'mpesa');
        $credential = MpesaCredential::query()->where('owner_type', 'platform')->firstOrFail();
        $transaction = MpesaTransaction::query()->create([
            'mpesa_credential_id' => $credential->id,
            'platform_payment_id' => $payment->id,
            'account_reference' => $invoice->invoice_number,
            'phone_hash' => str_repeat('a', 64),
            'amount' => $payment->amount,
            'status' => 'confirmed',
            'provider_receipt' => 'SUBPLAN12345',
        ]);
        $paidPlan->update(['is_active' => false]);

        app(PlatformBillingService::class)->confirmVerifiedProviderPayment($payment, $transaction, 'SUBPLAN12345');

        $this->assertSame('completed', $payment->fresh()->status);
        $this->assertSame('needs_staff_action', $invoice->fresh()->status);
        $this->assertEquals($currentPlan->id, $subscription->fresh()->plan_id);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $owner->id]);
    }

    public function test_second_plan_invoice_is_blocked_while_previous_plan_invoice_is_unpaid(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $tenant = Tenant::findOrFail($owner->tenant_id);
        $subscription = Subscription::withoutGlobalScopes()->where('tenant_id', $tenant->id)->latest('id')->first();
        if (! $subscription) {
            $subscription = Subscription::query()->create([
                'tenant_id' => $tenant->id,
                'plan_id' => Plan::where('code', 'trial')->value('id'),
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
            ]);
        }
        $openInvoice = app(PlatformBillingService::class)->generateInvoice(
            $tenant,
            $subscription,
            [['description' => 'Existing plan invoice', 'quantity' => 1, 'unit_price' => 6500]],
            now()->addDays(5)
        );
        $openInvoice->forceFill([
            'subscription_plan_id' => Plan::where('code', 'growth')->value('id'),
            'subscription_plan_name' => 'Growth Plan',
            'subscription_billing_cycle' => 'monthly',
        ])->save();

        $this->actingAs($owner, 'web')->post(route('tenant.subscription.pay'), [
            'plan_id' => Plan::where('code', 'enterprise')->value('id'),
            'billing_cycle' => 'monthly',
            'payment_method' => 'mpesa_stk',
            'phone_number' => '0712345678',
        ])->assertStatus(409);
    }
}
