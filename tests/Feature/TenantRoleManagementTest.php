<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantActivityLog;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\TenantRoleTemplateService;
use Database\Seeders\ModuleCatalogueSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantRoleManagementTest extends TestCase
{
    use RefreshDatabase;

    private function createOwner(): array
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
            'owner_email' => 'owner@roles.test',
            'industry' => 'retail',
        ]);
        $ownerRole = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Business Owner',
            'slug' => 'owner',
            'is_system' => true,
        ]);
        $owner = User::factory()->forTenant($tenant)->create(['email' => $tenant->owner_email]);
        $owner->roles()->attach($ownerRole->id);

        return [$tenant, $owner];
    }

    public function test_owner_can_create_update_and_delete_tenant_custom_roles_with_audit_snapshots(): void
    {
        [, $owner] = $this->createOwner();
        $inventory = TenantPermission::firstOrCreate(['name' => 'inventory.view'], ['group' => 'Inventory', 'description' => 'View stock levels.']);
        $accounting = TenantPermission::firstOrCreate(['name' => 'accounting.view'], ['group' => 'Finance', 'description' => 'View financial ledgers.']);

        $this->actingAs($owner)->post(route('tenant.roles.store'), [
            'name' => 'Inventory Officer',
            'description' => 'Receives and monitors stock.',
            'permission_ids' => [$inventory->id],
        ])->assertRedirect(route('tenant.roles.index'));

        $role = TenantRole::withoutGlobalScopes()->where('tenant_id', $owner->tenant_id)->where('slug', 'inventory-officer')->firstOrFail();
        $this->assertTrue($role->hasPermission('inventory.view'));
        $this->assertFalse($role->hasPermission('accounting.view'));

        $this->actingAs($owner)->put(route('tenant.roles.update', $role), [
            'name' => 'Stock Controller',
            'description' => 'Receives stock and views ledgers when assigned.',
            'permission_ids' => [$inventory->id, $accounting->id],
        ])->assertRedirect(route('tenant.roles.index'));

        $role->refresh();
        $this->assertSame('stock-controller', $role->slug);
        $this->assertTrue($role->hasPermission('accounting.view'));
        $this->assertDatabaseHas('tenant_activity_logs', [
            'tenant_id' => $owner->tenant_id,
            'action' => 'tenant.role.updated',
            'subject_id' => (string) $role->id,
        ]);
        $log = TenantActivityLog::withoutGlobalScopes()->where('action', 'tenant.role.updated')->firstOrFail();
        $this->assertContains('inventory.view', $log->context['before']['permissions']);
        $this->assertContains('accounting.view', $log->context['after']['permissions']);

        $this->actingAs($owner)->delete(route('tenant.roles.destroy', $role))
            ->assertRedirect(route('tenant.roles.index'));
        $this->assertDatabaseMissing('tenant_roles', ['id' => $role->id]);
        $this->assertDatabaseHas('tenant_activity_logs', [
            'tenant_id' => $owner->tenant_id,
            'action' => 'tenant.role.deleted',
            'subject_id' => (string) $role->id,
        ]);
    }

    public function test_staff_without_admin_permission_cannot_manage_roles(): void
    {
        [$tenant] = $this->createOwner();
        $staffRole = TenantRole::create(['tenant_id' => $tenant->id, 'name' => 'Waiter', 'slug' => 'waiter']);
        $staff = User::factory()->forTenant($tenant)->create(['email' => 'waiter@roles.test']);
        $staff->roles()->attach($staffRole->id);

        $this->actingAs($staff)->get(route('tenant.roles.index'))->assertForbidden();
        $this->actingAs($staff)->post(route('tenant.roles.store'), ['name' => 'Admin'])->assertForbidden();
    }

    public function test_unrecognized_operational_role_receives_staff_dashboard_without_owner_metrics(): void
    {
        [$tenant] = $this->createOwner();
        $waiterRole = TenantRole::create(['tenant_id' => $tenant->id, 'name' => 'Waiter', 'slug' => 'waiter']);
        $waiter = User::factory()->forTenant($tenant)->create(['email' => 'staff@roles.test']);
        $waiter->roles()->attach($waiterRole->id);

        $this->actingAs($waiter)->get(route('tenant.dashboard'))
            ->assertOk()
            ->assertSee('Staff Workspace')
            ->assertSee('Your work area')
            ->assertDontSee('Executive Overview')
            ->assertDontSee('Business Entity &amp; Subscription Plan', false)
            ->assertDontSee('Gross Sales');
    }

    public function test_template_roles_follow_industry_and_do_not_create_staff_accounts(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        $tenant = Tenant::factory()->create(['industry' => 'restaurant']);

        $service = app(TenantRoleTemplateService::class);
        $service->createTemplateRoles($tenant);
        $service->assignTemplatePermissions($tenant);

        $slugs = TenantRole::where('tenant_id', $tenant->id)->pluck('slug')->all();
        $this->assertContains('owner', $slugs);
        $this->assertContains('waiter', $slugs);
        $this->assertContains('chef-kitchen', $slugs);
        $this->assertContains('restaurant-supervisor', $slugs);
        $this->assertNotContains('stock-controller', $slugs);
        $this->assertSame(0, User::where('tenant_id', $tenant->id)->count());

        $waiter = TenantRole::where('tenant_id', $tenant->id)->where('slug', 'waiter')->firstOrFail();
        $this->assertFalse($waiter->hasPermission('inventory.adjust'));
        $this->assertFalse($waiter->hasPermission('accounting.view'));
    }
}
