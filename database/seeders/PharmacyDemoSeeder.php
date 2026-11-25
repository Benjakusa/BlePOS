<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\IndustryTemplate;
use App\Models\InventoryStock;
use App\Models\Module;
use App\Models\PharmacyBatch;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Register;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\ModuleService;
use App\Services\TenantContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PharmacyDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(ModuleCatalogueSeeder::class);

        $plan = Plan::firstOrCreate(
            ['code' => 'growth'],
            [
                'name' => 'Growth Plan',
                'description' => 'Multi-branch demo subscription.',
                'currency' => 'KES',
                'monthly_price' => 6500,
                'annual_price' => 65000,
                'trial_days' => 0,
                'max_shops' => 5,
                'max_users' => 25,
                'max_registers' => 10,
                'max_warehouses' => 3,
                'is_active' => true,
                'features' => ['pos', 'inventory', 'pharmacy', 'multi-branch', 'staff-management'],
            ]
        );

        $tenant = Tenant::updateOrCreate(
            ['slug' => 'afyacare-pharmacy-demo'],
            [
                'name' => 'AfyaCare Pharmacy Demo',
                'trading_name' => 'AfyaCare Pharmacy',
                'code' => $this->tenantCode(),
                'kra_pin' => 'P051234567A',
                'registration_number' => 'DEMO-PH-2026-001',
                'business_type' => 'pharmacy',
                'industry' => 'pharmacy',
                'status' => 'trial',
                'owner_name' => 'AfyaCare Demo Owner',
                'owner_email' => 'owner@afyacare.test',
                'owner_phone' => '+254700100001',
                'country' => 'KEN',
                'county' => 'Nairobi',
                'city' => 'Nairobi',
                'address' => 'Nairobi, Kenya',
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'settings' => ['theme' => 'light', 'tax_rate' => 0],
            ]
        );

        TenantDomain::updateOrCreate(
            ['domain' => 'afyacare.blepos.local'],
            ['tenant_id' => $tenant->id, 'is_primary' => true, 'is_verified' => true]
        );

        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $plan->id,
                'status' => 'trialing',
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'trial_ends_at' => now()->addDays(14),
                'renews_at' => now()->addDays(14),
            ]
        );

        TenantContext::setTenant($tenant);

        try {
            $shops = collect([
                ['code' => 'AF-CBD', 'branch' => '001', 'name' => 'AfyaCare Pharmacy - Nairobi CBD', 'address' => 'Kimathi Street, Nairobi', 'phone' => '+254700100011'],
                ['code' => 'AF-WEST', 'branch' => '002', 'name' => 'AfyaCare Pharmacy - Westlands', 'address' => 'Woodvale Grove, Westlands', 'phone' => '+254700100012'],
                ['code' => 'AF-THIKA', 'branch' => '003', 'name' => 'AfyaCare Pharmacy - Thika Road', 'address' => 'Garden City Mall, Thika Road', 'phone' => '+254700100013'],
            ])->mapWithKeys(function (array $store) use ($tenant): array {
                $shop = Shop::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'code' => $store['code']],
                    [
                        'name' => $store['name'],
                        'branch_number' => $store['branch'],
                        'kra_branch_id' => 'B'.$store['branch'],
                        'phone' => $store['phone'],
                        'email' => strtolower($store['code']).'@afyacare.test',
                        'address' => $store['address'],
                        'county' => 'Nairobi',
                        'city' => 'Nairobi',
                        'currency' => 'KES',
                        'timezone' => 'Africa/Nairobi',
                        'receipt_header' => "AFYACARE PHARMACY\n{$store['name']}\nDEMO DATA",
                        'receipt_footer' => 'Thank you. This is a demo pharmacy account.',
                        'is_active' => true,
                    ]
                );
                Register::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'shop_id' => $shop->id, 'code' => 'REG-01'],
                    ['name' => 'Main Counter', 'is_active' => true]
                );

                return [$store['code'] => $shop];
            });

            $permissionNames = [
                'pos.sale.create' => ['sales', 'Create POS sales and process checkout'],
                'pos.sales.view' => ['sales', 'View sales history and receipts'],
                'shifts.view' => ['shifts', 'View cashier shifts'],
                'shifts.manage' => ['shifts', 'Open and close cashier shifts'],
                'catalog.products.manage' => ['catalog', 'Manage pharmacy products'],
                'catalog.categories.manage' => ['catalog', 'Manage product categories'],
                'inventory.view' => ['inventory', 'View store inventory'],
                'inventory.adjust' => ['inventory', 'Adjust store inventory'],
                'inventory.receive' => ['inventory', 'Receive stock into a store'],
                'users.manage' => ['administration', 'Manage tenant staff'],
                'shops.all.manage' => ['administration', 'Manage all pharmacy stores'],
            ];
            $permissions = collect($permissionNames)->mapWithKeys(fn (array $definition, string $name) => [
                $name => TenantPermission::firstOrCreate(
                    ['name' => $name],
                    ['group' => $definition[0], 'description' => $definition[1]]
                ),
            ]);

            $ownerRole = TenantRole::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => 'owner'],
                ['name' => 'Pharmacy Owner', 'description' => 'Full access to the pharmacy tenant and all three stores.', 'is_system' => true]
            );
            $ownerRole->permissions()->sync($permissions->pluck('id')->all());

            $pharmacistRole = TenantRole::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => 'pharmacist'],
                ['name' => 'Dispensing Pharmacist', 'description' => 'POS, product catalog, stock management, and shift access.', 'is_system' => true]
            );
            $pharmacistRole->permissions()->sync($permissions->only([
                'pos.sale.create', 'pos.sales.view', 'shifts.view', 'shifts.manage',
                'catalog.products.manage', 'catalog.categories.manage', 'inventory.view', 'inventory.adjust', 'inventory.receive',
            ])->pluck('id')->all());

            $cashierRole = TenantRole::updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => 'cashier'],
                ['name' => 'Pharmacy Cashier', 'description' => 'POS checkout, receipts, and cashier shifts.', 'is_system' => true]
            );
            $cashierRole->permissions()->sync($permissions->only([
                'pos.sale.create', 'pos.sales.view', 'shifts.view', 'shifts.manage',
            ])->pluck('id')->all());

            $owner = $this->user($tenant, 'owner@afyacare.test', 'AfyaCare Demo Owner', 'AfyaOwner@2026!', '+254700100001');
            $owner->roles()->sync([$ownerRole->id]);
            $owner->shops()->syncWithPivotValues($shops->pluck('id')->all(), ['is_primary' => false]);
            if ($shops->isNotEmpty()) {
                $owner->shops()->updateExistingPivot($shops->first()->id, ['is_primary' => true]);
            }

            $staff = [
                ['store' => 'AF-CBD', 'role' => $pharmacistRole, 'email' => 'pharmacist.cbd@afyacare.test', 'name' => 'Wanjiku Kamau - CBD Pharmacist', 'password' => 'AfyaCBD@2026!', 'phone' => '+254700100021'],
                ['store' => 'AF-CBD', 'role' => $cashierRole, 'email' => 'cashier.cbd@afyacare.test', 'name' => 'Peter Otieno - CBD Cashier', 'password' => 'AfyaCBDcash@2026!', 'phone' => '+254700100022'],
                ['store' => 'AF-WEST', 'role' => $pharmacistRole, 'email' => 'pharmacist.westlands@afyacare.test', 'name' => 'Amina Hassan - Westlands Pharmacist', 'password' => 'AfyaWest@2026!', 'phone' => '+254700100023'],
                ['store' => 'AF-WEST', 'role' => $cashierRole, 'email' => 'cashier.westlands@afyacare.test', 'name' => 'Brian Mwangi - Westlands Cashier', 'password' => 'AfyaWestcash@2026!', 'phone' => '+254700100024'],
                ['store' => 'AF-THIKA', 'role' => $pharmacistRole, 'email' => 'pharmacist.thika@afyacare.test', 'name' => 'Grace Njeri - Thika Road Pharmacist', 'password' => 'AfyaThika@2026!', 'phone' => '+254700100025'],
                ['store' => 'AF-THIKA', 'role' => $cashierRole, 'email' => 'cashier.thika@afyacare.test', 'name' => 'David Kiptoo - Thika Road Cashier', 'password' => 'AfyaThikacash@2026!', 'phone' => '+254700100026'],
            ];
            foreach ($staff as $member) {
                $user = $this->user($tenant, $member['email'], $member['name'], $member['password'], $member['phone']);
                $user->roles()->sync([$member['role']->id]);
                $user->shops()->syncWithPivotValues([$shops[$member['store']]->id], ['is_primary' => true]);
            }

            $categories = [];
            foreach ([
                'OTC & Pain Relief' => 'Over-the-counter pain and fever relief products.',
                'Allergy & Respiratory' => 'Allergy and respiratory care products.',
                'Prescription Medicines' => 'Sample prescription-only products for demonstration.',
                'Diagnostics & Wellness' => 'Testing supplies and everyday wellness products.',
            ] as $name => $description) {
                $categories[$name] = Category::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'slug' => Str::slug($name)],
                    ['name' => $name, 'description' => $description, 'is_active' => true]
                );
            }

            $products = [
                ['sku' => 'AFY-OTC-001', 'name' => 'Paracetamol 500 mg (100 tablets)', 'category' => 'OTC & Pain Relief', 'barcode' => '6291000000011', 'cost' => 95, 'price' => 150, 'stock' => [72, 55, 46]],
                ['sku' => 'AFY-OTC-002', 'name' => 'Cetirizine 10 mg (10 tablets)', 'category' => 'Allergy & Respiratory', 'barcode' => '6291000000028', 'cost' => 60, 'price' => 100, 'stock' => [48, 36, 29]],
                ['sku' => 'AFY-OTC-003', 'name' => 'Oral Rehydration Salts (sachets)', 'category' => 'OTC & Pain Relief', 'barcode' => '6291000000035', 'cost' => 22, 'price' => 40, 'stock' => [100, 80, 64]],
                ['sku' => 'AFY-RX-001', 'name' => 'Amoxicillin 500 mg (21 capsules)', 'category' => 'Prescription Medicines', 'barcode' => '6291000000042', 'cost' => 210, 'price' => 320, 'stock' => [30, 24, 18]],
                ['sku' => 'AFY-DIAG-001', 'name' => 'Blood Glucose Test Strips (50)', 'category' => 'Diagnostics & Wellness', 'barcode' => '6291000000059', 'cost' => 760, 'price' => 1050, 'stock' => [20, 16, 12]],
                ['sku' => 'AFY-WELL-001', 'name' => 'Folic Acid 5 mg (100 tablets)', 'category' => 'Diagnostics & Wellness', 'barcode' => '6291000000066', 'cost' => 85, 'price' => 140, 'stock' => [40, 34, 26]],
            ];
            foreach ($products as $data) {
                $product = Product::updateOrCreate(
                    ['tenant_id' => $tenant->id, 'sku' => $data['sku']],
                    [
                        'category_id' => $categories[$data['category']]->id,
                        'name' => $data['name'],
                        'barcode' => $data['barcode'],
                        'unit_of_measure' => 'pack',
                        'cost_price' => $data['cost'],
                        'selling_price' => $data['price'],
                        'tax_rate' => 0,
                        'tax_type' => 'inclusive',
                        'track_stock' => true,
                        'reorder_level' => 10,
                        'is_active' => true,
                        'metadata' => ['demo_data' => true, 'requires_prescription' => $data['category'] === 'Prescription Medicines'],
                    ]
                );

                foreach ($shops->values() as $index => $shop) {
                    $quantity = $data['stock'][$index];
                    InventoryStock::updateOrCreate(
                        ['tenant_id' => $tenant->id, 'shop_id' => $shop->id, 'product_id' => $product->id],
                        ['quantity' => $quantity, 'reserved_quantity' => 0]
                    );
                    PharmacyBatch::updateOrCreate(
                        ['shop_id' => $shop->id, 'product_id' => $product->id, 'batch_number' => 'DEMO-'.str_replace('-', '', $data['sku']).'-'.$index],
                        [
                            'tenant_id' => $tenant->id,
                            'supplier_name' => 'AfyaCare Demo Supplier',
                            'manufacturing_date' => now()->subMonths(2)->toDateString(),
                            'expiry_date' => now()->addMonths(18)->toDateString(),
                            'initial_quantity' => $quantity,
                            'current_quantity' => $quantity,
                            'unit_cost' => $data['cost'],
                            'is_quarantined' => false,
                        ]
                    );
                }
            }

            $template = IndustryTemplate::where('slug', 'pharmacy')->first();
            if ($template) {
                app(ModuleService::class)->applyIndustryTemplate($tenant, $template);
            } else {
                $pharmacyModule = Module::where('slug', 'mod-pharmacy')->first();
                if ($pharmacyModule) {
                    app(ModuleService::class)->enableModule($tenant, $pharmacyModule, 'AfyaCare pharmacy demo seed');
                }
            }
        } finally {
            TenantContext::clear();
        }
    }

    private function user(Tenant $tenant, string $email, string $name, string $password, string $phone): User
    {
        return User::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'status' => 'active',
                'phone' => $phone,
                'must_reset_password' => false,
                'email_verified_at' => now(),
            ]
        );
    }

    private function tenantCode(): string
    {
        $existing = Tenant::where('slug', 'afyacare-pharmacy-demo')->first();
        if ($existing) {
            return $existing->code;
        }

        return Tenant::generateUniqueCode();
    }
}
