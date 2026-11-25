<?php

namespace Tests\Feature;

use App\Models\MpesaCredential;
use App\Models\MpesaCredentialAudit;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\MpesaCredentialResolver;
use App\Services\MpesaEncryptionService;
use App\Services\TenantContext;
use Database\Seeders\HotelTenantSeeder;
use Database\Seeders\MpesaSandboxSeeder;
use Database\Seeders\RestaurantTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class MpesaCredentialVaultTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RestaurantTenantSeeder::class);
        $this->seed(HotelTenantSeeder::class);
    }

    public function test_encryption_round_trip_preserves_plaintext_and_uses_random_iv(): void
    {
        $enc = app(MpesaEncryptionService::class);
        $original = 'my-super-secret-consumer-key-12345';

        $encryptedA = $enc->encrypt($original);
        $encryptedB = $enc->encrypt($original);

        $this->assertNotEquals($original, $encryptedA);
        $this->assertNotEquals($encryptedA, $encryptedB);
        $this->assertEquals($original, $enc->decrypt($encryptedA));
        $this->assertEquals($original, $enc->decrypt($encryptedB));
    }

    public function test_tenant_owner_can_create_mpesa_credential_and_secrets_are_encrypted_at_rest(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $plainSecret = 'my-plain-consumer-secret-xyz';
        $plainPasskey = 'my-plain-passkey-xyz';

        $response = $this->actingAs($owner, 'web')->post(route('tenant.mpesa.store'), [
            'owner_type' => 'tenant',
            'environment' => 'sandbox',
            'shortcode' => '174379',
            'passkey' => $plainPasskey,
            'consumer_key' => 'test-consumer-key-1234',
            'consumer_secret' => $plainSecret,
            'callback_url' => 'https://example.com/callback',
            'is_active' => '1',
            'is_default' => '1',
        ]);

        $response->assertRedirect(route('tenant.mpesa.index'));

        $row = DB::table('mpesa_credentials')
            ->where('tenant_id', $owner->tenant_id)
            ->where('shortcode', '174379')
            ->first();

        $this->assertNotNull($row);
        $this->assertNotEquals($plainSecret, $row->consumer_secret);
        $this->assertNotEquals($plainPasskey, $row->passkey);
        $this->assertStringNotContainsString($plainSecret, $row->consumer_secret);
    }

    public function test_secrets_never_appear_in_responses_or_serialized_models(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $plainSecret = 'ultra-secret-must-never-leak-9999';

        $this->actingAs($owner, 'web')->post(route('tenant.mpesa.store'), [
            'owner_type' => 'tenant',
            'environment' => 'sandbox',
            'shortcode' => '174399',
            'passkey' => 'passkey-secret-value',
            'consumer_key' => 'consumer-key-value',
            'consumer_secret' => $plainSecret,
            'callback_url' => 'https://example.com/cb',
        ]);

        $indexResponse = $this->actingAs($owner, 'web')->get(route('tenant.mpesa.index'));
        $indexResponse->assertStatus(200);
        $indexResponse->assertDontSee($plainSecret);
        $indexResponse->assertSee('••••9999');

        $credential = MpesaCredential::where('shortcode', '174399')->firstOrFail();
        $array = $credential->toArray();
        $this->assertArrayNotHasKey('consumer_secret', $array);
        $this->assertArrayNotHasKey('consumer_key', $array);
        $this->assertArrayNotHasKey('passkey', $array);
    }

    public function test_tenant_a_cannot_read_edit_or_delete_tenant_b_credentials(): void
    {
        $tenantA = Tenant::where('slug', 'savanna-bistro-grill')->firstOrFail();
        $enc = app(MpesaEncryptionService::class);

        $credA = MpesaCredential::create([
            'owner_type' => 'tenant',
            'owner_id' => $tenantA->id,
            'tenant_id' => $tenantA->id,
            'environment' => 'sandbox',
            'shortcode' => '999001',
            'passkey' => $enc->encrypt('pk'),
            'consumer_key' => $enc->encrypt('ck'),
            'consumer_secret' => $enc->encrypt('cs'),
            'callback_url' => 'https://example.com/a',
            'is_active' => true,
        ]);

        $tenantB = Tenant::where('slug', 'grand-horizon-hotel')->firstOrFail();
        $ownerB = User::withoutGlobalScopes()
            ->where('tenant_id', $tenantB->id)
            ->where('email', 'hotel.owner@blepos.co.ke')
            ->firstOrFail();

        $this->actingAs($ownerB, 'web')->get(route('tenant.mpesa.edit', $credA))->assertStatus(404);
        $this->actingAs($ownerB, 'web')->delete(route('tenant.mpesa.destroy', $credA))->assertStatus(404);
        $this->actingAs($ownerB, 'web')->post(route('tenant.mpesa.test', $credA))->assertStatus(404);
    }

    public function test_branch_a_user_cannot_read_branch_b_credentials(): void
    {
        $tenant = Tenant::where('slug', 'savanna-bistro-grill')->firstOrFail();
        $enc = app(MpesaEncryptionService::class);

        $branchA = Shop::withoutGlobalScopes()->where('tenant_id', $tenant->id)->firstOrFail();
        $branchB = Shop::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Westlands Branch',
            'code' => 'BR-WEST',
            'is_active' => true,
        ]);

        $credB = MpesaCredential::create([
            'owner_type' => 'branch',
            'owner_id' => $branchB->id,
            'tenant_id' => $tenant->id,
            'branch_id' => $branchB->id,
            'environment' => 'sandbox',
            'shortcode' => '888222',
            'passkey' => $enc->encrypt('pk-b'),
            'consumer_key' => $enc->encrypt('ck-b'),
            'consumer_secret' => $enc->encrypt('cs-b'),
            'callback_url' => 'https://example.com/b',
            'is_active' => true,
        ]);

        // Create a branch-level manager user assigned ONLY to Branch A with users.manage (but NOT shops.all.manage)
        $branchRole = TenantRole::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'slug' => 'branch_manager',
            'name' => 'Branch Manager',
            'description' => 'Scoped branch manager',
        ]);
        $perm = TenantPermission::where('name', 'users.manage')->firstOrFail();
        $branchRole->permissions()->sync([$perm->id]);

        $branchAUser = User::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Branch A Admin',
            'email' => 'branch.a@savanna.co.ke',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $branchAUser->roles()->sync([$branchRole->id]);
        $branchAUser->shops()->syncWithPivotValues([$branchA->id], ['is_primary' => true]);

        $this->actingAs($branchAUser, 'web')->get(route('tenant.mpesa.edit', $credB))->assertStatus(403);
        $this->actingAs($branchAUser, 'web')->delete(route('tenant.mpesa.destroy', $credB))->assertStatus(403);
    }

    public function test_credential_resolver_priority_branch_then_tenant_then_platform(): void
    {
        $enc = app(MpesaEncryptionService::class);
        $tenant = Tenant::where('slug', 'savanna-bistro-grill')->firstOrFail();
        $shop = Shop::withoutGlobalScopes()->where('tenant_id', $tenant->id)->firstOrFail();

        MpesaCredential::create([
            'owner_type' => 'platform', 'owner_id' => null, 'tenant_id' => null, 'branch_id' => null,
            'environment' => 'sandbox', 'shortcode' => '111111',
            'passkey' => $enc->encrypt('pk-plat'), 'consumer_key' => $enc->encrypt('ck-plat'), 'consumer_secret' => $enc->encrypt('cs-plat'),
            'callback_url' => 'https://cb/plat', 'is_active' => true, 'is_default' => true,
        ]);

        MpesaCredential::create([
            'owner_type' => 'tenant', 'owner_id' => $tenant->id, 'tenant_id' => $tenant->id, 'branch_id' => null,
            'environment' => 'sandbox', 'shortcode' => '222222',
            'passkey' => $enc->encrypt('pk-ten'), 'consumer_key' => $enc->encrypt('ck-ten'), 'consumer_secret' => $enc->encrypt('cs-ten'),
            'callback_url' => 'https://cb/ten', 'is_active' => true, 'is_default' => true,
        ]);

        MpesaCredential::create([
            'owner_type' => 'branch', 'owner_id' => $shop->id, 'tenant_id' => $tenant->id, 'branch_id' => $shop->id,
            'environment' => 'sandbox', 'shortcode' => '333333',
            'passkey' => $enc->encrypt('pk-br'), 'consumer_key' => $enc->encrypt('ck-br'), 'consumer_secret' => $enc->encrypt('cs-br'),
            'callback_url' => 'https://cb/br', 'is_active' => true, 'is_default' => true,
        ]);

        $resolver = app(MpesaCredentialResolver::class);

        $branchResolved = $resolver->resolve($tenant->id, $shop->id, 'sandbox');
        $this->assertEquals('333333', $branchResolved['shortcode']);
        $this->assertEquals('branch', $branchResolved['resolved_via']);

        $tenantResolved = $resolver->resolve($tenant->id, null, 'sandbox');
        $this->assertEquals('222222', $tenantResolved['shortcode']);
        $this->assertEquals('tenant', $tenantResolved['resolved_via']);

        $platformResolved = $resolver->resolve(null, null, 'sandbox');
        $this->assertEquals('111111', $platformResolved['shortcode']);
        $this->assertEquals('platform', $platformResolved['resolved_via']);
    }

    public function test_resolver_never_logs_decrypted_secrets(): void
    {
        $enc = app(MpesaEncryptionService::class);
        $plainSecret = 'secret-that-must-never-appear-in-logs';

        MpesaCredential::create([
            'owner_type' => 'platform', 'owner_id' => null, 'tenant_id' => null, 'branch_id' => null,
            'environment' => 'sandbox', 'shortcode' => '555555',
            'passkey' => $enc->encrypt('pk-nolog'), 'consumer_key' => $enc->encrypt('ck-nolog'),
            'consumer_secret' => $enc->encrypt($plainSecret),
            'callback_url' => 'https://cb', 'is_active' => true, 'is_default' => true,
        ]);

        Log::spy();

        app(MpesaCredentialResolver::class)->resolve(null, null, 'sandbox');

        Log::shouldHaveReceived('info')->withArgs(function (string $message, array $context) use ($plainSecret): bool {
            return ! str_contains(json_encode($context), $plainSecret);
        });
    }

    public function test_sandbox_oauth_mock_returns_success_and_logs_audit(): void
    {
        Http::fake([
            'https://sandbox.safaricom.co.ke/oauth/v1/generate*' => Http::response([
                'access_token' => 'mocked-sandbox-oauth-token',
                'expires_in' => '3599',
            ], 200),
        ]);

        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $enc = app(MpesaEncryptionService::class);
        $cred = MpesaCredential::create([
            'owner_type' => 'tenant',
            'owner_id' => $owner->tenant_id,
            'tenant_id' => $owner->tenant_id,
            'environment' => 'sandbox',
            'shortcode' => '174379',
            'passkey' => $enc->encrypt('pk'),
            'consumer_key' => $enc->encrypt('ck'),
            'consumer_secret' => $enc->encrypt('cs'),
            'callback_url' => 'https://example.com/cb',
            'is_active' => true,
        ]);

        $response = $this->actingAs($owner, 'web')->postJson(route('tenant.mpesa.test', $cred));

        $response->assertStatus(200)->assertJson([
            'success' => true,
            'http_code' => 200,
        ]);

        $audit = MpesaCredentialAudit::where('mpesa_credential_id', $cred->id)
            ->where('action', 'tested')
            ->first();
        $this->assertNotNull($audit);
    }

    public function test_callback_token_resolves_correct_tenant_and_branch_without_exposing_secrets(): void
    {
        $this->seed(MpesaSandboxSeeder::class);

        $branchCred = MpesaCredential::where('owner_type', 'branch')->where('shortcode', '174380')->firstOrFail();

        $response = $this->postJson(route('mpesa.callback', $branchCred->callback_token), [
            'Body' => ['stkCallback' => ['ResultCode' => 0]],
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('ResultCode', 0)
            ->assertJsonPath('context.credential_id', $branchCred->id)
            ->assertJsonPath('context.owner_type', 'branch')
            ->assertJsonPath('context.tenant_id', $branchCred->tenant_id)
            ->assertJsonPath('context.branch_id', $branchCred->branch_id);

        $this->assertSame($branchCred->tenant_id, TenantContext::getTenantId());

        $this->postJson(route('mpesa.callback', 'invalid-token-xyz'), [])->assertStatus(403);
    }

    public function test_audit_log_records_actions_without_secrets(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $plainSecret = 'audit-test-secret-no-leak';

        $this->actingAs($owner, 'web')->post(route('tenant.mpesa.store'), [
            'owner_type' => 'tenant',
            'environment' => 'sandbox',
            'shortcode' => '174339',
            'passkey' => 'pk',
            'consumer_key' => 'ck',
            'consumer_secret' => $plainSecret,
            'callback_url' => 'https://example.com/cb',
        ]);

        $audit = MpesaCredentialAudit::where('action', 'created')
            ->where('tenant_id', $owner->tenant_id)
            ->latest('id')
            ->first();

        $this->assertNotNull($audit);
        $this->assertStringNotContainsString($plainSecret, json_encode($audit->changes ?? []));
    }

    public function test_cashier_cannot_access_mpesa_settings(): void
    {
        $cashier = User::where('email', 'restaurant.cashier@blepos.co.ke')->firstOrFail();
        $this->actingAs($cashier, 'web')->get(route('tenant.mpesa.index'))->assertStatus(403);
    }
}
