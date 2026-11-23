<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        parent::tearDown();
    }

    public function test_tenant_user_has_role_and_permission_checks(): void
    {
        $tenant = Tenant::factory()->create();
        TenantContext::setTenant($tenant);

        $permSaleCreate = TenantPermission::create([
            'name' => 'sales.create',
            'group' => 'sales',
            'description' => 'Create POS sales',
        ]);
        $permProfitView = TenantPermission::create([
            'name' => 'reports.profit.view',
            'group' => 'reports',
            'description' => 'View business gross profit',
        ]);

        $cashierRole = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Cashier',
            'slug' => 'cashier',
        ]);
        $cashierRole->permissions()->attach($permSaleCreate->id);

        $cashier = User::factory()->forTenant($tenant)->create();
        $cashier->roles()->attach($cashierRole->id);

        $this->assertTrue($cashier->hasRole('cashier'));
        $this->assertFalse($cashier->hasRole('manager'));
        $this->assertTrue($cashier->hasPermission('sales.create'));
        $this->assertFalse($cashier->hasPermission('reports.profit.view'));
    }
}
