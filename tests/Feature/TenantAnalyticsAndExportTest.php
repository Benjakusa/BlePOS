<?php

namespace Tests\Feature;

use App\Models\CashierShift;
use App\Models\Register;
use App\Models\Sale;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantAnalyticsAndExportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();
        ShopContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        ShopContext::clear();
        parent::tearDown();
    }

    private function createTenantAndUser(string $industry = 'retail'): array
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Apex Retail Ltd',
            'industry' => $industry,
            'currency' => 'KES',
        ]);
        TenantContext::setTenant($tenant);

        $shop = Shop::factory()->create(['tenant_id' => $tenant->id, 'name' => 'CBD Branch']);
        ShopContext::setShop($shop);

        $role = TenantRole::create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager',
            'slug' => 'manager',
        ]);

        $permissions = [
            'pos.sale.create',
            'pos.sales.view',
            'shifts.view',
            'shifts.manage',
            'inventory.view',
            'catalog.products.manage',
            'accounting.view',
            'reports.sales.view',
        ];

        foreach ($permissions as $p) {
            $perm = TenantPermission::firstOrCreate(['name' => $p], ['group' => 'general', 'description' => $p]);
            $role->permissions()->attach($perm->id);
        }

        $industryViewPermissions = [
            'parcel-delivery' => 'parcel.view',
            'gym-fitness' => 'gym.view',
            'car-hire-fleet' => 'fleet.view',
            'auto-garage' => 'garage.view',
        ];
        if (isset($industryViewPermissions[$industry])) {
            $permission = TenantPermission::firstOrCreate(
                ['name' => $industryViewPermissions[$industry]],
                ['group' => 'industry', 'description' => 'View industry operations.'],
            );
            $role->permissions()->syncWithoutDetaching([$permission->id]);
        }

        $user = User::factory()->forTenant($tenant)->create(['name' => 'Jane Manager']);
        $user->roles()->attach($role->id);
        $user->shops()->attach($shop->id, ['is_primary' => true]);

        return [$tenant, $shop, $user];
    }

    public function test_tenant_analytics_dashboard_renders_with_metrics(): void
    {
        [$tenant, $shop, $user] = $this->createTenantAndUser();

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.analytics.index'));

        $response->assertOk();
        $response->assertSee('Business Analytics &amp; Reports', false);
        $response->assertSee('Gross Sales Total');
        $response->assertSee('16% VAT Collected');
        $response->assertSee('Payment Tender Distribution');
    }

    public function test_staff_without_reporting_permission_cannot_read_analytics_or_export(): void
    {
        [$tenant] = $this->createTenantAndUser();
        $staff = User::factory()->forTenant($tenant)->create(['name' => 'Unprivileged Staff']);

        $this->actingAs($staff)->get(route('tenant.analytics.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('tenant.analytics.export-center'))->assertForbidden();
        $this->actingAs($staff)->post(route('tenant.analytics.export'), ['export_type' => 'sales'])->assertForbidden();
    }

    public function test_report_user_cannot_query_or_export_an_unassigned_branch(): void
    {
        [$tenant, , $user] = $this->createTenantAndUser();
        $otherShop = Shop::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Unassigned Branch']);

        $this->actingAs($user)->get(route('tenant.analytics.index', ['shop_id' => $otherShop->id]))
            ->assertForbidden();
        $this->actingAs($user)->post(route('tenant.analytics.export'), [
            'export_type' => 'sales',
            'shop_id' => $otherShop->id,
        ])->assertForbidden();
    }

    public function test_tenant_analytics_period_filtering(): void
    {
        [$tenant, $shop, $user] = $this->createTenantAndUser();

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.analytics.index', ['period' => '7d']));

        $response->assertOk();
        $response->assertSee('Daily Sales Breakdown (7d)');
    }

    public function test_tenant_export_center_view_renders(): void
    {
        [$tenant, $shop, $user] = $this->createTenantAndUser();

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.analytics.export-center'));

        $response->assertOk();
        $response->assertSee('Data Export Center');
        $response->assertSee('Sales &amp; Orders', false);
        $response->assertSee('Cashier Shifts &amp; Z-Reports', false);
        $response->assertSee('Formula-Injection Safe');
    }

    public function test_tenant_can_export_sales_csv_with_formula_sanitization(): void
    {
        [$tenant, $shop, $user] = $this->createTenantAndUser();

        $register = Register::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'name' => 'Register 1',
            'code' => 'REG-1',
        ]);

        $shift = CashierShift::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'user_id' => $user->id,
            'shift_number' => 'SH-001',
            'status' => 'open',
            'opening_float' => 1000,
            'cash_sales' => 0,
            'mpesa_sales' => 0,
            'expected_cash' => 1000,
            'opened_at' => now(),
        ]);

        // Sale with formula injection payload in sale number
        Sale::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'cashier_shift_id' => $shift->id,
            'user_id' => $user->id,
            'sale_number' => '=1+1-HYPERLINK("calc")',
            'status' => 'completed',
            'subtotal' => 1000,
            'tax_total' => 160,
            'discount_total' => 0,
            'grand_total' => 1160,
            'paid_total' => 1160,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->post(route('tenant.analytics.export'), [
                'export_type' => 'sales',
            ]);

        $response->assertOk();
        $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        $this->assertStringContainsString('blepos-sales-', (string) $response->headers->get('Content-Disposition'));

        $content = $response->getContent();
        // Check formula character sanitized by prepending single quote
        $this->assertStringContainsString("'=1+1-HYPERLINK", $content);
    }

    public function test_tenant_can_export_shifts_products_inventory_csvs(): void
    {
        [$tenant, $shop, $user] = $this->createTenantAndUser();

        foreach (['shifts', 'products', 'inventory'] as $type) {
            $response = $this->actingAs($user)
                ->withSession(['tenant_id' => $tenant->id])
                ->post(route('tenant.analytics.export'), [
                    'export_type' => $type,
                ]);

            $response->assertOk();
            $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));
        }
    }

    public function test_tenant_can_export_industry_datasets_csvs(): void
    {
        // 1. Retail tenant should be forbidden from exporting specialized industry datasets
        [$retailTenant, , $retailUser] = $this->createTenantAndUser('retail');

        foreach (['parcels', 'gym', 'fleet', 'garage'] as $type) {
            $this->actingAs($retailUser)
                ->withSession(['tenant_id' => $retailTenant->id])
                ->post(route('tenant.analytics.export'), [
                    'export_type' => $type,
                ])
                ->assertForbidden();
        }

        // 2. Each industry tenant can export its own dataset and is forbidden from others
        $industryMap = [
            'parcel-delivery' => 'parcels',
            'gym-fitness' => 'gym',
            'car-hire-fleet' => 'fleet',
            'auto-garage' => 'garage',
        ];

        foreach ($industryMap as $industry => $allowedType) {
            [$tenant, , $user] = $this->createTenantAndUser($industry);

            // Allowed export succeeds
            $response = $this->actingAs($user)
                ->withSession(['tenant_id' => $tenant->id])
                ->post(route('tenant.analytics.export'), [
                    'export_type' => $allowedType,
                ]);

            $response->assertOk();
            $this->assertStringContainsString('text/csv', (string) $response->headers->get('Content-Type'));

            // Other industry exports are forbidden
            foreach ($industryMap as $otherInd => $forbiddenType) {
                if ($forbiddenType !== $allowedType) {
                    $this->actingAs($user)
                        ->withSession(['tenant_id' => $tenant->id])
                        ->post(route('tenant.analytics.export'), [
                            'export_type' => $forbiddenType,
                        ])
                        ->assertForbidden();
                }
            }
        }
    }

    public function test_cashier_shift_z_report_renders_printable_settlement(): void
    {
        [$tenant, $shop, $user] = $this->createTenantAndUser();

        $register = Register::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'name' => 'Main POS',
            'code' => 'POS-01',
        ]);

        $shift = CashierShift::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'user_id' => $user->id,
            'shift_number' => 'SH-999',
            'status' => 'open',
            'opening_float' => 5000,
            'cash_sales' => 12500,
            'mpesa_sales' => 8400,
            'card_sales' => 0,
            'expected_cash' => 17500,
            'opened_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.shifts.z-report', $shift));

        $response->assertOk();
        $response->assertSee('CASHIER SHIFT Z-REPORT');
        $response->assertSee('Expected Drawer Cash:');
        $response->assertSee('KES 17,500.00');
        $response->assertSee('Sales Tender Summary');
    }

    public function test_my_shift_route_redirects_to_active_shift(): void
    {
        [$tenant, $shop, $user] = $this->createTenantAndUser();

        $register = Register::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'name' => 'Counter 1',
            'code' => 'CTR-1',
        ]);

        $shift = CashierShift::create([
            'tenant_id' => $tenant->id,
            'shop_id' => $shop->id,
            'register_id' => $register->id,
            'user_id' => $user->id,
            'shift_number' => 'SH-ACTIVE',
            'status' => 'open',
            'opening_float' => 1000,
            'cash_sales' => 0,
            'mpesa_sales' => 0,
            'expected_cash' => 1000,
            'opened_at' => now(),
        ]);

        $response = $this->actingAs($user)
            ->withSession(['tenant_id' => $tenant->id])
            ->get(route('tenant.shifts.my-shift'));

        $response->assertRedirect(route('tenant.shifts.show', $shift));
    }
}
