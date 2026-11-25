<?php

namespace Database\Seeders;

use App\Models\Category;
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

class HotelTenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Ensure a Trial Plan exists
        $trialPlan = Plan::firstOrCreate(
            ['code' => 'trial'],
            [
                'name' => 'Free Trial (14 Days)',
                'description' => '14-day fully featured trial package for hospitality and retail.',
                'currency' => 'KES',
                'monthly_price' => 0,
                'annual_price' => 0,
                'trial_days' => 14,
                'max_shops' => 3,
                'max_users' => 10,
                'max_registers' => 5,
                'max_warehouses' => 2,
                'is_active' => true,
                'features' => ['pos', 'inventory', 'accounting', 'reports', 'multi-branch'],
            ]
        );

        // 2. Generate or use a unique 6-digit code for Hotel tenant
        $hotelCode = '839201'; // Clean 6-digit unique number
        if (Tenant::where('code', $hotelCode)->exists()) {
            $hotelCode = Tenant::generateUniqueCode();
        }

        // 3. Create Hotel Tenant
        $tenant = Tenant::updateOrCreate(
            ['slug' => 'grand-horizon-hotel'],
            [
                'name' => 'Grand Horizon Hotel & Suites',
                'trading_name' => 'Grand Horizon Hotel',
                'code' => $hotelCode,
                'slug' => 'grand-horizon-hotel',
                'kra_pin' => 'P051982736Z',
                'registration_number' => 'BN-2026-H8829',
                'business_type' => 'hospitality',
                'industry' => 'hotel',
                'status' => 'trial',
                'owner_name' => 'Alexander Kariuki',
                'owner_email' => 'hotel.owner@blepos.co.ke',
                'owner_phone' => '+254711223344',
                'country' => 'KEN',
                'county' => 'Nairobi',
                'city' => 'Nairobi',
                'address' => 'Upper Hill View Road, Nairobi',
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'settings' => [
                    'theme' => 'light',
                    'tax_rate' => 16.0,
                    'service_charge_rate' => 2.0,
                    'catering_levy_rate' => 2.0,
                ],
            ]
        );

        // 4. Create primary domain mapping
        TenantDomain::updateOrCreate(
            ['domain' => 'hotel.blepos.local'],
            [
                'tenant_id' => $tenant->id,
                'is_primary' => true,
                'is_verified' => true,
            ]
        );

        // 5. Create Subscription on Trial Plan
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

        // 6. Create Hotel Shops / Outlets
        $mainShop = Shop::updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'HTL-MAIN'],
            [
                'name' => 'Main Hotel & Restaurant',
                'branch_number' => '001',
                'kra_branch_id' => 'B001',
                'phone' => '+254711223344',
                'email' => 'restaurant@grandhorizon.co.ke',
                'address' => 'Ground Floor, Grand Horizon Hotel, Nairobi',
                'county' => 'Nairobi',
                'city' => 'Nairobi',
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'receipt_header' => "GRAND HORIZON HOTEL & SUITES\nTel: +254 711 223 344\nPIN: P051982736Z",
                'receipt_footer' => "Thank you for dining & staying with us!\nKaribu Tena",
                'is_active' => true,
            ]
        );

        // 7. Create Point of Sale Registers
        Register::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shop_id' => $mainShop->id, 'code' => 'REG-FRONT'],
            [
                'name' => 'Front Desk & Reception Till',
                'is_active' => true,
            ]
        );

        Register::updateOrCreate(
            ['tenant_id' => $tenant->id, 'shop_id' => $mainShop->id, 'code' => 'REG-BAR'],
            [
                'name' => 'Restaurant & Lounge Till',
                'is_active' => true,
            ]
        );

        // 8. Create Tenant Roles & Permissions
        $ownerRole = TenantRole::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'owner'],
            ['name' => 'Hotel Owner / GM', 'description' => 'Full operational & financial control', 'is_system' => true]
        );

        $cashierRole = TenantRole::firstOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'cashier'],
            ['name' => 'Front Desk / Cashier', 'description' => 'Point of Sale, shifts, receipts', 'is_system' => true]
        );

        $permissions = [
            'pos.checkout' => 'sales',
            'pos.refund' => 'sales',
            'inventory.view' => 'inventory',
            'inventory.adjust' => 'inventory',
            'reports.sales.view' => 'reports',
            'reports.profit.view' => 'reports',
            'shops.all.manage' => 'shops',
        ];

        $permIds = [];
        foreach ($permissions as $permName => $group) {
            $p = TenantPermission::firstOrCreate(
                ['name' => $permName],
                ['group' => $group, 'description' => ucfirst(str_replace('.', ' ', $permName))]
            );
            $permIds[] = $p->id;
        }

        $ownerRole->permissions()->sync($permIds);
        $cashierRole->permissions()->sync(
            TenantPermission::whereIn('name', ['pos.checkout', 'inventory.view'])->pluck('id')
        );

        // 9. Create Users (Owner & Cashier)
        $ownerUser = User::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'hotel.owner@blepos.co.ke'],
            [
                'name' => 'Alexander Kariuki (Owner)',
                'password' => Hash::make('Hotel@123456!'),
                'status' => 'active',
                'phone' => '+254711223344',
                'must_reset_password' => false,
                'email_verified_at' => now(),
            ]
        );
        $ownerUser->roles()->sync([$ownerRole->id]);
        $ownerUser->shops()->syncWithPivotValues([$mainShop->id], ['is_primary' => true]);

        $cashierUser = User::updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'hotel.cashier@blepos.co.ke'],
            [
                'name' => 'Mercy Chebet (Cashier)',
                'password' => Hash::make('Cashier@123456!'),
                'status' => 'active',
                'phone' => '+254722334455',
                'must_reset_password' => false,
                'email_verified_at' => now(),
            ]
        );
        $cashierUser->roles()->sync([$cashierRole->id]);
        $cashierUser->shops()->syncWithPivotValues([$mainShop->id], ['is_primary' => true]);

        // 10. Seed Categories
        $categoriesData = [
            'Rooms & Suites' => 'Accommodation rooms, standard, deluxe, and executive suites',
            'Buffet & Dining' => 'Main dining room, breakfast, lunch, and dinner specials',
            'Beverages & Lounge' => 'Hot beverages, fruit juices, sodas, and mocktails',
            'Conference & Events' => 'Meeting rooms, projector hire, and banquet hall packages',
        ];

        $categories = [];
        foreach ($categoriesData as $name => $desc) {
            $categories[$name] = Category::updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => $name],
                [
                    'slug' => Str::slug($name),
                    'description' => $desc,
                    'is_active' => true,
                ]
            );
        }

        // 11. Seed Hotel Products
        $productsData = [
            [
                'category' => 'Rooms & Suites',
                'name' => 'Deluxe King Room (Nightly)',
                'sku' => 'HTL-ROOM-DLX',
                'barcode' => '78910001',
                'unit_of_measure' => 'night',
                'cost_price' => 3000.00,
                'selling_price' => 8500.00,
                'tax_rate' => 16.00,
                'tax_type' => 'exclusive',
                'track_stock' => true,
                'is_reusable' => true,
                'availability_period_hours' => 24,
                'reorder_level' => 2,
                'stock' => 15,
            ],
            [
                'category' => 'Rooms & Suites',
                'name' => 'Standard Double Room (Nightly)',
                'sku' => 'HTL-ROOM-STD',
                'barcode' => '78910002',
                'unit_of_measure' => 'night',
                'cost_price' => 2000.00,
                'selling_price' => 6000.00,
                'tax_rate' => 16.00,
                'tax_type' => 'exclusive',
                'track_stock' => true,
                'is_reusable' => true,
                'availability_period_hours' => 24,
                'reorder_level' => 3,
                'stock' => 20,
            ],
            [
                'category' => 'Buffet & Dining',
                'name' => 'Full English & Swahili Breakfast Buffet',
                'sku' => 'HTL-BUFFET-BRK',
                'barcode' => '78920001',
                'unit_of_measure' => 'portion',
                'cost_price' => 550.00,
                'selling_price' => 1650.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 10,
                'stock' => 80,
            ],
            [
                'category' => 'Buffet & Dining',
                'name' => 'Grilled Prime Beef Sirloin Steak (300g)',
                'sku' => 'HTL-REST-STK',
                'barcode' => '78920002',
                'unit_of_measure' => 'portion',
                'cost_price' => 750.00,
                'selling_price' => 2200.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 5,
                'stock' => 45,
            ],
            [
                'category' => 'Buffet & Dining',
                'name' => 'Swahili Coconut Fish Curry with Rice',
                'sku' => 'HTL-REST-FSH',
                'barcode' => '78920003',
                'unit_of_measure' => 'portion',
                'cost_price' => 600.00,
                'selling_price' => 1850.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 5,
                'stock' => 35,
            ],
            [
                'category' => 'Beverages & Lounge',
                'name' => 'Fresh Passion & Mango Juice (500ml)',
                'sku' => 'HTL-BEV-JUC',
                'barcode' => '78930001',
                'unit_of_measure' => 'glass',
                'cost_price' => 80.00,
                'selling_price' => 350.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 15,
                'stock' => 120,
            ],
            [
                'category' => 'Beverages & Lounge',
                'name' => 'Natural Mineral Water (1000ml)',
                'sku' => 'HTL-BEV-WTR1L',
                'barcode' => '78930002',
                'unit_of_measure' => 'bottle',
                'cost_price' => 45.00,
                'selling_price' => 150.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 20,
                'stock' => 250,
            ],
            [
                'category' => 'Beverages & Lounge',
                'name' => 'Kenyan Highland Special Blend Coffee',
                'sku' => 'HTL-BEV-COF',
                'barcode' => '78930003',
                'unit_of_measure' => 'cup',
                'cost_price' => 60.00,
                'selling_price' => 280.00,
                'tax_rate' => 16.00,
                'tax_type' => 'inclusive',
                'track_stock' => true,
                'reorder_level' => 25,
                'stock' => 300,
            ],
            [
                'category' => 'Conference & Events',
                'name' => 'Executive Boardroom Half-Day Package',
                'sku' => 'HTL-CONF-HALF',
                'barcode' => '78940001',
                'unit_of_measure' => 'session',
                'cost_price' => 5000.00,
                'selling_price' => 25000.00,
                'tax_rate' => 16.00,
                'tax_type' => 'exclusive',
                'track_stock' => true,
                'reorder_level' => 1,
                'stock' => 4,
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
                    'is_reusable' => $prod['is_reusable'] ?? false,
                    'availability_period_hours' => $prod['availability_period_hours'] ?? null,
                    'reorder_level' => $prod['reorder_level'],
                    'is_active' => true,
                ]
            );

            // Record initial stock level in Main Shop
            InventoryStock::updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'shop_id' => $mainShop->id,
                    'product_id' => $product->id,
                ],
                [
                    'quantity' => $prod['stock'],
                    'reserved_quantity' => 0,
                ]
            );
        }
    }
}
