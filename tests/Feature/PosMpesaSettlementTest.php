<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\InventoryStock;
use App\Models\MpesaCredential;
use App\Models\MpesaTransaction;
use App\Models\Product;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\User;
use App\Services\InventoryService;
use Database\Seeders\MpesaSandboxSeeder;
use Database\Seeders\RestaurantTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PosMpesaSettlementTest extends TestCase
{
    use RefreshDatabase;

    private User $cashier;

    private Shop $shop;

    private Register $register;

    private CashierShift $shift;

    private Product $product;

    private MpesaCredential $credential;

    private int $stkAttempt = 0;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RestaurantTenantSeeder::class);
        $this->seed(MpesaSandboxSeeder::class);

        $this->cashier = User::query()->where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $this->shop = Shop::query()->where('tenant_id', $this->cashier->tenant_id)->firstOrFail();
        $this->register = Register::query()->where('shop_id', $this->shop->id)->firstOrFail();
        $this->shift = CashierShift::query()->create([
            'tenant_id' => $this->shop->tenant_id,
            'shop_id' => $this->shop->id,
            'register_id' => $this->register->id,
            'user_id' => $this->cashier->id,
            'shift_number' => 'SH-MPESA-001',
            'status' => 'open',
            'opening_float' => '0.00',
            'cash_sales' => '0.00',
            'mpesa_sales' => '0.00',
            'card_sales' => '0.00',
            'opened_at' => now(),
        ]);
        $this->product = Product::query()->create([
            'tenant_id' => $this->shop->tenant_id,
            'name' => 'STK Test Product',
            'sku' => 'STK-001',
            'cost_price' => '100.00',
            'selling_price' => '500.00',
            'tax_rate' => '0.00',
            'tax_type' => 'none',
            'track_stock' => true,
            'is_active' => true,
        ]);
        app(InventoryService::class)->receiveStock($this->product, $this->shop, 10, $this->cashier, 'STK-OPENING');
        $this->credential = MpesaCredential::query()
            ->where('owner_type', 'branch')->where('branch_id', $this->shop->id)->firstOrFail();

        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response(['access_token' => 'stk-test-token'], 200),
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => function () {
                $this->stkAttempt++;

                return Http::response([
                    'MerchantRequestID' => 'merchant-pos-'.$this->stkAttempt,
                    'CheckoutRequestID' => 'checkout-pos-'.$this->stkAttempt,
                    'ResponseCode' => '0',
                    'CustomerMessage' => 'STK sent',
                ], 200);
            },
        ]);
    }

    public function test_successful_callback_completes_sale_and_deducts_stock_once(): void
    {
        $transaction = $this->startStkSale();
        $payload = $this->callbackPayload((int) $transaction->amount, $transaction);
        $callbackUrl = route('mpesa.callback', $this->credential->callback_token);

        $this->postJson($callbackUrl, $payload)->assertOk()->assertJsonPath('ResultCode', 0);
        $this->postJson($callbackUrl, $payload)->assertOk()->assertJsonPath('ResultCode', 0);

        $sale = Sale::withoutGlobalScopes()->findOrFail($transaction->sale_id);
        $this->assertSame('completed', $sale->status);
        $this->assertSame('paid', $sale->payment_status);
        $this->assertSame('500.00', (string) $sale->paid_total);
        $this->assertSame('confirmed', $transaction->fresh()->status);
        $this->assertSame('QWE'.str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT), $sale->payments()->firstOrFail()->reference);
        $this->assertSame(9.0, $this->product->fresh()->getStockInShop($this->shop));
        $this->assertSame('500.00', (string) $this->shift->fresh()->mpesa_sales);
        $this->assertSame(1, $sale->payments()->count());
        $this->assertSame(1, $sale->items()->count());
        $this->assertSame('0.0000', (string) InventoryStock::withoutGlobalScopes()->where('shop_id', $this->shop->id)->where('product_id', $this->product->id)->value('reserved_quantity'));
    }

    public function test_amount_mismatch_does_not_complete_sale_or_change_stock(): void
    {
        $transaction = $this->startStkSale();

        $this->postJson(route('mpesa.callback', $this->credential->callback_token), $this->callbackPayload(501, $transaction))
            ->assertStatus(202)->assertJsonPath('ResultCode', 1);

        $sale = Sale::withoutGlobalScopes()->findOrFail($transaction->sale_id);
        $this->assertSame('pending_payment', $sale->status);
        $this->assertSame('pending', $transaction->fresh()->status);
        $this->assertSame(10.0, $this->product->fresh()->getStockInShop($this->shop));
        $this->assertSame('1.0000', (string) InventoryStock::withoutGlobalScopes()->where('shop_id', $this->shop->id)->where('product_id', $this->product->id)->value('reserved_quantity'));
        $this->assertDatabaseHas('unmatched_mpesa_callbacks', ['reason' => 'amount_mismatch']);
    }

    public function test_timeout_notifies_cashier_and_sale_can_still_settle_from_late_callback(): void
    {
        $transaction = $this->startStkSale();
        $this->travel(11)->minutes();
        $this->artisan('mpesa:expire-pending-transactions')->assertExitCode(0);

        $this->assertSame('timed_out', $transaction->fresh()->status);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->cashier->id]);
        $this->assertSame('0.0000', (string) InventoryStock::withoutGlobalScopes()->where('shop_id', $this->shop->id)->where('product_id', $this->product->id)->value('reserved_quantity'));
        $this->postJson(route('mpesa.callback', $this->credential->callback_token), $this->callbackPayload(500, $transaction))
            ->assertOk()->assertJsonPath('ResultCode', 0);

        $this->assertSame('completed', Sale::withoutGlobalScopes()->findOrFail($transaction->sale_id)->status);
        $this->assertSame(9.0, $this->product->fresh()->getStockInShop($this->shop));
        $this->assertSame('0.0000', (string) InventoryStock::withoutGlobalScopes()->where('shop_id', $this->shop->id)->where('product_id', $this->product->id)->value('reserved_quantity'));
    }

    public function test_second_paid_attempt_for_same_sale_is_flagged_for_refund_review(): void
    {
        $first = $this->startStkSale();
        $this->travel(11)->minutes();
        $this->artisan('mpesa:expire-pending-transactions')->assertExitCode(0);
        $second = $this->startStkSale();
        $this->assertSame($first->sale_id, $second->sale_id);

        $this->postJson(route('mpesa.callback', $this->credential->callback_token), $this->callbackPayload(500, $first))
            ->assertOk();
        $this->postJson(route('mpesa.callback', $this->credential->callback_token), $this->callbackPayload(500, $second))
            ->assertOk()->assertJsonPath('ResultDesc', 'Payment received; settlement requires refund review.');

        $this->assertSame('completed', Sale::withoutGlobalScopes()->findOrFail($first->sale_id)->status);
        $this->assertSame('confirmed', $first->fresh()->status);
        $this->assertSame('refund_required', $second->fresh()->status);
        $this->assertSame('QWE00000002', $second->fresh()->provider_receipt);
        $this->assertSame(9.0, $this->product->fresh()->getStockInShop($this->shop));
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->cashier->id]);
        $this->artisan('mpesa:report-unsettled-sales')->assertExitCode(1);
    }

    public function test_out_of_stock_at_callback_flags_paid_attempt_for_refund_and_releases_reservation(): void
    {
        $transaction = $this->startStkSale();
        InventoryStock::withoutGlobalScopes()->where('shop_id', $this->shop->id)->where('product_id', $this->product->id)->update(['quantity' => '0.0000']);

        $this->postJson(route('mpesa.callback', $this->credential->callback_token), $this->callbackPayload(500, $transaction))
            ->assertOk()->assertJsonPath('ResultDesc', 'Payment received; settlement requires refund review.');

        $this->assertSame('pending_payment', Sale::withoutGlobalScopes()->findOrFail($transaction->sale_id)->status);
        $this->assertSame('refund_required', $transaction->fresh()->status);
        $this->assertSame('0.0000', (string) InventoryStock::withoutGlobalScopes()->where('shop_id', $this->shop->id)->where('product_id', $this->product->id)->value('reserved_quantity'));
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $this->cashier->id]);
    }

    public function test_same_idempotency_key_cannot_create_another_sale_or_stk_attempt_while_pending(): void
    {
        $transaction = $this->startStkSale();
        $response = $this->actingAs($this->cashier, 'web')->postJson(route('tenant.pos.mpesa.stk-push'), [
            'shift_id' => $this->shift->id,
            'shop_id' => $this->shop->id,
            'register_id' => $this->register->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'phone_number' => '0712345678',
        ], ['Idempotency-Key' => 'pos-stk-test-001']);

        $response->assertStatus(409);
        $this->assertSame(1, Sale::withoutGlobalScopes()->where('tenant_id', $this->shop->tenant_id)->count());
        $this->assertSame(1, MpesaTransaction::query()->where('sale_id', $transaction->sale_id)->count());
    }

    private function startStkSale(): MpesaTransaction
    {
        $response = $this->actingAs($this->cashier, 'web')->postJson(route('tenant.pos.mpesa.stk-push'), [
            'shift_id' => $this->shift->id,
            'shop_id' => $this->shop->id,
            'register_id' => $this->register->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'phone_number' => '0712345678',
        ], ['Idempotency-Key' => 'pos-stk-test-001']);

        $response->assertStatus(202)->assertJsonPath('success', true);
        $transaction = MpesaTransaction::query()->where('sale_id', $response->json('sale_id'))->latest('id')->firstOrFail();
        $this->assertDatabaseHas('sales', ['id' => $response->json('sale_id'), 'status' => 'pending_payment']);
        $this->assertSame(10.0, $this->product->fresh()->getStockInShop($this->shop));
        $this->assertSame('1.0000', (string) InventoryStock::withoutGlobalScopes()->where('shop_id', $this->shop->id)->where('product_id', $this->product->id)->value('reserved_quantity'));
        $this->assertNull($this->credential->fresh()->getRawOriginal('callback_token'));
        $this->assertSame(hash('sha256', $this->credential->callback_token), $this->credential->fresh()->callback_token_hash);

        return $transaction;
    }

    private function callbackPayload(int $amount, MpesaTransaction $transaction): array
    {
        return [
            'Body' => ['stkCallback' => [
                'MerchantRequestID' => $transaction->merchant_request_id,
                'CheckoutRequestID' => $transaction->checkout_request_id,
                'ResultCode' => 0,
                'ResultDesc' => 'Success',
                'CallbackMetadata' => ['Item' => [
                    ['Name' => 'Amount', 'Value' => $amount],
                    ['Name' => 'MpesaReceiptNumber', 'Value' => 'QWE'.str_pad((string) $transaction->id, 8, '0', STR_PAD_LEFT)],
                ]],
            ]],
        ];
    }
}
