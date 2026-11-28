<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIndustryRoleEnforcementTest extends TestCase
{
    use RefreshDatabase;

    private function staffForIndustry(string $industry, array $permissions, string $roleSlug): User
    {
        $tenant = Tenant::factory()->create([
            'status' => 'active',
            'industry' => $industry,
            'owner_email' => 'owner-'.$roleSlug.'@test.local',
        ]);
        $role = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => ucfirst(str_replace('-', ' ', $roleSlug)),
            'slug' => $roleSlug,
        ]);

        foreach ($permissions as $permissionName) {
            $permission = TenantPermission::firstOrCreate(
                ['name' => $permissionName],
                ['group' => 'test', 'description' => $permissionName],
            );
            $role->permissions()->attach($permission->id);
        }

        $user = User::factory()->forTenant($tenant)->create();
        $user->roles()->attach($role->id);

        return $user;
    }

    public function test_gym_trainer_can_use_classes_but_cannot_manage_members_or_stock(): void
    {
        $trainer = $this->staffForIndustry('gym-fitness', ['gym.view', 'gym.class.manage'], 'trainer');

        $this->actingAs($trainer)->get(route('tenant.gym.index'))->assertOk();
        $this->actingAs($trainer)->get(route('tenant.gym.classes'))->assertOk();
        $this->actingAs($trainer)->get(route('tenant.gym.members'))->assertForbidden();
        $this->actingAs($trainer)->post(route('tenant.gym.members.store'), [])->assertForbidden();
        $this->actingAs($trainer)->get(route('tenant.inventory.index'))->assertForbidden();
    }

    public function test_fleet_inspector_does_not_get_booking_or_payment_access(): void
    {
        $inspector = $this->staffForIndustry('car-hire-fleet', ['fleet.view', 'fleet.inspection.manage'], 'inspector');

        $this->actingAs($inspector)->get(route('tenant.fleet.index'))->assertOk();
        $this->actingAs($inspector)->get(route('tenant.fleet.bookings'))->assertForbidden();
        $this->actingAs($inspector)->get(route('tenant.fleet.vehicles'))->assertForbidden();
    }

    public function test_industry_route_requires_the_business_capability_even_if_user_has_permission(): void
    {
        $retailStaff = $this->staffForIndustry('retail', ['gym.view', 'gym.class.manage'], 'trainer');

        $this->actingAs($retailStaff)->get(route('tenant.gym.index'))->assertForbidden();
    }

    public function test_direct_product_lookup_requires_a_catalog_or_pos_capability(): void
    {
        $staff = $this->staffForIndustry('retail', [], 'waiter');

        $this->actingAs($staff)->get(route('tenant.products.lookup', ['q' => 'water']))->assertForbidden();
    }
}
