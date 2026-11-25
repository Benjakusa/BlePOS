<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\InventoryStock;
use App\Models\PharmacyBatch;
use App\Models\PharmacyDispensingLog;
use App\Models\Product;
use App\Models\Register;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PharmacyService;
use Database\Seeders\PharmacyDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyCheckoutSafetyTest extends TestCase
{
    use RefreshDatabase;

    private Tenant $tenant;

    private Shop $shop;

    private Register $register;

    private User $pharmacist;

    private CashierShift $shift;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PharmacyDemoSeeder::class);

        $this->tenant = Tenant::withoutGlobalScopes()->where('slug', 'afyacare-pharmacy-demo')->firstOrFail();
        $this->shop = Shop::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->where('code', 'AF-CBD')->firstOrFail();
        $this->register = Register::withoutGlobalScopes()->where('shop_id', $this->shop->id)->firstOrFail();
        $this->pharmacist = User::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)
            ->where('email', 'pharmacist.cbd@afyacare.test')->firstOrFail();
        $this->shift = CashierShift::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'shop_id' => $this->shop->id,
            'register_id' => $this->register->id,
            'user_id' => $this->pharmacist->id,
            'shift_number' => 'SH-PHARMACY-TEST',
            'status' => 'open',
            'opening_float' => 500,
            'cash_in' => 0,
            'cash_out' => 0,
            'cash_sales' => 0,
            'mpesa_sales' => 0,
            'card_sales' => 0,
            'expected_cash' => 500,
            'opened_at' => now(),
        ]);
    }

    public function test_pharmacy_checkout_blocks_expired_batches_without_changing_stock(): void
    {
        $product = $this->product('AFY-OTC-001');
        $batch = $this->batch($product, 'EXPIRED-TEST', now()->subDay()->toDateString(), 5);
        $stockBefore = InventoryStock::withoutGlobalScopes()->where('shop_id', $this->shop->id)
            ->where('product_id', $product->id)->value('quantity');

        $response = $this->checkout($product, ['batch_id' => $batch->id]);

        $response->assertUnprocessable()->assertJsonValidationErrors('batch');
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('pharmacy_dispensing_logs', 0);
        $this->assertSame((string) $stockBefore, (string) InventoryStock::withoutGlobalScopes()
            ->where('shop_id', $this->shop->id)->where('product_id', $product->id)->value('quantity'));
    }

    public function test_pharmacy_checkout_requires_a_valid_prescription_for_rx_products(): void
    {
        $product = $this->product('AFY-RX-001');
        $batch = $this->batch($product, 'RX-VALID-BATCH', now()->addYear()->toDateString(), 5);

        $response = $this->checkout($product, ['batch_id' => $batch->id]);

        $response->assertUnprocessable()->assertJsonValidationErrors('prescription');
        $this->assertDatabaseCount('sales', 0);
        $this->assertSame('5.0000', (string) PharmacyBatch::withoutGlobalScopes()->findOrFail($batch->id)->current_quantity);
    }

    public function test_pharmacy_checkout_records_batch_depletion_and_dispensing_audit(): void
    {
        $product = $this->product('AFY-OTC-001');
        $batch = PharmacyBatch::withoutGlobalScopes()->where('shop_id', $this->shop->id)
            ->where('product_id', $product->id)->firstOrFail();
        $before = (float) $batch->current_quantity;

        $response = $this->checkout($product, ['batch_id' => $batch->id]);

        $response->assertOk()->assertJsonPath('success', true);
        $this->assertSame($before - 1, (float) PharmacyBatch::withoutGlobalScopes()->findOrFail($batch->id)->current_quantity);
        $this->assertDatabaseHas('pharmacy_dispensing_logs', [
            'sale_id' => $response->json('sale_id'),
            'batch_id' => $batch->id,
            'product_id' => $product->id,
            'user_id' => $this->pharmacist->id,
            'quantity' => 1,
        ]);
        $this->assertSame(1, PharmacyDispensingLog::withoutGlobalScopes()->where('sale_id', $response->json('sale_id'))->count());
    }

    public function test_checkout_cannot_claim_another_user_as_the_controlled_drug_pharmacist(): void
    {
        $product = $this->product('AFY-OTC-001');
        $product->forceFill(['metadata' => array_merge($product->metadata ?? [], ['is_controlled_substance' => true])])->save();
        $batch = PharmacyBatch::withoutGlobalScopes()->where('shop_id', $this->shop->id)
            ->where('product_id', $product->id)->firstOrFail();
        $otherUser = User::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)
            ->where('email', 'cashier.cbd@afyacare.test')->firstOrFail();

        $response = $this->checkout($product, [
            'batch_id' => $batch->id,
            'pharmacist_user_id' => $otherUser->id,
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('sales', 0);
        $this->assertDatabaseCount('pharmacy_dispensing_logs', 0);
    }

    public function test_log_dispensing_accepts_valid_depletion_and_commits_batch_and_log_atomically(): void
    {
        $product = $this->product('AFY-OTC-001');
        $batch = $this->batch($product, 'ATOMIC-LOG-TEST', now()->addYear()->toDateString(), 5);

        $log = app(PharmacyService::class)->logDispensing(
            shop: $this->shop,
            user: $this->pharmacist,
            product: $product,
            quantity: 4,
            patientName: 'Test Patient',
            batch: $batch,
        );

        $this->assertSame(1.0, (float) PharmacyBatch::withoutGlobalScopes()->findOrFail($batch->id)->current_quantity);
        $this->assertSame(4.0, (float) $log->quantity);
        $this->assertDatabaseHas('pharmacy_dispensing_logs', ['id' => $log->id, 'batch_id' => $batch->id]);
    }

    private function checkout(Product $product, array $itemOverrides = [])
    {
        return $this->actingAs($this->pharmacist, 'web')
            ->withHeader('Idempotency-Key', 'pharmacy-test-'.uniqid())
            ->postJson(route('tenant.pos.process'), [
                'shift_id' => $this->shift->id,
                'shop_id' => $this->shop->id,
                'register_id' => $this->register->id,
                'items' => [array_merge([
                    'product_id' => $product->id,
                    'quantity' => 1,
                ], $itemOverrides)],
                'payments' => [[
                    'method' => 'cash',
                    'amount' => $product->selling_price,
                ]],
            ]);
    }

    private function product(string $sku): Product
    {
        return Product::withoutGlobalScopes()->where('tenant_id', $this->tenant->id)->where('sku', $sku)->firstOrFail();
    }

    private function batch(Product $product, string $number, string $expiry, float $quantity): PharmacyBatch
    {
        return PharmacyBatch::withoutGlobalScopes()->create([
            'tenant_id' => $this->tenant->id,
            'shop_id' => $this->shop->id,
            'product_id' => $product->id,
            'batch_number' => $number,
            'supplier_name' => 'Test Supplier',
            'manufacturing_date' => now()->subMonth()->toDateString(),
            'expiry_date' => $expiry,
            'initial_quantity' => $quantity,
            'current_quantity' => $quantity,
            'unit_cost' => $product->cost_price,
            'is_quarantined' => false,
        ]);
    }
}
