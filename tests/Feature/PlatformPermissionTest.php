<?php

namespace Tests\Feature;

use App\Models\PlatformRole;
use App\Models\PlatformUser;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlatformPermissionTest extends TestCase
{
    use RefreshDatabase;

    public function test_platform_roles_require_an_explicit_permission_grant(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $user = PlatformUser::factory()->create();
        $this->assertFalse($user->hasPermission('platform.tenants.delete'));

        $superAdminRole = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $user->roles()->attach($superAdminRole->id);

        $this->assertTrue($user->fresh()->hasPermission('platform.dashboard.view'));
        $this->assertFalse($user->fresh()->hasPermission('platform.tenants.delete'));
    }

    public function test_disabled_platform_user_has_no_permissions_even_with_an_assigned_role(): void
    {
        $this->seed(PlatformRoleSeeder::class);
        $user = PlatformUser::factory()->create(['status' => 'disabled']);
        $roleId = PlatformRole::query()->where('slug', 'super-administrator')->value('id');
        $user->roles()->attach($roleId);

        $this->assertFalse($user->fresh()->hasPermission('platform.dashboard.view'));
    }
}
