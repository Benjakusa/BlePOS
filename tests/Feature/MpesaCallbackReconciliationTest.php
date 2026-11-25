<?php

namespace Tests\Feature;

use App\Models\MpesaCredential;
use App\Models\MpesaTransaction;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\Tenant;
use App\Services\PlatformBillingService;
use Database\Seeders\MpesaSandboxSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MpesaCallbackReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private MpesaCredential $credential;

    private PlatformInvoice $invoice;

    private PlatformPayment $payment;

    private MpesaTransaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(MpesaSandboxSeeder::class);
        $this->credential = MpesaCredential::query()->where('owner_type', 'platform')->firstOrFail();
        $tenant = Tenant::factory()->create();
        $this->invoice = PlatformInvoice::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-CALLBACK-001',
            'status' => 'issued',
            'currency' => 'KES',
            'subtotal' => '100.00',
            'tax_rate' => '16.00',
            'tax_amount' => '16.00',
            'total_amount' => '116.00',
            'amount_paid' => '0.00',
            'amount_due' => '116.00',
            'due_date' => now()->addDay()->toDateString(),
        ]);
        $this->payment = app(PlatformBillingService::class)->createPaymentAttempt(
            invoice: $this->invoice,
            amount: '116.00',
            paymentMethod: 'mpesa',
            gatewayReference: 'ws_CO_callback_001',
        );
        $this->transaction = MpesaTransaction::query()->create([
            'tenant_id' => $this->invoice->tenant_id,
            'mpesa_credential_id' => $this->credential->id,
            'platform_payment_id' => $this->payment->id,
            'account_reference' => $this->invoice->invoice_number,
            'phone_hash' => hash('sha256', '254712345678'),
            'amount' => '116.00',
            'merchant_request_id' => 'merchant-callback-001',
            'checkout_request_id' => 'checkout-callback-001',
            'status' => 'pending',
        ]);
    }

    public function test_verified_callback_confirms_invoice_payment_exactly_once(): void
    {
        $payload = $this->successCallback();
        $url = route('mpesa.callback', $this->credential->callback_token);

        $this->postJson($url, $payload)->assertOk()->assertJsonPath('ResultCode', 0);
        $this->postJson($url, $payload)->assertOk()->assertJsonPath('ResultCode', 0);

        $this->assertSame('confirmed', $this->transaction->fresh()->status);
        $this->assertSame('QWE12345678', $this->transaction->fresh()->provider_receipt);
        $this->assertSame('completed', $this->payment->fresh()->status);
        $this->assertSame('paid', $this->invoice->fresh()->status);
        $this->assertSame('116.00', (string) $this->invoice->fresh()->amount_paid);
        $this->assertDatabaseCount('platform_payments', 1);
    }

    public function test_callback_with_wrong_amount_is_stored_and_does_not_settle(): void
    {
        $payload = $this->successCallback(115);
        $this->postJson(route('mpesa.callback', $this->credential->callback_token), $payload)
            ->assertStatus(202)->assertJsonPath('ResultCode', 1);

        $this->assertSame('pending', $this->transaction->fresh()->status);
        $this->assertSame('pending', $this->payment->fresh()->status);
        $this->assertSame('issued', $this->invoice->fresh()->status);
        $this->assertDatabaseHas('unmatched_mpesa_callbacks', ['reason' => 'amount_mismatch']);
    }

    public function test_failed_callback_marks_attempt_failed_without_invoice_change(): void
    {
        $payload = $this->successCallback();
        $payload['Body']['stkCallback']['ResultCode'] = 1032;

        $this->postJson(route('mpesa.callback', $this->credential->callback_token), $payload)
            ->assertOk()->assertJsonPath('ResultCode', 0);

        $this->assertSame('failed', $this->transaction->fresh()->status);
        $this->assertSame('pending', $this->payment->fresh()->status);
        $this->assertSame('issued', $this->invoice->fresh()->status);
    }

    public function test_mismatched_checkout_is_saved_for_operations_review(): void
    {
        $payload = $this->successCallback();
        $payload['Body']['stkCallback']['CheckoutRequestID'] = 'checkout-from-another-credential';

        $this->postJson(route('mpesa.callback', $this->credential->callback_token), $payload)->assertStatus(202);
        $this->assertSame('pending', $this->transaction->fresh()->status);
        $this->assertDatabaseHas('unmatched_mpesa_callbacks', ['reason' => 'no_matching_pending_transaction']);
    }

    private function successCallback(int|float $amount = 116): array
    {
        return [
            'Body' => [
                'stkCallback' => [
                    'MerchantRequestID' => 'merchant-callback-001',
                    'CheckoutRequestID' => 'checkout-callback-001',
                    'ResultCode' => 0,
                    'ResultDesc' => 'Success',
                    'CallbackMetadata' => [
                        'Item' => [
                            ['Name' => 'Amount', 'Value' => $amount],
                            ['Name' => 'MpesaReceiptNumber', 'Value' => 'QWE12345678'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
