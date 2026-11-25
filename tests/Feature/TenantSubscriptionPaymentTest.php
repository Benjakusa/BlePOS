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
}
