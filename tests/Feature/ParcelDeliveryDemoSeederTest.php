<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\ParcelDeliveryDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ParcelDeliveryDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_parcel_delivery_demo_seeds_four_offices_and_branch_assigned_staff_idempotently(): void
    {
        $this->seed(ParcelDeliveryDemoSeeder::class);

        $tenant = Tenant::withoutGlobalScopes()->where('slug', 'parcelway-courier')->firstOrFail();
        $this->assertSame('parcel-delivery', $tenant->industry);

        $shops = Shop::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get()->keyBy('code');
        $this->assertSame(['PW-ELD', 'PW-KSM', 'PW-MBA', 'PW-NBO'], $shops->keys()->sort()->values()->all());

        $users = User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->get()->keyBy('email');
        $this->assertCount(7, $users);
        $this->assertTrue(Hash::check('ParcelOwner@2026!', $users['owner@parcelway.test']->password));
        $this->assertTrue(Hash::check('ParcelCounter@2026!', $users['counter.mombasa@parcelway.test']->password));
        $this->assertDatabaseHas('shop_user', [
            'shop_id' => $shops['PW-MBA']->id,
            'user_id' => $users['counter.mombasa@parcelway.test']->id,
        ]);
        $this->assertDatabaseMissing('shop_user', [
            'shop_id' => $shops['PW-NBO']->id,
            'user_id' => $users['counter.mombasa@parcelway.test']->id,
        ]);
        $this->assertDatabaseHas('tenant_module_entitlements', [
            'tenant_id' => $tenant->id,
            'module_id' => Module::where('slug', 'parcel-delivery')->value('id'),
            'status' => 'enabled',
        ]);

        $this->post(route('tenant.login.store'), [
            'email' => 'counter.mombasa@parcelway.test',
            'password' => 'ParcelCounter@2026!',
        ])
            ->assertRedirect(route('tenant.dashboard'))
            ->assertSessionHas('active_shop_id', $shops['PW-MBA']->id);
        $this->assertAuthenticatedAs($users['counter.mombasa@parcelway.test']);

        $counterRoleId = DB::table('tenant_role_user')
            ->where('user_id', $users['counter.mombasa@parcelway.test']->id)
            ->value('tenant_role_id');
        $dispatcherRoleId = DB::table('tenant_role_user')
            ->where('user_id', $users['dispatcher@parcelway.test']->id)
            ->value('tenant_role_id');
        $receivePermissionId = DB::table('tenant_permissions')->where('name', 'parcel.receive')->value('id');
        $dispatchPermissionId = DB::table('tenant_permissions')->where('name', 'parcel.dispatch')->value('id');

        $this->assertDatabaseHas('tenant_permission_role', [
            'tenant_role_id' => $counterRoleId,
            'tenant_permission_id' => $receivePermissionId,
        ]);
        $this->assertDatabaseMissing('tenant_permission_role', [
            'tenant_role_id' => $counterRoleId,
            'tenant_permission_id' => $dispatchPermissionId,
        ]);
        $this->assertDatabaseHas('tenant_permission_role', [
            'tenant_role_id' => $dispatcherRoleId,
            'tenant_permission_id' => $dispatchPermissionId,
        ]);

        $this->seed(ParcelDeliveryDemoSeeder::class);

        $this->assertSame(4, Shop::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
        $this->assertSame(7, User::withoutGlobalScopes()->where('tenant_id', $tenant->id)->count());
    }
}
