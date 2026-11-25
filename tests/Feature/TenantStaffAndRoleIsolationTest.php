<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantRole;
use App\Models\User;
use Database\Seeders\RestaurantTenantSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantStaffAndRoleIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RestaurantTenantSeeder::class);
    }

    public function test_tenant_owner_has_unrestricted_access_to_all_modules(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->first();

        // 1. Can view Shifts
        $this->actingAs($owner, 'web')->get(route('tenant.shifts.index'))->assertStatus(200);

        // 2. Can view Catalog products & categories
        $this->actingAs($owner, 'web')->get(route('tenant.products.index'))->assertStatus(200);
        $this->actingAs($owner, 'web')->get(route('tenant.categories.index'))->assertStatus(200);

        // 3. Can view Inventory & adjustments
        $this->actingAs($owner, 'web')->get(route('tenant.inventory.index'))->assertStatus(200);
        $this->actingAs($owner, 'web')->get(route('tenant.inventory.adjust'))->assertStatus(200);

        // 4. Can view Finance & Ledgers
        $this->actingAs($owner, 'web')->get(route('tenant.accounting.accounts.index'))->assertStatus(200);
        $this->actingAs($owner, 'web')->get(route('tenant.accounting.reports.trial-balance'))->assertStatus(200);

        // 5. Can view Staff & Team Management
        $this->actingAs($owner, 'web')->get(route('tenant.users.index'))->assertStatus(200);

        // 6. POS Checkout requires open shift, so without open shift it redirects to shifts.index with 302
        $this->actingAs($owner, 'web')->get(route('tenant.pos.checkout'))->assertRedirect(route('tenant.shifts.index'));
    }

    public function test_cashier_can_access_pos_but_cannot_access_inventory_accounting_or_staff(): void
    {
        $cashier = User::where('email', 'restaurant.cashier@blepos.co.ke')->first();

        // Allowed: Shifts and sales history
        $this->actingAs($cashier, 'web')->get(route('tenant.shifts.index'))->assertStatus(200);
        $this->actingAs($cashier, 'web')->get(route('tenant.pos.sales'))->assertStatus(200);
        $this->actingAs($cashier, 'web')->get(route('tenant.pos.checkout'))->assertRedirect(route('tenant.shifts.index'));

        // Denied: Catalog management
        $this->actingAs($cashier, 'web')->get(route('tenant.products.index'))->assertStatus(403);
        $this->actingAs($cashier, 'web')->get(route('tenant.categories.index'))->assertStatus(403);

        // Denied: Inventory
        $this->actingAs($cashier, 'web')->get(route('tenant.inventory.index'))->assertStatus(403);
        $this->actingAs($cashier, 'web')->get(route('tenant.inventory.adjust'))->assertStatus(403);

        // Denied: Accounting
        $this->actingAs($cashier, 'web')->get(route('tenant.accounting.accounts.index'))->assertStatus(403);

        // Denied: Staff Management
        $this->actingAs($cashier, 'web')->get(route('tenant.users.index'))->assertStatus(403);
    }

    public function test_inventory_officer_can_access_stock_but_cannot_access_pos_accounting_or_staff(): void
    {
        $inventoryOfficer = User::where('email', 'restaurant.inventory@blepos.co.ke')->first();

        // Allowed: Inventory stock levels & adjustments
        $this->actingAs($inventoryOfficer, 'web')->get(route('tenant.inventory.index'))->assertStatus(200);
        $this->actingAs($inventoryOfficer, 'web')->get(route('tenant.inventory.adjust'))->assertStatus(200);
        $this->actingAs($inventoryOfficer, 'web')->get(route('tenant.inventory.receive'))->assertStatus(200);
        $this->actingAs($inventoryOfficer, 'web')->get(route('tenant.inventory.movements'))->assertStatus(200);

        // Denied: POS Terminal & Shifts
        $this->actingAs($inventoryOfficer, 'web')->get(route('tenant.pos.checkout'))->assertStatus(403);
        $this->actingAs($inventoryOfficer, 'web')->get(route('tenant.shifts.index'))->assertStatus(403);

        // Denied: Accounting
        $this->actingAs($inventoryOfficer, 'web')->get(route('tenant.accounting.accounts.index'))->assertStatus(403);

        // Denied: Staff Management
        $this->actingAs($inventoryOfficer, 'web')->get(route('tenant.users.index'))->assertStatus(403);
    }

    public function test_accountant_can_access_financials_but_cannot_access_pos_checkout_or_staff(): void
    {
        $accountant = User::where('email', 'restaurant.accountant@blepos.co.ke')->first();

        // Allowed: Accounting reports, journals, accounts
        $this->actingAs($accountant, 'web')->get(route('tenant.accounting.accounts.index'))->assertStatus(200);
        $this->actingAs($accountant, 'web')->get(route('tenant.accounting.reports.trial-balance'))->assertStatus(200);
        $this->actingAs($accountant, 'web')->get(route('tenant.accounting.reports.profit-loss'))->assertStatus(200);
        $this->actingAs($accountant, 'web')->get(route('tenant.pos.sales'))->assertStatus(200);

        // Denied: POS checkout & inventory adjusting
        $this->actingAs($accountant, 'web')->get(route('tenant.pos.checkout'))->assertStatus(403);
        $this->actingAs($accountant, 'web')->get(route('tenant.inventory.adjust'))->assertStatus(403);

        // Denied: Staff Management
        $this->actingAs($accountant, 'web')->get(route('tenant.users.index'))->assertStatus(403);
    }

    public function test_tenant_owner_can_create_new_staff_member(): void
    {
        $owner = User::where('email', 'restaurant.owner@blepos.co.ke')->first();
        $tenant = Tenant::where('slug', 'savanna-bistro-grill')->first();
        $cashierRole = TenantRole::where('tenant_id', $tenant->id)->where('slug', 'cashier')->first();

        $response = $this->actingAs($owner, 'web')->post(route('tenant.users.store'), [
            'name' => 'Alice Barista',
            'email' => 'alice.barista@savannabistro.co.ke',
            'phone' => '+254700998877',
            'password' => 'Barista@123456!',
            'role_ids' => [$cashierRole->id],
            'status' => 'active',
        ]);

        $response->assertRedirect(route('tenant.users.index'));
        $this->assertDatabaseHas('users', [
            'tenant_id' => $tenant->id,
            'email' => 'alice.barista@savannabistro.co.ke',
            'name' => 'Alice Barista',
        ]);
    }
}
