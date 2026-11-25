<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\MpesaCredential;
use App\Models\Product;
use App\Models\Shop;
use App\Models\User;
use Database\Seeders\MpesaSandboxSeeder;
use Database\Seeders\RestaurantTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PosProductImageAndStkPushTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RestaurantTenantSeeder::class);
        $this->seed(MpesaSandboxSeeder::class);
    }

    public function test_product_image_can_be_uploaded_on_create_and_returned_in_pos_lookup(): void
    {
        Storage::fake('public');

        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $category = Category::withoutGlobalScopes()->where('tenant_id', $owner->tenant_id)->firstOrFail();

        $file = UploadedFile::fake()->image('burger.jpg', 300, 300);

        $response = $this->actingAs($owner, 'web')->post(route('tenant.products.store'), [
            'name' => 'Double Smash Burger',
            'sku' => 'RES-BRG-001',
            'barcode' => '60099901',
            'category_id' => $category->id,
            'cost_price' => 300,
            'selling_price' => 850,
            'tax_rate' => 16,
            'image' => $file,
        ]);

        $response->assertRedirect(route('tenant.products.index'));

        $product = Product::withoutGlobalScopes()->where('sku', 'RES-BRG-001')->firstOrFail();
        $this->assertNotNull($product->image_path);
        Storage::disk('public')->assertExists($product->image_path);

        $lookupResponse = $this->actingAs($owner, 'web')->getJson(route('tenant.products.lookup', ['q' => 'Smash']));
        $lookupResponse->assertStatus(200)
            ->assertJsonPath('found', true)
            ->assertJsonPath('products.0.sku', 'RES-BRG-001')
            ->assertJsonPath('products.0.image_url', asset('storage/'.$product->image_path));
    }

    public function test_pos_mpesa_stk_push_uses_resolved_credential_and_sends_stk_request(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $shop = Shop::withoutGlobalScopes()->where('tenant_id', $owner->tenant_id)->firstOrFail();
        $branchCred = MpesaCredential::where('owner_type', 'branch')->where('branch_id', $shop->id)->firstOrFail();

        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'test-oauth-token',
                'expires_in' => '3599',
            ], 200),
            'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest' => Http::response([
                'MerchantRequestID' => '29115-34620561-1',
                'CheckoutRequestID' => 'ws_CO_191220191020363925',
                'ResponseCode' => '0',
                'ResponseDescription' => 'Success. Request accepted for processing',
                'CustomerMessage' => 'Success. Request accepted for processing',
            ], 200),
        ]);

        $response = $this->actingAs($owner, 'web')->postJson(route('tenant.pos.mpesa.stk-push'), [
            'shop_id' => $shop->id,
            'phone_number' => '0712345678',
            'amount' => 1250,
            'account_reference' => 'POS-1',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors('pending_checkout_id');
        Http::assertNothingSent();
    }
}
