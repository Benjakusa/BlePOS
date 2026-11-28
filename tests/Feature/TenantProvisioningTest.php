<?php

namespace Tests\Feature;

use App\Models\ProvisioningRun;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\User;
use App\Services\ProvisioningEngine;
use Database\Seeders\ModuleCatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantProvisioningTest extends TestCase
{
    use RefreshDatabase;

    public function test_provisioning_engine_initiates_and_executes_30_steps_successfully(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        $engine = new ProvisioningEngine;

        $payload = [
            'legal_name' => 'Kenyatta Avenue Chemist Ltd',
            'trading_name' => 'Kenyatta Chemist',
            'code' => 'KAC01',
            'slug' => 'kenyatta-chemist',
            'kra_pin' => 'P051234567A',
            'business_type' => 'retail',
            'industry' => 'pharmacy',
            'owner_name' => 'Dr. Jane Mwangi',
            'owner_email' => 'jane@kenyattachemist.co.ke',
            'owner_phone' => '+254712345678',
            'domain' => 'pos.kenyattachemist.co.ke',
            'initial_shop_name' => 'CBD Main Pharmacy',
            'initial_shop_code' => 'CBD',
        ];

        // 1. Initiate provisioning run
        $run = $engine->initiate($payload);

        $this->assertInstanceOf(ProvisioningRun::class, $run);
        $this->assertEquals(30, $run->steps()->count());
        $this->assertEquals('in_progress', $run->status);

        $tenant = $run->tenant;
        $this->assertEquals('provisioning', $tenant->status);

        // 2. Execute provisioning run
        $success = $engine->execute($run);

        $this->assertTrue($success);
        $this->assertEquals('completed', $run->fresh()->status);
        $this->assertEquals('trial', $tenant->fresh()->status);

        // Assert all 30 steps completed
        $this->assertEquals(30, $run->steps()->where('status', 'completed')->count());

        // Assert domain mapping created
        $this->assertDatabaseHas('tenant_domains', [
            'tenant_id' => $tenant->id,
            'domain' => 'pos.kenyattachemist.co.ke',
        ]);

        // Assert default roles created
        $this->assertDatabaseHas('tenant_roles', [
            'tenant_id' => $tenant->id,
            'slug' => 'owner',
        ]);
        $this->assertDatabaseHas('tenant_roles', [
            'tenant_id' => $tenant->id,
            'slug' => 'cashier',
        ]);
        $this->assertDatabaseHas('tenant_roles', [
            'tenant_id' => $tenant->id,
            'slug' => 'dispensing-pharmacist',
        ]);
        $this->assertDatabaseHas('tenant_roles', [
            'tenant_id' => $tenant->id,
            'slug' => 'stock-controller',
        ]);
        $this->assertDatabaseMissing('tenant_roles', [
            'tenant_id' => $tenant->id,
            'slug' => 'waiter',
        ]);

        // Assert owner user created and assigned to owner role
        $owner = User::where('tenant_id', $tenant->id)->where('email', 'jane@kenyattachemist.co.ke')->first();
        $this->assertNotNull($owner);
        $this->assertTrue($owner->hasRole('owner'));
        $this->assertSame(1, User::where('tenant_id', $tenant->id)->count(), 'Role templates must not create employee accounts.');

        // Assert initial shop and register created
        $shop = Shop::where('tenant_id', $tenant->id)->where('code', 'CBD')->first();
        $this->assertNotNull($shop);
        $this->assertDatabaseHas('registers', [
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'code' => 'REG-01',
        ]);
    }

    public function test_provisioning_failure_records_error_and_supports_idempotent_retry(): void
    {
        $engine = new ProvisioningEngine;

        // Register with an existing domain belonging to another tenant to trigger Step 2 failure
        $otherTenant = Tenant::factory()->create();
        TenantDomain::create([
            'tenant_id' => $otherTenant->id,
            'domain' => 'taken.blepos.co.ke',
        ]);

        $payload = [
            'legal_name' => 'Failing Pharmacy',
            'code' => 'FAIL01',
            'owner_name' => 'Test Owner',
            'owner_email' => 'fail@example.com',
            'domain' => 'taken.blepos.co.ke', // Duplicate domain
        ];

        $run = $engine->initiate($payload);
        $success = $engine->execute($run);

        $this->assertFalse($success);
        $run->refresh();
        $this->assertEquals('failed', $run->status);
        $this->assertEquals('provisioning_failed', $run->tenant->status);

        // Assert Step 2 failed
        $step2 = $run->steps()->where('step_number', 2)->first();
        $this->assertEquals('failed', $step2->status);
        $this->assertStringContainsString('already registered', $step2->error_message);

        // Now fix the payload domain and retry
        $payload['domain'] = 'unique-pharmacy.blepos.co.ke';
        $run->update(['payload' => $payload]);

        $retrySuccess = $engine->retry($run);
        $this->assertTrue($retrySuccess);
        $this->assertEquals('completed', $run->fresh()->status);
        $this->assertEquals('trial', $run->tenant->fresh()->status);
    }
}
