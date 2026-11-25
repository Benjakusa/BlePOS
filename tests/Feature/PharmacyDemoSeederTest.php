<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PharmacyDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PharmacyDemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_pharmacy_demo_seeds_three_stores_products_and_individual_store_users(): void
    {
        $this->seed(PharmacyDemoSeeder::class);
        $tenant = Tenant::where('slug', 'afyacare-pharmacy-demo')->firstOrFail();

        $this->assertSame('pharmacy', $tenant->industry);
        $this->assertSame(3, $tenant->shops()->count());
        $this->assertSame(7, $tenant->users()->count());
        $this->assertSame(6, Product::where('tenant_id', $tenant->id)->count());
        $this->assertSame(9, $tenant->shops()->withCount('users')->get()->sum('users_count'));
        $this->assertDatabaseHas('tenant_module_entitlements', [
            'tenant_id' => $tenant->id,
            'status' => 'enabled',
        ]);
    }

    public function test_pharmacy_owner_and_each_store_user_can_sign_in(): void
    {
        $this->seed(PharmacyDemoSeeder::class);
        $credentials = [
            ['email' => 'owner@afyacare.test', 'password' => 'AfyaOwner@2026!'],
            ['email' => 'pharmacist.cbd@afyacare.test', 'password' => 'AfyaCBD@2026!'],
            ['email' => 'cashier.cbd@afyacare.test', 'password' => 'AfyaCBDcash@2026!'],
            ['email' => 'pharmacist.westlands@afyacare.test', 'password' => 'AfyaWest@2026!'],
            ['email' => 'cashier.westlands@afyacare.test', 'password' => 'AfyaWestcash@2026!'],
            ['email' => 'pharmacist.thika@afyacare.test', 'password' => 'AfyaThika@2026!'],
            ['email' => 'cashier.thika@afyacare.test', 'password' => 'AfyaThikacash@2026!'],
        ];

        foreach ($credentials as $credential) {
            $response = $this->post(route('tenant.login.store'), $credential);
            $response->assertRedirect(route('tenant.dashboard'));
            $this->assertAuthenticatedAs(User::where('email', $credential['email'])->firstOrFail(), 'web');
            auth('web')->logout();
        }
    }
}
