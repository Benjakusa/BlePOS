<?php

namespace Database\Seeders;

use App\Models\IndustryTemplate;
use App\Models\Plan;
use App\Models\Register;
use App\Models\Shop;
use App\Models\Subscription;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\ModuleService;
use App\Support\SeederScopeContext;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ParcelDeliveryDemoSeeder extends Seeder
{
    public function run(): void
    {
        $scope = new SeederScopeContext;

        $this->call(ModuleCatalogueSeeder::class);

        $trialPlan = Plan::query()->firstOrCreate(
            ['code' => 'trial'],
            [
                'name' => 'Free Trial (14 Days)',
                'description' => '14-day trial plan for parcel delivery operations.',
                'currency' => 'KES',
                'monthly_price' => 0,
                'annual_price' => 0,
                'trial_days' => 14,
                'max_shops' => 5,
                'max_users' => 20,
                'max_registers' => 10,
                'max_warehouses' => 2,
                'is_active' => true,
                'features' => ['pos', 'multi-branch', 'delivery', 'reports'],
            ],
        );

        $tenant = Tenant::query()->updateOrCreate(
            ['slug' => 'parcelway-courier'],
            [
                'name' => 'ParcelWay Courier & Logistics',
                'trading_name' => 'ParcelWay',
                'code' => Tenant::query()->where('slug', 'parcelway-courier')->value('code') ?? $this->uniqueTenantCode(),
                'slug' => 'parcelway-courier',
                'registration_number' => 'DEMO-PCL-2026-001',
                'business_type' => 'parcel-delivery',
                'industry' => 'parcel-delivery',
                'status' => 'trial',
                'owner_name' => 'Mercy Njeri',
                'owner_email' => 'owner@parcelway.test',
                'owner_phone' => '+254700800001',
                'country' => 'KEN',
                'county' => 'Nairobi',
                'city' => 'Nairobi',
                'address' => 'ParcelWay Central Office, Tom Mboya Street, Nairobi',
                'currency' => 'KES',
                'timezone' => 'Africa/Nairobi',
                'settings' => [
                    'theme' => 'light',
                    'tax_rate' => 16.0,
                    'demo' => true,
                    'service_type' => 'parcel-delivery',
                ],
            ],
        );

        TenantDomain::query()->updateOrCreate(
            ['domain' => 'parcelway.blepos.local'],
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

        $offices = [
            [
                'code' => 'PW-NBO',
                'branch_number' => '001',
                'name' => 'Nairobi Central Office',
                'county' => 'Nairobi',
                'city' => 'Nairobi',
                'phone' => '+254700800010',
                'email' => 'nairobi@parcelway.test',
                'address' => 'Tom Mboya Street, Nairobi',
            ],
            [
                'code' => 'PW-MBA',
                'branch_number' => '002',
                'name' => 'Mombasa Destination Office',
                'county' => 'Mombasa',
                'city' => 'Mombasa',
                'phone' => '+254700800020',
                'email' => 'mombasa@parcelway.test',
                'address' => 'Digo Road, Mombasa',
            ],
            [
                'code' => 'PW-KSM',
                'branch_number' => '003',
                'name' => 'Kisumu Destination Office',
                'county' => 'Kisumu',
                'city' => 'Kisumu',
                'phone' => '+254700800030',
                'email' => 'kisumu@parcelway.test',
                'address' => 'Oginga Odinga Street, Kisumu',
            ],
            [
                'code' => 'PW-ELD',
                'branch_number' => '004',
                'name' => 'Eldoret Destination Office',
                'county' => 'Uasin Gishu',
                'city' => 'Eldoret',
                'phone' => '+254700800040',
                'email' => 'eldoret@parcelway.test',
                'address' => 'Uganda Road, Eldoret',
            ],
        ];

        $shops = [];
        foreach ($offices as $office) {
            $shops[$office['code']] = Shop::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'code' => $office['code']],
                [
                    ...$office,
                    'tenant_id' => $tenant->id,
                    'currency' => 'KES',
                    'timezone' => 'Africa/Nairobi',
                    'receipt_header' => "PARCELWAY COURIER\n{$office['name']}\n{$office['city']}, Kenya",
                    'receipt_footer' => 'Thank you for choosing ParcelWay.',
                    'is_active' => true,
                ],
            );

            Register::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'shop_id' => $shops[$office['code']]->id, 'code' => 'COUNTER-01'],
                ['name' => 'Parcel Counter', 'is_active' => true],
            );
        }

        $permissionGroups = [
            'pos.sale.create' => 'sales',
            'pos.sales.view' => 'sales',
            'shops.all.manage' => 'administration',
            'users.manage' => 'administration',
            'reports.sales.view' => 'reports',
            'delivery.manage' => 'delivery',
            'delivery.dispatch' => 'delivery',
            'delivery.track' => 'delivery',
            'parcel.view' => 'parcel',
            'parcel.receive' => 'parcel',
            'parcel.dispatch' => 'parcel',
            'parcel.track' => 'parcel',
            'parcel.deliver' => 'parcel',
        ];

        $permissions = [];
        foreach ($permissionGroups as $name => $group) {
            $permissions[$name] = TenantPermission::query()->firstOrCreate(
                ['name' => $name],
                ['group' => $group, 'description' => ucfirst(str_replace('.', ' ', $name))],
            );
        }

        $roleDefinitions = [
            'owner' => ['Company Owner', array_keys($permissionGroups)],
            'counter-clerk' => ['Counter Clerk', ['pos.sale.create', 'pos.sales.view', 'parcel.view', 'parcel.receive']],
            'office-dispatcher' => ['Office Dispatcher', ['delivery.manage', 'delivery.dispatch', 'delivery.track', 'parcel.view', 'parcel.dispatch', 'parcel.track', 'shops.all.manage', 'reports.sales.view']],
            'courier' => ['Courier', ['parcel.view', 'delivery.track', 'parcel.track', 'parcel.deliver']],
        ];

        $roles = [];
        foreach ($roleDefinitions as $slug => [$name, $grants]) {
            $roles[$slug] = TenantRole::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => $slug],
                ['name' => $name, 'description' => "{$name} ParcelWay demo role", 'is_system' => true],
            );
            $roles[$slug]->permissions()->sync(
                collect($grants)->map(fn (string $permission) => $permissions[$permission]->id)->all(),
            );
        }

        $staff = [
            [
                'email' => 'owner@parcelway.test',
                'name' => 'Mercy Njeri (Owner)',
                'phone' => '+254700800001',
                'password' => 'ParcelOwner@2026!',
                'role' => 'owner',
                'shops' => array_keys($shops),
                'primary_shop' => 'PW-NBO',
            ],
            [
                'email' => 'counter.nairobi@parcelway.test',
                'name' => 'Brian Otieno (Nairobi Counter Clerk)',
                'phone' => '+254700800011',
                'password' => 'ParcelCounter@2026!',
                'role' => 'counter-clerk',
                'shops' => ['PW-NBO'],
                'primary_shop' => 'PW-NBO',
            ],
            [
                'email' => 'counter.mombasa@parcelway.test',
                'name' => 'Amina Hassan (Mombasa Counter Clerk)',
                'phone' => '+254700800021',
                'password' => 'ParcelCounter@2026!',
                'role' => 'counter-clerk',
                'shops' => ['PW-MBA'],
                'primary_shop' => 'PW-MBA',
            ],
            [
                'email' => 'counter.kisumu@parcelway.test',
                'name' => 'Peter Ochieng (Kisumu Counter Clerk)',
                'phone' => '+254700800031',
                'password' => 'ParcelCounter@2026!',
                'role' => 'counter-clerk',
                'shops' => ['PW-KSM'],
                'primary_shop' => 'PW-KSM',
            ],
            [
                'email' => 'counter.eldoret@parcelway.test',
                'name' => 'Faith Chelimo (Eldoret Counter Clerk)',
                'phone' => '+254700800041',
                'password' => 'ParcelCounter@2026!',
                'role' => 'counter-clerk',
                'shops' => ['PW-ELD'],
                'primary_shop' => 'PW-ELD',
            ],
            [
                'email' => 'dispatcher@parcelway.test',
                'name' => 'Joseph Mwangi (Office Dispatcher)',
                'phone' => '+254700800002',
                'password' => 'ParcelDispatch@2026!',
                'role' => 'office-dispatcher',
                'shops' => array_keys($shops),
                'primary_shop' => 'PW-NBO',
            ],
            [
                'email' => 'courier@parcelway.test',
                'name' => 'David Kiptoo (Courier)',
                'phone' => '+254700800003',
                'password' => 'ParcelCourier@2026!',
                'role' => 'courier',
                'shops' => ['PW-NBO', 'PW-MBA'],
                'primary_shop' => 'PW-NBO',
            ],
        ];

        foreach ($staff as $member) {
            $user = User::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'email' => $member['email']],
                [
                    'name' => $member['name'],
                    'password' => Hash::make($member['password']),
                    'status' => 'active',
                    'phone' => $member['phone'],
                    'must_reset_password' => false,
                    'email_verified_at' => now(),
                ],
            );

            $user->roles()->sync([$roles[$member['role']]->id]);
            $shopIds = collect($member['shops'])->map(fn (string $code) => $shops[$code]->id)->all();
            $primaryShopId = $shops[$member['primary_shop']]->id;
            $user->shops()->syncWithPivotValues($shopIds, ['is_primary' => false]);
            $user->shops()->updateExistingPivot($primaryShopId, ['is_primary' => true]);
        }

        $template = IndustryTemplate::query()->where('slug', 'parcel-delivery')->firstOrFail();
        app(ModuleService::class)->applyIndustryTemplate($tenant, $template);
    }

    private function uniqueTenantCode(): string
    {
        $code = 'PD2026';

        return Tenant::query()->where('code', $code)->exists()
            ? Tenant::generateUniqueCode()
            : $code;
    }
}
