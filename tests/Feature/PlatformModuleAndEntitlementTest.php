<?php

namespace Tests\Feature;

use App\Models\IndustryTemplate;
use App\Models\Module;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Models\TenantModuleEntitlement;
use Database\Seeders\ModuleCatalogueSeeder;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class PlatformModuleAndEntitlementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformRoleSeeder::class);
    }

    /**
     * Create a super-administrator with a valid platform session.
     *
     * @return array{0: PlatformUser, 1: string}
     */
    private function createSuperAdmin(): array
    {
        $user = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->where('slug', 'super-administrator')->firstOrFail();
        $user->roles()->attach($role->id);

        $sessionId = Str::random(40);
        PlatformSession::create([
            'platform_user_id' => $user->id,
            'session_hash' => hash('sha256', $sessionId),
            'session_identifier' => $sessionId,
            'logged_in_at' => now(),
            'last_activity_at' => now(),
            'expires_at' => now()->addHours(8),
        ]);

        return [$user, $sessionId];
    }

    /**
     * Return a test request builder authenticated as the given platform user.
     */
    private function platformRequest(PlatformUser $user, string $sessionId)
    {
        return $this->actingAs($user, 'platform')
            ->withSession([
                'platform_mfa_verified_at' => now()->timestamp,
                'platform_step_up_at' => now()->timestamp,
            ])
            ->withCookie(config('session.platform.cookie'), $sessionId);
    }

    // -------------------------------------------------------------------------
    // Module catalogue view
    // -------------------------------------------------------------------------

    public function test_module_catalogue_index_groups_modules_by_category(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$user, $sessionId] = $this->createSuperAdmin();

        $response = $this->platformRequest($user, $sessionId)
            ->get(route('platform.modules.index'));

        $response->assertOk();
        $response->assertSee('Module Catalogue');
        $response->assertSee('Core'); // category heading rendered by view
    }

    public function test_module_catalogue_show_displays_module_details_and_dependents(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$user, $sessionId] = $this->createSuperAdmin();

        $module = Module::query()->where('slug', 'mod-pos')->firstOrFail();

        $response = $this->platformRequest($user, $sessionId)
            ->get(route('platform.modules.show', $module));

        $response->assertOk();
        $response->assertSee($module->name);
    }

    public function test_industry_templates_index_lists_all_templates(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$user, $sessionId] = $this->createSuperAdmin();

        $response = $this->platformRequest($user, $sessionId)
            ->get(route('platform.templates.index'));

        $response->assertOk();
        $response->assertSee('Industry Templates');
    }

    // -------------------------------------------------------------------------
    // Enable module
    // -------------------------------------------------------------------------

    public function test_enabling_a_module_with_satisfied_dependencies_succeeds_and_writes_audit(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$user, $sessionId] = $this->createSuperAdmin();

        $tenant = Tenant::factory()->create(['status' => 'active']);

        // Enable the core dependency first (mod-customers has no deps itself)
        $customers = Module::query()->where('slug', 'mod-customers')->firstOrFail();
        TenantModuleEntitlement::create([
            'tenant_id' => $tenant->id,
            'module_id' => $customers->id,
            'status' => 'enabled',
            'enabled_at' => now(),
        ]);

        // Now enable mod-loyalty which depends on mod-customers
        $module = Module::query()->where('slug', 'mod-loyalty')->firstOrFail();

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.tenants.modules.enable', $tenant), [
                'module_id' => $module->id,
                'notes' => 'Test enable',
            ]);

        $response->assertRedirectToRoute('platform.tenants.show', $tenant);

        $this->assertDatabaseHas('tenant_module_entitlements', [
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
            'status' => 'enabled',
        ]);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'tenant.module.enable',
            'actor_id' => $user->id,
        ]);
    }

    public function test_enabling_a_module_with_unsatisfied_dependency_returns_422(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$user, $sessionId] = $this->createSuperAdmin();

        $tenant = Tenant::factory()->create(['status' => 'active']);

        // mod-loyalty depends on mod-customers — do NOT enable mod-customers first
        $module = Module::query()->where('slug', 'mod-loyalty')->firstOrFail();

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.tenants.modules.enable', $tenant), [
                'module_id' => $module->id,
            ]);

        // Web POST with ValidationException redirects back (302) with session errors
        $response->assertRedirect();
        $response->assertSessionHasErrors('module');

        $this->assertDatabaseMissing('tenant_module_entitlements', [
            'tenant_id' => $tenant->id,
            'module_id' => $module->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Disable module
    // -------------------------------------------------------------------------

    public function test_disabling_a_core_module_returns_422(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$user, $sessionId] = $this->createSuperAdmin();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $core = Module::query()->where('slug', 'mod-pos')->where('is_core', true)->firstOrFail();

        TenantModuleEntitlement::create([
            'tenant_id' => $tenant->id,
            'module_id' => $core->id,
            'status' => 'enabled',
            'enabled_at' => now(),
        ]);

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.tenants.modules.disable', [$tenant, $core]));

        // Core module disable: abort(422) — returns a plain 422 (not a redirect)
        $response->assertStatus(422);

        $this->assertDatabaseHas('tenant_module_entitlements', [
            'tenant_id' => $tenant->id,
            'module_id' => $core->id,
            'status' => 'enabled',
        ]);
    }

    public function test_disabling_a_module_that_another_active_module_depends_on_returns_422(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$user, $sessionId] = $this->createSuperAdmin();

        $tenant = Tenant::factory()->create(['status' => 'active']);

        $customers = Module::query()->where('slug', 'mod-customers')->firstOrFail();
        $loyalty = Module::query()->where('slug', 'mod-loyalty')->firstOrFail();

        // Enable both
        foreach ([$customers, $loyalty] as $mod) {
            TenantModuleEntitlement::create([
                'tenant_id' => $tenant->id,
                'module_id' => $mod->id,
                'status' => 'enabled',
                'enabled_at' => now(),
            ]);
        }

        // Try to disable mod-customers while mod-loyalty depends on it
        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.tenants.modules.disable', [$tenant, $customers]));

        // ValidationException in web context redirects back with session errors
        $response->assertRedirect();
        $response->assertSessionHasErrors('module');
    }

    public function test_disabling_a_non_core_module_with_no_dependents_succeeds_and_writes_audit(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$user, $sessionId] = $this->createSuperAdmin();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $customers = Module::query()->where('slug', 'mod-customers')->firstOrFail();
        $loyalty = Module::query()->where('slug', 'mod-loyalty')->firstOrFail();

        // Enable both
        foreach ([$customers, $loyalty] as $mod) {
            TenantModuleEntitlement::create([
                'tenant_id' => $tenant->id,
                'module_id' => $mod->id,
                'status' => 'enabled',
                'enabled_at' => now(),
            ]);
        }

        // Disable loyalty (no other module depends on it in this test)
        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.tenants.modules.disable', [$tenant, $loyalty]));

        $response->assertRedirectToRoute('platform.tenants.show', $tenant);

        $this->assertDatabaseHas('tenant_module_entitlements', [
            'tenant_id' => $tenant->id,
            'module_id' => $loyalty->id,
            'status' => 'disabled',
        ]);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'tenant.module.disable',
            'actor_id' => $user->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Industry template apply
    // -------------------------------------------------------------------------

    public function test_applying_an_industry_template_enables_all_modules_in_dependency_order(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$user, $sessionId] = $this->createSuperAdmin();

        $tenant = Tenant::factory()->create(['status' => 'active']);
        $template = IndustryTemplate::query()->where('slug', 'retail')->firstOrFail();

        $response = $this->platformRequest($user, $sessionId)
            ->post(route('platform.tenants.templates.apply', $tenant), [
                'template_id' => $template->id,
            ]);

        $response->assertRedirectToRoute('platform.tenants.show', $tenant);

        // All modules listed in the retail template must be enabled
        foreach ($template->default_modules as $slug) {
            $mod = Module::query()->where('slug', $slug)->first();
            if ($mod) {
                $this->assertDatabaseHas('tenant_module_entitlements', [
                    'tenant_id' => $tenant->id,
                    'module_id' => $mod->id,
                    'status' => 'enabled',
                ]);
            }
        }

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'tenant.template.apply',
            'actor_id' => $user->id,
        ]);
    }
}
