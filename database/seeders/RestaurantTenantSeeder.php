<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\InventoryStock;
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
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RestaurantTenantSeeder extends Seeder
{
    /**
     * Run the database seeds for a modern Restaurant business on a trial package.
     */
    public function run(): void
    {
        // 1. Ensure Trial Plan exists
        $trialPlan = Plan::firstOrCreate(
            ['code' => 'trial'],
            [
                'name' => 'Free Trial (14 Days)',
                'description' => '14-day fully featured trial package for restaurant, hospitality and retail.',
                'currency' => 'KES',
                'monthly_price' => 0,
                'annual_price' => 0,
                'trial_days' => 14,
                'max_shops' => 3,
                'max_users' => 15,
                'max_registers' => 5,
                'max_warehouses' => 2,
                'is_active' => true,
                'features' => ['pos', 'inventory', 'accounting', 'reports', 'multi-branch', 'staff-management'],
            ]
        );

        foreach ([
            ['code' => 'starter', 'name' => 'Starter Plan', 'monthly_price' => 2500, 'annual_price' => 25000, 'max_shops' => 1, 'max_users' => 5, 'max_registers' => 2, 'max_warehouses' => 1],
            ['code' => 'growth', 'name' => 'Growth Plan', 'monthly_price' => 6500, 'annual_price' => 65000, 'max_shops' => 5, 'max_users' => 25, 'max_registers' => 10, 'max_warehouses' => 3],
            ['code' => 'enterprise', 'name' => 'Enterprise Plan', 'monthly_price' => 15000, 'annual_price' => 150000, 'max_shops' => 20, 'max_users' => 100, 'max_registers' => 40, 'max_warehouses' => 10],
        ] as $paidPlan) {
            Plan::firstOrCreate(
                ['code' => $paidPlan['code']],
                [
                    'name' => $paidPlan['name'],
                    'description' => "{$paidPlan['name']} for growing hospitality and retail businesses.",
                    'currency' => 'KES',
                    'monthly_price' => $paidPlan['monthly_price'],
                    'annual_price' => $paidPlan['annual_price'],
                    'trial_days' => 0,
                    'max_shops' => $paidPlan['max_shops'],
                    'max_users' => $paidPlan['max_users'],
                    'max_registers' => $paidPlan['max_registers'],
                    'max_warehouses' => $paidPlan['max_warehouses'],
                    'is_active' => true,
                    'features' => ['pos', 'inventory', 'accounting', 'reports', 'multi-branch', 'staff-management'],
                ]
            );
        }

        // 2. Ensure all granular Tenant Permissions exist
        $permissionDefs = [
            // Point of Sale & Cashiering
            'pos.sale.create' => ['sales', 'Create POS sales and process checkout'],
            'pos.sales.view' => ['sales', 'View sales history and receipts'],
            'pos.sale.void' => ['sales', 'Void existing sales transactions'],
            'shifts.view' => ['shifts', 'View cashier shift lists and records'],
            'shifts.manage' => ['shifts', 'Open, close, and manage cash movements in shifts'],

            // Catalog
            'catalog.products.manage' => ['catalog', 'Create, update, and manage products'],
            'catalog.categories.manage' => ['catalog', 'Create, update, and manage categories'],

            // Inventory
            'inventory.view' => ['inventory', 'View inventory stock levels and movements'],
            'inventory.adjust' => ['inventory', 'Adjust inventory quantities and write-offs'],
            'inventory.receive' => ['inventory', 'Receive incoming purchase orders and supplier deliveries'],

            // Accounting & Ledgers
            'accounting.view' => ['accounting', 'View chart of accounts, journals, expenses, and financial reports'],

            // Administration & Staff Management
            'users.manage' => ['administration', 'Manage tenant staff members, roles, and branch assignments'],
            'shops.all.manage' => ['administration', 'Manage and switch between all restaurant branches'],
        ];

        $permissions = [];
        foreach ($permissionDefs as $permName => [$group, $desc]) {
            $permissions[$permName] = TenantPermission::firstOrCreate(
                ['name' => $permName],
                ['group' => $group, 'description' => $desc]
            );
        }

        // 3. Create Restaurant Tenant
        $restaurantCode = '482915';
        if (Tenant::where('code', $restaurantCode)->exists()) {
            $restaurantCode = Tenant::generateUniqueCode();
        }

        $tenant = Tenant::updateOrCreate(
            ['slug' => 'savanna-bistro-grill'],
            [
                'name' => 'Savanna Bistro & Grill',
                'trading_name' => 'Savanna Bistro',
                'code' => $restaurantCode,
                'slug' => 'savanna-bistro-grill',
                'kra_pin' => 'P051839201L',
                'registration_number' => 'BN-2026-R4920',
                'business_type' => 'restaurant',
                'industry' => 'restaurant',
                'status' => 'trial',
                'owner_name' => 'Chef Daniel Omwamba',
                'owner_email' => 'restaurant.owner@blepos.co.ke',
                'owner_phone' => '+254700112233',
                'country' => 'KEN',
                'county' => 'Nairobi',
                'city' => 'Nairobi',
                'address' => 'Lenana Road, Kilimani, Nairobi',
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'settings' => [
                    'theme' => 'light',
                    'tax_rate' => 16.0,
                    'service_charge_rate' => 2.5,
                    'catering_levy_rate' => 2.0,
                ],
            ]
        );

        // Domain mapping
        TenantDomain::updateOrCreate(
            ['domain' => 'restaurant.blepos.local'],
            [
                'tenant_id' => $tenant->id,
                'is_primary' => true,
                'is_verified' => true,
            ]
        );

        // Subscription on Trial Plan
        Subscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'plan_id' => $trialPlan->id,
                'status' => 'trialing',
                'billing_cycle' => 'monthly',
                'starts_at' => now(),
                'trial_ends_at' => now()->addDays(14),
                'renews_at' => now()->addDays(14),
                'grace_ends_at' => null,
                'cancelled_at' => null,
            ]
        );

        // 4. Create Restaurant Branch & Registers
        $mainBranch = Shop::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'SB-MAIN'],
            [
                'name' => 'Savanna Bistro - Kilimani Main',
                'branch_number' => '001',
                'kra_branch_id' => 'B001',
                'phone' => '+254700112233',
                'email' => 'kilimani@savannabistro.co.ke',
                'address' => 'Lenana Road, Kilimani, Nairobi',
                'county' => 'Nairobi',
                'city' => 'Nairobi',
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'receipt_header' => "SAVANNA BISTRO & GRILL\nKilimani, Nairobi\nPIN: P051839201L",
                'receipt_footer' => "Thank you for dining with us!\nVisit again soon.",
                'is_active' => true,
            ]
        );

        $posTill1 = Register::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shop_id' => $mainBranch->id, 'code' => 'TILL-01'],
            ['name' => 'Bar & Dining Till 1', 'is_active' => true]
        );

        $posTill2 = Register::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shop_id' => $mainBranch->id, 'code' => 'TILL-02'],
            ['name' => 'Terrace & Takeaway Till 2', 'is_active' => true]
        );

        // 5. Create Canonical Tenant Roles
        // Owner Role: Full unrestricted operational, financial, and team access
        $ownerRole = TenantRole::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'owner'],
            ['name' => 'Restaurant Owner', 'description' => 'Unrestricted access to every module and feature', 'is_system' => true]
        );
        $ownerRole->permissions()->sync(array_values(array_map(fn ($p) => $p->id, $permissions)));

        // Cashier Role: POS Terminal, Shift opening/closing, and receipt lookup only
        $cashierRole = TenantRole::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'cashier'],
            ['name' => 'Cashier / Waitstaff', 'description' => 'POS Terminal, checkout, shifts, and receipts only', 'is_system' => true]
        );
        $cashierRole->permissions()->sync([
            $permissions['pos.sale.create']->id,
            $permissions['pos.sales.view']->id,
            $permissions['shifts.view']->id,
            $permissions['shifts.manage']->id,
        ]);

        // Inventory Officer Role: Stock levels, stock adjustments, stock receiving, movements only
        $inventoryRole = TenantRole::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'inventory-officer'],
            ['name' => 'Inventory Officer', 'description' => 'Stock counts, receiving purchase orders, inventory audit', 'is_system' => true]
        );
        $inventoryRole->permissions()->sync([
            $permissions['inventory.view']->id,
            $permissions['inventory.adjust']->id,
            $permissions['inventory.receive']->id,
        ]);

        // Accountant Role: Chart of accounts, journals, expenses, trial balance, P&L, balance sheet
        $accountantRole = TenantRole::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'accountant'],
            ['name' => 'Accountant', 'description' => 'Financial ledgers, expenses, journals, and reports', 'is_system' => true]
        );
        $accountantRole->permissions()->sync([
            $permissions['accounting.view']->id,
            $permissions['pos.sales.view']->id,
        ]);

        // 6. Create Staff Accounts with clear role boundaries
        // 1. Owner (Chef Daniel)
        $ownerUser = User::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'restaurant.owner@blepos.co.ke'],
            [
                'name' => 'Chef Daniel Omwamba (Owner)',
                'password' => Hash::make('Owner@123456!'),
                'status' => 'active',
                'phone' => '+254700112233',
                'must_reset_password' => false,
                'email_verified_at' => now(),
            ]
        );
        $ownerUser->roles()->sync([$ownerRole->id]);
        $ownerUser->shops()->syncWithPivotValues([$mainBranch->id], ['is_primary' => true]);

        // 2. Cashier (Brian - cannot access inventory, finance, or staff)
        $cashierUser = User::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'restaurant.cashier@blepos.co.ke'],
            [
                'name' => 'Brian Otieno (Cashier)',
                'password' => Hash::make('Cashier@123456!'),
                'status' => 'active',
                'phone' => '+254711334455',
                'must_reset_password' => false,
                'email_verified_at' => now(),
            ]
        );
        $cashierUser->roles()->sync([$cashierRole->id]);
        $cashierUser->shops()->syncWithPivotValues([$mainBranch->id], ['is_primary' => true]);

        // 3. Inventory Officer (Grace - cannot access POS, finance, or staff)
        $inventoryUser = User::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'restaurant.inventory@blepos.co.ke'],
            [
                'name' => 'Grace Wanjiku (Inventory Officer)',
                'password' => Hash::make('Inventory@123456!'),
                'status' => 'active',
                'phone' => '+254722556677',
                'must_reset_password' => false,
                'email_verified_at' => now(),
            ]
        );
        $inventoryUser->roles()->sync([$inventoryRole->id]);
        $inventoryUser->shops()->syncWithPivotValues([$mainBranch->id], ['is_primary' => true]);

        // 4. Accountant (Patrick - financial ledgers & reports)
        $accountantUser = User::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'restaurant.accountant@blepos.co.ke'],
            [
                'name' => 'Patrick Mutua (Accountant)',
                'password' => Hash::make('Accountant@123456!'),
                'status' => 'active',
                'phone' => '+254733778899',
                'must_reset_password' => false,
                'email_verified_at' => now(),
            ]
        );
        $accountantUser->roles()->sync([$accountantRole->id]);
        $accountantUser->shops()->syncWithPivotValues([$mainBranch->id], ['is_primary' => true]);

        // 7. Seed Restaurant Menu Categories
        $categoriesData = [
            'Starters & Appetizers' => 'Soups, salads, samosas, and finger foods',
            'Flame Grill & Steaks' => 'Nyama choma, aged ribeye, T-bone, BBQ ribs',
            'Seafood & Fish' => 'Grilled tilapia, butter garlic prawns, calamari',
            'Sides & Accompaniments' => 'Ugali, mukimo, french fries, sautéed greens',
            'Desserts' => 'Chocolate fondant, fruit platters, artisanal gelato',
            'Beverages & Mocktails' => 'Fresh fruit juices, craft mocktails, sodas, and coffee',
        ];

        $categories = [];
        foreach ($categoriesData as $catName => $desc) {
            $categories[$catName] = Category::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $catName],
                [
                    'slug' => Str::slug($catName),
                    'description' => $desc,
                    'is_active' => true,
                ]
            );
        }

        // 8. Seed Restaurant Menu Products & Initial Stocks
        $productsData = [
            [
                'category' => 'Starters & Appetizers',
                'name' => 'Savanna Crispy Beef Samosas (Pair)',
                'sku' => 'RES-APP-SAM',
                'barcode' => '60010001',
                'unit_of_measure' => 'plate',
                'cost_price' => 120.00,
                'selling_price' => 350.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 15,
                'stock' => 100,
            ],
            [
                'category' => 'Starters & Appetizers',
                'name' => 'Cream of Wild Forest Mushroom Soup',
                'sku' => 'RES-APP-SUP',
                'barcode' => '60010002',
                'unit_of_measure' => 'bowl',
                'cost_price' => 180.00,
                'selling_price' => 550.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 10,
                'stock' => 60,
            ],
            [
                'category' => 'Flame Grill & Steaks',
                'name' => 'Grilled Ribeye Steak (350g) with Pepper Sauce',
                'sku' => 'RES-GRL-RIB',
                'barcode' => '60020001',
                'unit_of_measure' => 'portion',
                'cost_price' => 850.00,
                'selling_price' => 2400.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 8,
                'stock' => 45,
            ],
            [
                'category' => 'Flame Grill & Steaks',
                'name' => 'Savanna Special Mbuzi Choma (1kg Platter)',
                'sku' => 'RES-GRL-MBZ',
                'barcode' => '60020002',
                'unit_of_measure' => 'kg',
                'cost_price' => 700.00,
                'selling_price' => 1950.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 5,
                'stock' => 30,
            ],
            [
                'category' => 'Seafood & Fish',
                'name' => 'Whole Deep Fried Tilapia with Kachumbari',
                'sku' => 'RES-SEA-TLP',
                'barcode' => '60030001',
                'unit_of_measure' => 'piece',
                'cost_price' => 450.00,
                'selling_price' => 1350.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 6,
                'stock' => 50,
            ],
            [
                'category' => 'Sides & Accompaniments',
                'name' => 'Crispy Potato French Fries',
                'sku' => 'RES-SID-CHIP',
                'barcode' => '60040001',
                'unit_of_measure' => 'portion',
                'cost_price' => 70.00,
                'selling_price' => 250.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 20,
                'stock' => 180,
            ],
            [
                'category' => 'Sides & Accompaniments',
                'name' => 'Traditional Brown Ugali & Sukuma Wiki',
                'sku' => 'RES-SID-UGL',
                'barcode' => '60040002',
                'unit_of_measure' => 'portion',
                'cost_price' => 50.00,
                'selling_price' => 200.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 15,
                'stock' => 120,
            ],
            [
                'category' => 'Beverages & Mocktails',
                'name' => 'Fresh Mint Pineapple Juice (500ml)',
                'sku' => 'RES-BEV-PIN',
                'barcode' => '60050001',
                'unit_of_measure' => 'glass',
                'cost_price' => 65.00,
                'selling_price' => 320.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 15,
                'stock' => 140,
            ],
            [
                'category' => 'Beverages & Mocktails',
                'name' => 'Highland Arabica Iced Caramel Macchiato',
                'sku' => 'RES-BEV-MAC',
                'barcode' => '60050002',
                'unit_of_measure' => 'cup',
                'cost_price' => 85.00,
                'selling_price' => 420.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 20,
                'stock' => 110,
            ],
            [
                'category' => 'Desserts',
                'name' => 'Warm Chocolate Lava Cake with Vanilla Gelato',
                'sku' => 'RES-DES-LAV',
                'barcode' => '60060001',
                'unit_of_measure' => 'portion',
                'cost_price' => 160.00,
                'selling_price' => 650.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 8,
                'stock' => 35,
            ],
        ];

        foreach ($productsData as $prod) {
            $cat = $categories[$prod['category']];
            $product = Product::updateOrCreate(
                ['tenant_id' => $tenant->id, 'sku' => $prod['sku']],
                [
                    'category_id' => $cat->id,
                    'name' => $prod['name'],
                    'barcode' => $prod['barcode'],
                    'unit_of_measure' => $prod['unit_of_measure'],
                    'cost_price' => $prod['cost_price'],
                    'selling_price' => $prod['selling_price'],
                    'tax_rate' => $prod['tax_rate'],
                    'tax_type' => $prod['tax_type'],
                    'track_stock' => $prod['track_stock'],
                    'reorder_level' => $prod['reorder_level'],
                    'is_active' => true,
                ]
            );

            InventoryStock::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'shop_id' => $mainBranch->id,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => $prod['stock'],
                    'reserved_quantity' => 0,
                ]
            );
        }

        // 10. Seed Dining Tables
        $tablesData = [
            // Main Dining Hall
            ['name' => 'Table T-01', 'code' => 'T-01', 'zone' => 'Main Dining', 'seating_capacity' => 4],
            ['name' => 'Table T-02', 'code' => 'T-02', 'zone' => 'Main Dining', 'seating_capacity' => 4],
            ['name' => 'Table T-03', 'code' => 'T-03', 'zone' => 'Main Dining', 'seating_capacity' => 4],
            ['name' => 'Table T-04', 'code' => 'T-04', 'zone' => 'Main Dining', 'seating_capacity' => 4],
            ['name' => 'Table T-05', 'code' => 'T-05', 'zone' => 'Main Dining', 'seating_capacity' => 6],
            ['name' => 'Table T-06', 'code' => 'T-06', 'zone' => 'Main Dining', 'seating_capacity' => 6],
            ['name' => 'Table T-07', 'code' => 'T-07', 'zone' => 'Main Dining', 'seating_capacity' => 4],
            ['name' => 'Table T-08', 'code' => 'T-08', 'zone' => 'Main Dining', 'seating_capacity' => 4],
            // Terrace & Garden
            ['name' => 'Terrace T-09', 'code' => 'T-09', 'zone' => 'Terrace & Garden', 'seating_capacity' => 4],
            ['name' => 'Terrace T-10', 'code' => 'T-10', 'zone' => 'Terrace & Garden', 'seating_capacity' => 4],
            ['name' => 'Garden T-11', 'code' => 'T-11', 'zone' => 'Terrace & Garden', 'seating_capacity' => 6],
            ['name' => 'Garden T-12', 'code' => 'T-12', 'zone' => 'Terrace & Garden', 'seating_capacity' => 6],
            // VIP Lounge
            ['name' => 'VIP Suite 01', 'code' => 'VIP-01', 'zone' => 'VIP Lounge', 'seating_capacity' => 8],
            ['name' => 'VIP Suite 02', 'code' => 'VIP-02', 'zone' => 'VIP Lounge', 'seating_capacity' => 8],
            // Bar Counter
            ['name' => 'Bar Stool 1', 'code' => 'BAR-01', 'zone' => 'Bar Counter', 'seating_capacity' => 2],
            ['name' => 'Bar Stool 2', 'code' => 'BAR-02', 'zone' => 'Bar Counter', 'seating_capacity' => 2],
            ['name' => 'Bar Stool 3', 'code' => 'BAR-03', 'zone' => 'Bar Counter', 'seating_capacity' => 2],
            ['name' => 'Bar Stool 4', 'code' => 'BAR-04', 'zone' => 'Bar Counter', 'seating_capacity' => 2],
        ];

        foreach ($tablesData as $tableData) {
            DiningTable::updateOrCreate(
                ['tenant_id' => $tenant->id, 'shop_id' => $mainBranch->id, 'code' => $tableData['code']],
                [
                    'name' => $tableData['name'],
                    'zone' => $tableData['zone'],
                    'seating_capacity' => $tableData['seating_capacity'],
                    'status' => 'available',
                    'is_active' => true,
                ]
            );
        }
    }
}
