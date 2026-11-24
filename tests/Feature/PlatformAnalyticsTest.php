<?php

namespace Tests\Feature;

use App\Models\Plan;
use App\Models\PlatformInvoice;
use App\Models\PlatformPayment;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Models\Subscription;
use App\Models\Tenant;
use Database\Seeders\ModuleCatalogueSeeder;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformRoleSeeder::class);
        $this->seed(ModuleCatalogueSeeder::class);
    }

    private function createAdmin(string $roleSlug = 'platform-operations-administrator'): array
    {
        $user = PlatformUser::factory()->create([
            'status' => 'active',
            'mfa_confirmed_at' => now(),
        ]);

        $role = PlatformRole::where('slug', $roleSlug)->firstOrFail();
        $user->roles()->attach($role->id);

        $sessionId = Str::random(40);
        PlatformSession::create([
            'platform_user_id' => $user->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'device_type' => 'desktop',
            'browser' => 'PHPUnit',
            'platform' => 'Linux',
            'last_activity_at' => now(),
            'logged_in_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        return [$user, $sessionId];
    }

    private function platformRequest(PlatformUser $user, string $sessionId)
    {
        return $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId);
    }

    public function test_authorized_platform_admin_can_view_analytics_overview(): void
    {
        [$admin, $sessionId] = $this->createAdmin();

        $plan = Plan::create([
            'code' => 'GROWTH',
            'name' => 'Growth Retail Tier',
            'monthly_price' => 4500.00,
            'annual_price' => 45000.00,
            'is_active' => true,
        ]);

        $tenant = Tenant::factory()->create([
            'name' => 'Nairobi Mega Supermarket',
            'status' => 'active',
            'industry' => 'retail',
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now()->subMonth(),
            'current_period_starts_at' => now()->subDays(5),
            'current_period_ends_at' => now()->addDays(25),
        ]);

        $response = $this->platformRequest($admin, $sessionId)
            ->get(route('platform.analytics.index'));

        $response->assertOk();
        $response->assertSee('Platform Analytics & Reports', false);
        $response->assertSee('Monthly Recurring Revenue');
        $response->assertSee('KES 4,500.00');
        $response->assertSee('Growth Retail Tier');
        $response->assertSee('Module Catalogue Adoption');
        $response->assertSee('Industry Blueprint Breakdown');
    }

    public function test_period_filter_returns_valid_metrics(): void
    {
        [$admin, $sessionId] = $this->createAdmin();

        foreach (['7d', '30d', '90d', '12m'] as $period) {
            $response = $this->platformRequest($admin, $sessionId)
                ->get(route('platform.analytics.index', ['period' => $period]));

            $response->assertOk();
            $response->assertSee("value=\"{$period}\" selected", false);
        }
    }

    public function test_export_revenue_report_generates_formula_safe_csv_and_audits(): void
    {
        [$admin, $sessionId] = $this->createAdmin();

        $plan = Plan::create([
            'code' => 'ENTERPRISE',
            'name' => 'Enterprise Tier',
            'monthly_price' => 12000.00,
            'annual_price' => 120000.00,
            'is_active' => true,
        ]);

        $tenant = Tenant::factory()->create(['status' => 'active']);

        $invoice = PlatformInvoice::create([
            'tenant_id' => $tenant->id,
            'invoice_number' => 'INV-2026-TEST-001',
            'status' => 'issued',
            'subtotal' => 10344.83,
            'tax_rate' => 16.00,
            'tax_amount' => 1655.17,
            'total_amount' => 12000.00,
            'amount_paid' => 12000.00,
            'amount_due' => 0.00,
            'currency' => 'KES',
            'period_start' => now()->startOfMonth(),
            'period_end' => now()->endOfMonth(),
            'due_date' => now()->addDays(7),
            'issued_at' => now(),
        ]);

        PlatformPayment::create([
            'platform_invoice_id' => $invoice->id,
            'tenant_id' => $tenant->id,
            'payment_reference' => 'PAY-2026-TEST-001',
            'payment_method' => 'mpesa',
            'amount' => 12000.00,
            'currency' => 'KES',
            'status' => 'completed',
            'paid_at' => now(),
        ]);

        $response = $this->platformRequest($admin, $sessionId)
            ->post(route('platform.analytics.export'), [
                'report_type' => 'revenue',
            ]);

        $response->assertOk();
        $this->assertStringStartsWith('text/csv', (string) $response->headers->get('Content-Type'));
        $content = $response->getContent();

        $this->assertStringContainsString('Monthly Recurring Revenue (KES)', $content);
        $this->assertStringContainsString('Total Invoiced (KES)', $content);
        $this->assertStringContainsString('12000.00', $content);

        // Verify audit record
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.analytics.report_exported',
            'target_type' => 'PlatformAnalytics',
            'target_id' => 'revenue',
        ]);
    }

    public function test_export_tenants_report_protects_against_csv_formula_injection(): void
    {
        [$admin, $sessionId] = $this->createAdmin();

        Tenant::factory()->create([
            'name' => '=SUM(A1:A10)', // Formula injection attempt
            'slug' => 'formula-test',
            'status' => 'active',
        ]);

        $response = $this->platformRequest($admin, $sessionId)
            ->post(route('platform.analytics.export'), [
                'report_type' => 'tenants',
            ]);

        $response->assertOk();
        $content = $response->getContent();

        // Formula must be prepended with single quote to prevent execution in spreadsheet software
        $this->assertStringContainsString("'=SUM(A1:A10)", $content);
    }
}
