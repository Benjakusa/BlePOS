<?php

namespace Database\Seeders;

use App\Models\MpesaCredential;
use App\Models\Shop;
use App\Models\Tenant;
use App\Services\MpesaEncryptionService;
use Illuminate\Database\Seeder;

class MpesaSandboxSeeder extends Seeder
{
    public function run(): void
    {
        $enc = app(MpesaEncryptionService::class);

        // 1. Platform-level sandbox credential
        MpesaCredential::updateOrCreate(
            ['owner_type' => 'platform', 'owner_id' => null, 'environment' => 'sandbox', 'shortcode' => '174379'],
            [
                'tenant_id' => null,
                'branch_id' => null,
                'passkey' => $enc->encrypt('bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
                'consumer_key' => $enc->encrypt('GKEJKlFHRRMgRRpX7i8GG0YpNqRJ9fXN'),
                'consumer_secret' => $enc->encrypt('MpXqXrL2VVnB3kqDR0M8e1JU7fXrQhKD'),
                'callback_url' => 'https://blepos.co.ke/api/mpesa/callback/platform',
                'is_active' => true,
                'is_default' => true,
            ]
        );

        // 2. Demo Tenant A (Restaurant) — tenant-level + branch-level
        $restaurantTenant = Tenant::where('slug', 'savanna-bistro-grill')->first();
        if ($restaurantTenant) {
            MpesaCredential::updateOrCreate(
                ['owner_type' => 'tenant', 'owner_id' => $restaurantTenant->id, 'environment' => 'sandbox', 'shortcode' => '174379'],
                [
                    'tenant_id' => $restaurantTenant->id,
                    'branch_id' => null,
                    'passkey' => $enc->encrypt('bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
                    'consumer_key' => $enc->encrypt('RstGKEJKlFHRRMgRRpX7i8GG0YpNqRJ9fXN'),
                    'consumer_secret' => $enc->encrypt('RstMpXqXrL2VVnB3kqDR0M8e1JU7fXrQhKD'),
                    'callback_url' => 'https://blepos.co.ke/api/mpesa/callback/tenant/'.$restaurantTenant->id,
                    'is_active' => true,
                    'is_default' => true,
                ]
            );

            $shop = Shop::withoutGlobalScopes()->where('tenant_id', $restaurantTenant->id)->first();
            if ($shop) {
                MpesaCredential::updateOrCreate(
                    ['owner_type' => 'branch', 'owner_id' => $shop->id, 'environment' => 'sandbox', 'shortcode' => '174380'],
                    [
                        'tenant_id' => $restaurantTenant->id,
                        'branch_id' => $shop->id,
                        'passkey' => $enc->encrypt('bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
                        'consumer_key' => $enc->encrypt('BranchRstGKEJKlFHRR123'),
                        'consumer_secret' => $enc->encrypt('BranchRstMpXqXrL2VVnB3k456'),
                        'callback_url' => 'https://blepos.co.ke/api/mpesa/callback/branch/'.$shop->id,
                        'is_active' => true,
                        'is_default' => true,
                    ]
                );
            }
        }

        // 3. Demo Tenant B (Hotel) — tenant-level + branch-level
        $hotelTenant = Tenant::where('slug', 'grand-horizon-hotel')->first();
        if ($hotelTenant) {
            MpesaCredential::updateOrCreate(
                ['owner_type' => 'tenant', 'owner_id' => $hotelTenant->id, 'environment' => 'sandbox', 'shortcode' => '174379'],
                [
                    'tenant_id' => $hotelTenant->id,
                    'branch_id' => null,
                    'passkey' => $enc->encrypt('bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
                    'consumer_key' => $enc->encrypt('HtlGKEJKlFHRRMgRRpX7i8GG0YpNqRJ9fXN'),
                    'consumer_secret' => $enc->encrypt('HtlMpXqXrL2VVnB3kqDR0M8e1JU7fXrQhKD'),
                    'callback_url' => 'https://blepos.co.ke/api/mpesa/callback/tenant/'.$hotelTenant->id,
                    'is_active' => true,
                    'is_default' => true,
                ]
            );

            $hotelShop = Shop::withoutGlobalScopes()->where('tenant_id', $hotelTenant->id)->first();
            if ($hotelShop) {
                MpesaCredential::updateOrCreate(
                    ['owner_type' => 'branch', 'owner_id' => $hotelShop->id, 'environment' => 'sandbox', 'shortcode' => '174381'],
                    [
                        'tenant_id' => $hotelTenant->id,
                        'branch_id' => $hotelShop->id,
                        'passkey' => $enc->encrypt('bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919'),
                        'consumer_key' => $enc->encrypt('BranchHtlGKEJKlFHRR789'),
                        'consumer_secret' => $enc->encrypt('BranchHtlMpXqXrL2VVnB3k012'),
                        'callback_url' => 'https://blepos.co.ke/api/mpesa/callback/branch/'.$hotelShop->id,
                        'is_active' => true,
                        'is_default' => true,
                    ]
                );
            }
        }
    }
}
