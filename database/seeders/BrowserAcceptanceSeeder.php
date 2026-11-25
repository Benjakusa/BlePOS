<?php

namespace Database\Seeders;

use App\Models\CashierShift;
use App\Models\PlatformRole;
use App\Models\PlatformUser;
use App\Models\Register;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;

class BrowserAcceptanceSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PharmacyDemoSeeder::class);
        $this->call(PlatformRoleSeeder::class);

        $platformAdmin = PlatformUser::query()->updateOrCreate(
            ['email' => 'platform.owner@blepos.test'],
            [
                'name' => 'Browser Acceptance Admin',
                'password' => 'Platform@123456!',
                'status' => 'active',
                'must_reset_password' => false,
            ],
        );
        $platformAdmin->forceFill([
            'totp_secret' => null,
            'mfa_confirmed_at' => null,
            'recovery_code_hashes' => null,
        ])->save();
        $platformAdmin->roles()->sync([PlatformRole::query()->where('slug', 'super-administrator')->value('id')]);

        $tenant = Tenant::query()->where('slug', 'savanna-bistro-grill')->firstOrFail();
        $user = User::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->where('email', 'restaurant.owner@blepos.co.ke')->firstOrFail();
        $user->forceFill([
            'totp_secret' => null,
            'mfa_confirmed_at' => null,
            'recovery_code_hashes' => null,
        ])->save();
        $pharmacist = User::withoutGlobalScopes()->where('email', 'pharmacist.cbd@afyacare.test')->firstOrFail();
        $pharmacist->forceFill([
            'totp_secret' => null,
            'mfa_confirmed_at' => null,
            'recovery_code_hashes' => null,
        ])->save();
        $shop = Shop::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->where('code', 'SB-MAIN')->firstOrFail();
        $register = Register::withoutGlobalScopes()->where('tenant_id', $tenant->id)
            ->where('shop_id', $shop->id)->where('code', 'TILL-01')->firstOrFail();

        CashierShift::withoutGlobalScopes()->updateOrCreate(
            ['user_id' => $user->id, 'status' => 'open'],
            [
                'tenant_id' => $tenant->id,
                'shop_id' => $shop->id,
                'register_id' => $register->id,
                'shift_number' => 'SH-BROWSER-ACCEPTANCE',
                'opening_float' => '1000.00',
                'cash_in' => '0.00',
                'cash_out' => '0.00',
                'cash_sales' => '0.00',
                'mpesa_sales' => '0.00',
                'card_sales' => '0.00',
                'expected_cash' => '1000.00',
                'opened_at' => now(),
            ]
        );
    }
}
