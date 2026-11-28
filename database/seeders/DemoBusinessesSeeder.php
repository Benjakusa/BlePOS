<?php

namespace Database\Seeders;

use App\Models\CashierShift;
use App\Models\Category;
use App\Models\FleetBooking;
use App\Models\FleetCustomer;
use App\Models\FleetDriver;
use App\Models\FleetVehicle;
use App\Models\GymClass;
use App\Models\GymClassBooking;
use App\Models\GymMember;
use App\Models\GymPlan;
use App\Models\GymSubscription;
use App\Models\GymTrainer;
use App\Models\IndustryTemplate;
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
use App\Models\Warehouse;
use App\Services\ModuleService;
use App\Support\SeederScopeContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoBusinessesSeeder extends Seeder
{
    private const OWNER_PASSWORD = 'DemoOwner@2026!';

    private const CASHIER_PASSWORD = 'DemoCashier@2026!';

    public function run(): void
    {
        $scope = new SeederScopeContext;
        $trialPlan = $this->seedPlans();
        $permissions = $this->seedPermissions();

        $gym = $this->seedBusiness([
            'name' => 'LiftLab Fitness Club',
            'trading_name' => 'LiftLab',
            'code' => 'GYM-DEMO-01',
            'slug' => 'liftlab-fitness',
            'business_type' => 'gym-fitness',
            'industry' => 'gym-fitness',
            'owner_name' => 'LiftLab Owner',
            'owner_email' => 'owner@liftlab.test',
            'owner_phone' => '+254700101001',
            'county' => 'Nairobi',
            'address' => 'Kilimani, Nairobi',
            'domain' => 'liftlab.blepos.local',
            'shop_name' => 'LiftLab Kilimani',
            'shop_code' => 'LL-KIL',
            'register_name' => 'LiftLab Front Desk',
            'register_code' => 'LL-TILL-01',
            'cashier_email' => 'frontdesk@liftlab.test',
            'cashier_name' => 'LiftLab Front Desk',
        ], $trialPlan, $permissions);
        $this->seedGymData($gym['tenant'], $gym['shop']);

        $fleet = $this->seedBusiness([
            'name' => 'SwiftMove Fleet Services',
            'trading_name' => 'SwiftMove',
            'code' => 'FLT-DEMO-01',
            'slug' => 'swiftmove-fleet',
            'business_type' => 'car-hire-fleet',
            'industry' => 'car-hire-fleet',
            'owner_name' => 'SwiftMove Owner',
            'owner_email' => 'owner@swiftmove.test',
            'owner_phone' => '+254700202001',
            'county' => 'Nairobi',
            'address' => 'Westlands, Nairobi',
            'domain' => 'swiftmove.blepos.local',
            'shop_name' => 'SwiftMove Dispatch',
            'shop_code' => 'SM-NBO',
            'register_name' => 'SwiftMove Dispatch Desk',
            'register_code' => 'SM-TILL-01',
            'cashier_email' => 'dispatcher@swiftmove.test',
            'cashier_name' => 'SwiftMove Dispatcher',
        ], $trialPlan, $permissions);
        $this->seedFleetData($fleet['tenant'], $fleet['shop']);

        $wholesale = $this->seedBusiness([
            'name' => 'NorthStar Wholesale Distributors',
            'trading_name' => 'NorthStar Wholesale',
            'code' => 'WHOLESALE-DEMO-01',
            'slug' => 'northstar-wholesale',
            'business_type' => 'wholesale',
            'industry' => 'wholesale',
            'owner_name' => 'NorthStar Owner',
            'owner_email' => 'owner@northstar.test',
            'owner_phone' => '+254700303001',
            'county' => 'Nairobi',
            'address' => 'Industrial Area, Nairobi',
            'domain' => 'northstar.blepos.local',
            'shop_name' => 'NorthStar Main Depot',
            'shop_code' => 'NS-DEPOT',
            'register_name' => 'NorthStar Sales Counter',
            'register_code' => 'NS-TILL-01',
            'cashier_email' => 'sales@northstar.test',
            'cashier_name' => 'NorthStar Sales Desk',
        ], $trialPlan, $permissions);
        $this->seedWholesaleData($wholesale['tenant'], $wholesale['shop']);

        $this->command?->info('Seeded LiftLab Fitness, SwiftMove Fleet Services, and NorthStar Wholesale.');
    }

    /** @return array<string, mixed> */
    private function seedPlans(): Plan
    {
        $plans = [
            ['code' => 'trial', 'name' => 'Free Trial', 'monthly_price' => 0, 'annual_price' => 0, 'trial_days' => 14, 'max_shops' => 3, 'max_users' => 15, 'max_registers' => 5, 'max_warehouses' => 2],
            ['code' => 'starter', 'name' => 'Starter Plan', 'monthly_price' => 2500, 'annual_price' => 25000, 'trial_days' => 0, 'max_shops' => 1, 'max_users' => 5, 'max_registers' => 2, 'max_warehouses' => 1],
            ['code' => 'growth', 'name' => 'Growth Plan', 'monthly_price' => 6500, 'annual_price' => 65000, 'trial_days' => 0, 'max_shops' => 5, 'max_users' => 25, 'max_registers' => 10, 'max_warehouses' => 3],
            ['code' => 'enterprise', 'name' => 'Enterprise Plan', 'monthly_price' => 15000, 'annual_price' => 150000, 'trial_days' => 0, 'max_shops' => 20, 'max_users' => 100, 'max_registers' => 40, 'max_warehouses' => 10],
        ];
        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['code' => $plan['code']],
                $plan + [
                    'currency' => 'KES',
                    'description' => $plan['name'].' sample platform plan for local testing.',
                    'is_active' => true,
                    'features' => ['pos', 'inventory', 'reports', 'staff-management'],
                ],
            );
        }

        return Plan::query()->where('code', 'trial')->firstOrFail();
    }

    /** @return array<string, int> */
    private function seedPermissions(): array
    {
        $names = [
            'pos.sale.create' => ['pos', 'Create POS sales'],
            'pos.sales.view' => ['pos', 'View sales and receipts'],
            'pos.sale.void' => ['pos', 'Void POS sales'],
            'pos.sale.credit' => ['pos', 'Create credit sales'],
            'pos.discount.apply' => ['pos', 'Apply permitted discounts'],
            'pos.discount.override' => ['pos', 'Override discount limits'],
            'shifts.view' => ['shifts', 'View cashier shifts'],
            'shifts.manage' => ['shifts', 'Manage cashier shifts'],
            'catalog.products.manage' => ['catalog', 'Manage products'],
            'catalog.categories.manage' => ['catalog', 'Manage product categories'],
            'inventory.view' => ['inventory', 'View stock'],
            'inventory.adjust' => ['inventory', 'Adjust stock'],
            'inventory.receive' => ['inventory', 'Receive stock'],
            'inventory.manage' => ['inventory', 'Manage inventory'],
            'accounting.view' => ['accounting', 'View accounts and journals'],
            'users.manage' => ['administration', 'Manage staff'],
            'shops.all.manage' => ['administration', 'Manage all shops'],
        ];
        $permissionIds = [];
        foreach ($names as $name => [$group, $description]) {
            $permissionIds[$name] = TenantPermission::query()->firstOrCreate(
                ['name' => $name], ['group' => $group, 'description' => $description],
            )->id;
        }

        return $permissionIds;
    }

    /** @param array<string, mixed> $data @param array<string, int> $permissions @return array{tenant: Tenant, shop: Shop, register: Register, owner: User, cashier: User} */
    private function seedBusiness(array $data, Plan $trialPlan, array $permissions): array
    {
        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => $data['slug']],
            [
                'name' => $data['name'],
                'trading_name' => $data['trading_name'],
                'code' => $data['code'],
                'registration_number' => 'DEMO-'.strtoupper($data['code']),
                'business_type' => $data['business_type'],
                'industry' => $data['industry'],
                'status' => 'active',
                'owner_name' => $data['owner_name'],
                'owner_email' => $data['owner_email'],
                'owner_phone' => $data['owner_phone'],
                'country' => 'KEN',
                'county' => $data['county'],
                'city' => 'Nairobi',
                'address' => $data['address'],
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'settings' => ['demo' => true, 'tax_rate' => 16],
            ],
        );

        TenantDomain::query()->updateOrCreate(
            ['domain' => $data['domain']],
            ['tenant_id' => $tenant->id, 'is_primary' => true, 'is_verified' => true],
        );
        Subscription::query()->updateOrCreate(
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
            ],
        );

        $template = IndustryTemplate::query()->where('slug', $data['industry'])->firstOrFail();
        app(ModuleService::class)->applyIndustryTemplate($tenant, $template);

        $shop = Shop::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => $data['shop_code']],
            [
                'name' => $data['shop_name'],
                'branch_number' => '001',
                'phone' => $data['owner_phone'],
                'email' => $data['owner_email'],
                'address' => $data['address'],
                'county' => $data['county'],
                'city' => 'Nairobi',
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'is_active' => true,
            ],
        );
        $register = Register::query()->updateOrCreate(
            ['shop_id' => $shop->id, 'code' => $data['register_code']],
            ['tenant_id' => $tenant->id, 'name' => $data['register_name'], 'is_active' => true],
        );

        $ownerRole = TenantRole::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'owner'],
            ['name' => 'Business Owner', 'description' => 'Full access for local demo testing.', 'is_system' => true],
        );
        $ownerRole->permissions()->sync(array_values($permissions));
        $cashierPermissions = collect(['pos.sale.create', 'pos.sales.view', 'shifts.view', 'shifts.manage'])
            ->map(fn (string $name): int => $permissions[$name])->all();
        if ($data['industry'] === 'wholesale') {
            $cashierPermissions = array_merge($cashierPermissions, [
                $permissions['inventory.view'],
                $permissions['catalog.products.manage'],
            ]);
        }
        $cashierRole = TenantRole::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'cashier'],
            ['name' => 'Cashier', 'description' => 'POS and shift testing role.', 'is_system' => true],
        );
        $cashierRole->permissions()->sync(array_values(array_unique($cashierPermissions)));

        $owner = User::query()->updateOrCreate(
            ['email' => $data['owner_email']],
            [
                'tenant_id' => $tenant->id,
                'name' => $data['owner_name'],
                'password' => Hash::make(self::OWNER_PASSWORD),
                'status' => 'active',
                'phone' => $data['owner_phone'],
                'must_reset_password' => false,
            ],
        );
        $owner->roles()->sync([$ownerRole->id]);
        $owner->shops()->sync([$shop->id => ['is_primary' => true]]);

        $cashier = User::query()->updateOrCreate(
            ['email' => $data['cashier_email']],
            [
                'tenant_id' => $tenant->id,
                'name' => $data['cashier_name'],
                'password' => Hash::make(self::CASHIER_PASSWORD),
                'status' => 'active',
                'phone' => $data['owner_phone'],
                'must_reset_password' => false,
            ],
        );
        $cashier->roles()->sync([$cashierRole->id]);
        $cashier->shops()->sync([$shop->id => ['is_primary' => true]]);

        CashierShift::query()->updateOrCreate(
            ['user_id' => $cashier->id, 'status' => 'open'],
            [
                'tenant_id' => $tenant->id,
                'shop_id' => $shop->id,
                'register_id' => $register->id,
                'shift_number' => 'SH-'.$data['code'],
                'opening_float' => '2000.00',
                'cash_in' => '0.00',
                'cash_out' => '0.00',
                'cash_sales' => '0.00',
                'mpesa_sales' => '0.00',
                'card_sales' => '0.00',
                'expected_cash' => '2000.00',
                'opened_at' => now(),
            ],
        );

        return compact('tenant', 'shop', 'register', 'owner', 'cashier');
    }

    private function seedGymData(Tenant $tenant, Shop $shop): void
    {
        $plan = GymPlan::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Monthly Unlimited'],
            ['shop_id' => $shop->id, 'billing_cycle' => 'monthly', 'price' => '4500.00', 'is_active' => true],
        );
        $member = GymMember::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => '+254711101001'],
            ['shop_id' => $shop->id, 'name' => 'Amani Wanjiru', 'email' => 'amani.member@liftlab.test', 'dob' => '1994-06-12', 'notes' => 'Demo member record.'],
        );
        GymSubscription::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'member_id' => $member->id, 'plan_id' => $plan->id],
            ['shop_id' => $shop->id, 'status' => 'active', 'start_date' => now()->subDays(8)->toDateString(), 'end_date' => now()->addDays(22)->toDateString(), 'notes' => 'Seeded active membership.'],
        );
        $trainer = GymTrainer::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'email' => 'trainer@liftlab.test'],
            ['shop_id' => $shop->id, 'name' => 'Kevin Mwangi', 'phone' => '+254711101002', 'commission_rate' => '10.00'],
        );
        $class = GymClass::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'name' => 'Strength & Conditioning'],
            ['shop_id' => $shop->id, 'trainer_id' => $trainer->id, 'scheduled_at' => now()->addDay()->setTime(18, 0), 'duration_minutes' => 60, 'capacity' => 18],
        );
        GymClassBooking::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'class_id' => $class->id, 'member_id' => $member->id],
            ['shop_id' => $shop->id, 'status' => 'confirmed'],
        );
    }

    private function seedFleetData(Tenant $tenant, Shop $shop): void
    {
        $customer = FleetCustomer::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'id_number' => 'DEMO-FLEET-1001'],
            ['shop_id' => $shop->id, 'name' => 'Njeri Kamau', 'phone' => '+254722202010', 'license_number' => 'DL-DEMO-1001', 'license_expiry' => now()->addYears(2)->toDateString(), 'email' => 'customer@swiftmove.test'],
        );
        $driver = FleetDriver::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'license_number' => 'DL-DEMO-1002'],
            ['shop_id' => $shop->id, 'name' => 'Peter Ochieng', 'phone' => '+254722202011', 'license_expiry' => now()->addYears(2)->toDateString(), 'is_active' => true],
        );
        $vehicle = FleetVehicle::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'plate' => 'KDA 101A'],
            ['shop_id' => $shop->id, 'make' => 'Toyota', 'model' => 'Fielder', 'year' => 2022, 'color' => 'Silver', 'type' => 'car', 'daily_rate' => '6500.00', 'weekly_rate' => '39000.00', 'deposit_amount' => '10000.00', 'insurance_expiry' => now()->addMonths(8)->toDateString(), 'inspection_expiry' => now()->addMonths(6)->toDateString(), 'license_expiry' => now()->addMonths(10)->toDateString(), 'status' => 'available', 'mileage' => 48200],
        );
        FleetVehicle::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'plate' => 'KDB 202B'],
            ['shop_id' => $shop->id, 'make' => 'Nissan', 'model' => 'NV200', 'year' => 2021, 'color' => 'White', 'type' => 'van', 'daily_rate' => '9500.00', 'weekly_rate' => '57000.00', 'deposit_amount' => '15000.00', 'status' => 'available', 'mileage' => 76100],
        );
        FleetBooking::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'vehicle_id' => $vehicle->id, 'customer_id' => $customer->id],
            ['shop_id' => $shop->id, 'driver_id' => $driver->id, 'pickup_at' => now()->addDays(2), 'return_at' => now()->addDays(4), 'status' => 'confirmed', 'rate_type' => 'daily', 'agreed_rate' => '6500.00', 'deposit_amount' => '10000.00', 'deposit_paid' => false, 'total_billed' => '13000.00', 'notes' => 'Demo upcoming fleet booking.'],
        );
    }

    private function seedWholesaleData(Tenant $tenant, Shop $shop): void
    {
        Warehouse::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'code' => 'NS-WH-01'],
            ['shop_id' => $shop->id, 'name' => 'NorthStar Main Warehouse', 'location' => 'Athi River Road, Nairobi', 'is_active' => true],
        );
        $category = Category::query()->updateOrCreate(
            ['tenant_id' => $tenant->id, 'slug' => 'dry-goods'],
            ['name' => 'Dry Goods', 'description' => 'Bulk dry-goods catalogue for demo transactions.', 'is_active' => true],
        );
        foreach ([
            ['name' => 'Grade 1 Maize Flour 2kg', 'sku' => 'NS-MF-2KG', 'cost_price' => '105.00', 'selling_price' => '135.00', 'stock' => '240.0000'],
            ['name' => 'Long Grain Rice 5kg', 'sku' => 'NS-RICE-5KG', 'cost_price' => '530.00', 'selling_price' => '620.00', 'stock' => '160.0000'],
            ['name' => 'Cooking Oil 20L', 'sku' => 'NS-OIL-20L', 'cost_price' => '4600.00', 'selling_price' => '5200.00', 'stock' => '35.0000'],
        ] as $productData) {
            $product = Product::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'sku' => $productData['sku']],
                ['category_id' => $category->id, 'name' => $productData['name'], 'unit_of_measure' => 'piece', 'cost_price' => $productData['cost_price'], 'selling_price' => $productData['selling_price'], 'tax_rate' => '16.00', 'tax_type' => 'inclusive', 'track_stock' => true, 'reorder_level' => 10, 'is_active' => true],
            );
            InventoryStock::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'shop_id' => $shop->id, 'product_id' => $product->id],
                ['quantity' => $productData['stock'], 'reserved_quantity' => '0.0000'],
            );
        }
    }
}
