<?php

namespace Tests\Feature;

use App\Exceptions\TenantContextMissingException;
use App\Models\Tenant;
use App\Models\User;
use App\Services\PlatformAuditVerifier;
use App\Services\ProductionConfiguration;
use App\Services\TenantContext;
use App\Services\TenantFinancialAuditRecorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReleaseHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_scope_throws_without_context_and_allows_explicit_platform_scope(): void
    {
        TenantContext::clear();
        $this->expectException(TenantContextMissingException::class);
        User::query()->count();
    }

    public function test_allow_all_tenants_requires_explicit_call(): void
    {
        TenantContext::clear();
        TenantContext::allowAllTenants();
        $this->assertIsInt(User::query()->count());
    }

    public function test_production_check_reports_all_configuration_violations(): void
    {
        config([
            'app.env' => 'testing', 'app.debug' => true, 'app.key' => '',
            'session.secure' => false, 'platform.security.force_https' => false,
            'database.default' => 'sqlite', 'filesystems.default' => 'local',
            'platform.backup_disk' => 'local', 'queue.default' => 'sync',
            'cache.default' => 'array', 'trustedproxy.proxies' => [],
        ]);
        $violations = app(ProductionConfiguration::class)->violations();
        $this->assertCount(13, $violations);
        $this->artisan('platform:production-check')->assertExitCode(1);
    }

    public function test_production_check_rejects_platform_fallback_without_tenant_remittance_accounting(): void
    {
        config(['services.mpesa.allow_platform_fallback_for_tenant_sales' => true]);

        $this->assertContains(
            'MPESA_ALLOW_PLATFORM_FALLBACK_FOR_TENANT_SALES must remain false until tenant remittance accounting is available',
            app(ProductionConfiguration::class)->violations()
        );
    }

    public function test_tenant_financial_audit_chain_verifies_and_detects_tampering(): void
    {
        $tenant = Tenant::factory()->create();
        $recorder = app(TenantFinancialAuditRecorder::class);
        $first = $recorder->record($tenant->id, 10, 20, 'Sale', 30, 'sale.created', null, ['total' => '12.00']);
        $recorder->record($tenant->id, 10, 20, 'StockMovement', 31, 'stock.adjustment', ['quantity' => 3], ['quantity' => 2]);
        $this->assertTrue(app(PlatformAuditVerifier::class)->verify()['intact']);

        DB::table('tenant_financial_audit_entries')->where('id', $first->id)->update(['action' => 'tampered']);
        $result = app(PlatformAuditVerifier::class)->verify();
        $this->assertFalse($result['intact']);
        $this->assertSame($first->id, $result['broken_entry_id']);
    }
}
