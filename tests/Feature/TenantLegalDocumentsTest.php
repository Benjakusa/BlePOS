<?php

namespace Tests\Feature;

use App\Models\Shop;
use App\Models\Tenant;
use App\Models\User;
use App\Services\ShopContext;
use App\Services\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantLegalDocumentsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TenantContext::clear();
        ShopContext::clear();
    }

    protected function tearDown(): void
    {
        TenantContext::clear();
        ShopContext::clear();
        parent::tearDown();
    }

    private function createTenantAndUser(): array
    {
        $tenant = Tenant::factory()->create([
            'name' => 'Safari Retail Enterprises',
            'industry' => 'retail',
            'currency' => 'KES',
        ]);
        $shop = Shop::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Main Branch']);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);
        $user->shops()->attach($shop->id, ['is_primary' => true]);

        return [$tenant, $shop, $user];
    }

    public function test_unauthenticated_user_redirected_from_legal_hub(): void
    {
        $response = $this->get(route('tenant.legal.index'));
        $response->assertRedirect(route('tenant.login'));
    }

    public function test_authenticated_tenant_user_can_view_legal_hub(): void
    {
        [$tenant, $shop, $user] = $this->createTenantAndUser();

        $response = $this->actingAs($user, 'web')
            ->withSession(['active_shop_id' => $shop->id, 'tenant_id' => $tenant->id])
            ->get(route('tenant.legal.index'));

        $response->assertOk();
        $response->assertSee('Legal &amp; Regulatory Compliance', false);
        $response->assertSee('Data Protection Act, 2019');
        $response->assertSee('Terms of Service &amp; Master SaaS Agreement', false);
        $response->assertSee('Office of the Data Protection Commissioner');
    }

    public function test_authenticated_tenant_user_can_view_privacy_policy(): void
    {
        [$tenant, $shop, $user] = $this->createTenantAndUser();

        $response = $this->actingAs($user, 'web')
            ->withSession(['active_shop_id' => $shop->id, 'tenant_id' => $tenant->id])
            ->get(route('tenant.legal.privacy'));

        $response->assertOk();
        $response->assertSee('Data Protection Act, 2019 (Act No. 24 of 2019)', false);
        $response->assertSee('Data Controller');
        $response->assertSee('Data Processor');
        $response->assertSee('Principles of Data Protection (Section 19)');
        $response->assertSee('Lawful Bases for Processing (Section 30');
        $response->assertSee('72-Hour Personal Data Breach Protocols');
        $response->assertSee('dpo@blepos.co.ke');
        $response->assertSee('odpc.go.ke');
    }

    public function test_authenticated_tenant_user_can_view_terms_of_service(): void
    {
        [$tenant, $shop, $user] = $this->createTenantAndUser();

        $response = $this->actingAs($user, 'web')
            ->withSession(['active_shop_id' => $shop->id, 'tenant_id' => $tenant->id])
            ->get(route('tenant.legal.terms'));

        $response->assertOk();
        $response->assertSee('Master SaaS Agreement &amp; Terms of Service', false);
        $response->assertSee('Kenya Information and Communications Act (KICA), 1998');
        $response->assertSee('16% Value Added Tax (VAT)');
        $response->assertSee('Kenya Revenue Authority (KRA) eTIMS');
        $response->assertSee('Anti-Counterfeit Act, 2008');
        $response->assertSee('Chartered Institute of Arbitrators (Kenya Branch)');
        $response->assertSee('High Court of Kenya (Commercial and Tax Division)');
    }

    public function test_authenticated_tenant_user_can_view_data_processing_agreement(): void
    {
        [$tenant, $shop, $user] = $this->createTenantAndUser();

        $response = $this->actingAs($user, 'web')
            ->withSession(['active_shop_id' => $shop->id, 'tenant_id' => $tenant->id])
            ->get(route('tenant.legal.dpa'));

        $response->assertOk();
        $response->assertSee('Data Processing Agreement (DPA)');
        $response->assertSee('Processor Obligations');
        $response->assertSee('Controller Obligations');
    }

    public function test_tenant_dashboard_displays_legal_and_compliance_panel(): void
    {
        [$tenant, $shop, $user] = $this->createTenantAndUser();

        $response = $this->actingAs($user, 'web')
            ->withSession(['active_shop_id' => $shop->id, 'tenant_id' => $tenant->id])
            ->get(route('tenant.dashboard'));

        $response->assertOk();
        $response->assertSee('Legal &amp; Regulatory Compliance', false);
        $response->assertSee(route('tenant.legal.privacy'));
        $response->assertSee(route('tenant.legal.terms'));
        $response->assertSee(route('tenant.legal.index'));
    }
}
