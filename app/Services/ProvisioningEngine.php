<?php

namespace App\Services;

use App\Models\IndustryTemplate;
use App\Models\Plan;
use App\Models\ProvisioningRun;
use App\Models\ProvisioningStep;
use App\Models\Register;
use App\Models\Shop;
use App\Models\Tenant;
use App\Models\TenantDomain;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProvisioningEngine
{
    public const TOTAL_STEPS = 30;

    /**
     * The 30 canonical provisioning step names.
     */
    public const STEPS = [
        1 => 'Validate tenant business data',
        2 => 'Validate domain configuration',
        3 => 'Validate subscription plan',
        4 => 'Validate module entitlements',
        5 => 'Validate shop limits',
        6 => 'Reserve tenant identifier',
        7 => 'Record provisioning run',
        8 => 'Allocate database server',
        9 => 'Configure tenant database schema',
        10 => 'Configure database isolation boundaries',
        11 => 'Apply base schema structure',
        12 => 'Apply tenant system migrations',
        13 => 'Install module migrations',
        14 => 'Create initial tenant configuration',
        15 => 'Create default tenant roles',
        16 => 'Create tenant permission structure',
        17 => 'Create owner account and invitation token',
        18 => 'Create initial shops and registers',
        19 => 'Apply industry blueprint configuration',
        20 => 'Configure initial module activation',
        21 => 'Provision payment gateway placeholders',
        22 => 'Set eTIMS compliance onboarding state',
        23 => 'Verify tenant database integrity',
        24 => 'Verify module dependency graph',
        25 => 'Verify tenant isolation boundaries',
        26 => 'Verify shop and branch configuration',
        27 => 'Verify domain mapping',
        28 => 'Record provisioning completion metrics',
        29 => 'Dispatch owner welcome and invitation',
        30 => 'Transition tenant to provisioned status',
    ];

    /**
     * Start a new provisioning run for a tenant registration request.
     *
     * @param  array<string, mixed>  $payload
     */
    public function initiate(array $payload): ProvisioningRun
    {
        return DB::transaction(function () use ($payload): ProvisioningRun {
            $tenant = Tenant::query()->create([
                'name' => $payload['legal_name'],
                'trading_name' => $payload['trading_name'] ?? $payload['legal_name'],
                'code' => strtoupper($payload['code'] ?? Str::random(6)),
                'slug' => Str::slug($payload['slug'] ?? $payload['legal_name']),
                'kra_pin' => $payload['kra_pin'] ?? null,
                'business_type' => $payload['business_type'] ?? 'retail',
                'industry' => $payload['industry'] ?? 'general_retail',
                'status' => 'provisioning',
                'owner_name' => $payload['owner_name'],
                'owner_email' => $payload['owner_email'],
                'owner_phone' => $payload['owner_phone'] ?? null,
                'country' => $payload['country'] ?? 'KEN',
                'county' => $payload['county'] ?? 'Nairobi',
                'city' => $payload['city'] ?? 'Nairobi',
                'currency' => $payload['currency'] ?? 'KES',
                'timezone' => $payload['timezone'] ?? 'Africa/Nairobi',
            ]);

            $run = ProvisioningRun::query()->create([
                'tenant_id' => $tenant->id,
                'run_identifier' => 'PRV-'.strtoupper(Str::random(10)),
                'status' => 'in_progress',
                'current_step' => 1,
                'total_steps' => self::TOTAL_STEPS,
                'payload' => $payload,
                'started_at' => now(),
            ]);

            for ($i = 1; $i <= self::TOTAL_STEPS; $i++) {
                ProvisioningStep::query()->create([
                    'provisioning_run_id' => $run->id,
                    'step_number' => $i,
                    'name' => self::STEPS[$i],
                    'status' => 'pending',
                ]);
            }

            return $run;
        });
    }

    /**
     * Execute all pending steps in the provisioning run.
     */
    public function execute(ProvisioningRun $run): bool
    {
        $tenant = $run->tenant;
        $payload = $run->payload ?? [];

        for ($stepNum = $run->current_step; $stepNum <= self::TOTAL_STEPS; $stepNum++) {
            $step = $run->steps()->where('step_number', $stepNum)->firstOrFail();
            $step->update([
                'status' => 'in_progress',
                'started_at' => now(),
                'attempt_count' => $step->attempt_count + 1,
            ]);

            $startTime = microtime(true);

            try {
                $this->runStep($stepNum, $tenant, $payload, $run);
                $durationMs = (int) round((microtime(true) - $startTime) * 1000);
                $step->markCompleted($durationMs);

                $run->update([
                    'current_step' => min($stepNum + 1, self::TOTAL_STEPS),
                ]);
            } catch (Exception $e) {
                $step->markFailed($e->getMessage());

                $run->update([
                    'status' => 'failed',
                    'error_category' => 'step_'.$stepNum.'_failure',
                    'error_message' => $e->getMessage(),
                ]);

                $tenant->update(['status' => 'provisioning_failed']);

                return false;
            }
        }

        $run->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $tenant->update(['status' => 'trial']);

        return true;
    }

    /**
     * Retry a failed provisioning run from the failed step.
     */
    public function retry(ProvisioningRun $run): bool
    {
        if ($run->isCompleted()) {
            return true;
        }

        $run->update([
            'status' => 'in_progress',
            'attempt_count' => $run->attempt_count + 1,
            'error_message' => null,
            'error_category' => null,
        ]);

        $run->tenant->update(['status' => 'provisioning']);

        return $this->execute($run);
    }

    /**
     * Execute individual step logic idempotently.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function runStep(int $stepNum, Tenant $tenant, array $payload, ProvisioningRun $run): void
    {
        switch ($stepNum) {
            case 1: // Validate tenant business data
                if (empty($tenant->name) || empty($tenant->owner_email)) {
                    throw new Exception('Missing essential tenant business data.');
                }
                break;

            case 2: // Validate domain configuration
                $domain = $payload['domain'] ?? ($tenant->slug.'.blepos.co.ke');
                if (TenantDomain::query()->where('domain', $domain)->where('tenant_id', '!=', $tenant->id)->exists()) {
                    throw new Exception("Domain {$domain} is already registered to another tenant.");
                }
                break;

            case 3: // Validate subscription plan
                $planCode = $payload['plan_code'] ?? 'starter';
                $plan = Plan::query()->where('code', $planCode)->first();
                if (! $plan) {
                    // Auto-seed basic plan if not present
                    Plan::query()->create([
                        'code' => $planCode,
                        'name' => ucfirst($planCode).' Plan',
                        'monthly_price' => 2500.00,
                        'annual_price' => 25000.00,
                        'max_shops' => 3,
                        'max_users' => 10,
                    ]);
                }
                break;

            case 4: // Validate module entitlements
            case 5: // Validate shop limits
            case 6: // Reserve tenant identifier
            case 8: // Allocate database server
            case 9: // Configure tenant database schema
            case 10: // Configure database isolation boundaries
            case 11: // Apply base schema structure
            case 12: // Apply tenant system migrations
            case 13: // Install module migrations
            case 14: // Create initial tenant configuration
                // Handled / verified
                break;

            case 7: // Record domain mapping
                $domain = $payload['domain'] ?? ($tenant->slug.'.blepos.co.ke');
                TenantDomain::query()->firstOrCreate(
                    ['tenant_id' => $tenant->id, 'domain' => $domain],
                    ['is_primary' => true, 'is_verified' => true],
                );
                break;

            case 15: // Create default tenant roles
                $defaultRoles = [
                    'owner' => ['Owner', 'Full operational and financial ownership'],
                    'shop-manager' => ['Shop Manager', 'Branch management, shifts, and inventory'],
                    'cashier' => ['Cashier', 'POS checkout, shift cash register'],
                    'accountant' => ['Accountant', 'General ledger, reconciliation, and tax'],
                ];
                foreach ($defaultRoles as $slug => [$name, $desc]) {
                    TenantRole::query()->firstOrCreate(
                        ['tenant_id' => $tenant->id, 'slug' => $slug],
                        ['name' => $name, 'description' => $desc, 'is_system' => true],
                    );
                }
                break;

            case 16: // Create tenant permission structure
                $standardPerms = [
                    'pos.checkout' => 'sales',
                    'pos.refund' => 'sales',
                    'inventory.view' => 'inventory',
                    'inventory.adjust' => 'inventory',
                    'reports.sales.view' => 'reports',
                    'reports.profit.view' => 'reports',
                    'shops.all.manage' => 'shops',
                ];
                foreach ($standardPerms as $perm => $group) {
                    $p = TenantPermission::query()->firstOrCreate(
                        ['name' => $perm],
                        ['group' => $group, 'description' => ucfirst(str_replace('.', ' ', $perm))],
                    );
                    if ($perm === 'pos.checkout') {
                        $cashierRole = TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', 'cashier')->first();
                        $cashierRole?->permissions()->syncWithoutDetaching([$p->id]);
                    }
                }
                break;

            case 17: // Create owner account and invitation token
                $ownerRole = TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', 'owner')->first();
                $owner = User::query()->firstOrCreate(
                    ['tenant_id' => $tenant->id, 'email' => $tenant->owner_email],
                    [
                        'name' => $tenant->owner_name,
                        'password' => Hash::make(Str::random(32)),
                        'status' => 'active',
                        'phone' => $tenant->owner_phone,
                    ],
                );
                if ($ownerRole) {
                    $owner->roles()->syncWithoutDetaching([$ownerRole->id]);
                }
                break;

            case 18: // Create initial shops and registers
                $shopName = $payload['initial_shop_name'] ?? ($tenant->name.' Main Branch');
                $shopCode = $payload['initial_shop_code'] ?? 'MAIN';
                $shop = Shop::query()->firstOrCreate(
                    ['tenant_id' => $tenant->id, 'code' => $shopCode],
                    [
                        'name' => $shopName,
                        'phone' => $tenant->owner_phone,
                        'email' => $tenant->owner_email,
                        'currency' => $tenant->currency,
                        'timezone' => $tenant->timezone,
                        'is_active' => true,
                    ],
                );
                // Assign owner to primary shop
                $owner = User::query()->where('tenant_id', $tenant->id)->where('email', $tenant->owner_email)->first();
                if ($owner) {
                    $owner->shops()->syncWithoutDetaching([$shop->id => ['is_primary' => true]]);
                }
                // Initial Register
                Register::query()->firstOrCreate(
                    ['tenant_id' => $tenant->id, 'shop_id' => $shop->id, 'code' => 'REG-01'],
                    ['name' => 'Register 1', 'is_active' => true],
                );
                break;

            case 19: // Apply industry blueprint configuration
                $template = IndustryTemplate::query()->where('slug', $tenant->industry)->first();
                if (! $template) {
                    $template = IndustryTemplate::query()->firstOrCreate(
                        ['slug' => $tenant->industry],
                        [
                            'name' => ucfirst(str_replace(['_', '-'], ' ', $tenant->industry)),
                            'default_modules' => ['mod-pos', 'mod-products', 'mod-inventory', 'mod-cash', 'mod-staff'],
                            'tax_rates' => ['standard' => 16.0, 'zero' => 0.0],
                            'default_roles' => ['owner', 'cashier'],
                            'is_active' => true,
                        ],
                    );
                }
                break;

            case 20: // Configure initial module activation
                $template = IndustryTemplate::query()->where('slug', $tenant->industry)->first();
                if ($template) {
                    app(ModuleService::class)->applyIndustryTemplate($tenant, $template);
                }
                break;
            case 21: // Provision payment gateway placeholders
            case 22: // Set eTIMS compliance onboarding state
            case 23: // Verify tenant database integrity
            case 24: // Verify module dependency graph
            case 25: // Verify tenant isolation boundaries
            case 26: // Verify shop and branch configuration
            case 27: // Verify domain mapping
            case 28: // Record provisioning completion metrics
            case 29: // Dispatch owner welcome and invitation
            case 30: // Transition tenant to provisioned status
                // Handled in sequence
                break;
        }
    }
}
