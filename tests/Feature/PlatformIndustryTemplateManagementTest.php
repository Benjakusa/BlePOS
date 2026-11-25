<?php

namespace Tests\Feature;

use App\Http\Controllers\Platform\PlatformIndustryTemplateController;
use App\Models\IndustryTemplate;
use App\Models\Module;
use App\Models\PlatformRole;
use App\Models\PlatformSession;
use App\Models\PlatformUser;
use App\Models\Tenant;
use App\Models\TenantModuleEntitlement;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\ModuleCatalogueSeeder;
use Database\Seeders\PlatformRoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class PlatformIndustryTemplateManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(PlatformRoleSeeder::class);
    }

    /**
     * Helper to create an authenticated platform user with given role.
     *
     * @return array{0: PlatformUser, 1: string}
     */
    private function createPlatformUserWithRole(string $roleSlug): array
    {
        $user = PlatformUser::factory()->create(['mfa_confirmed_at' => now()]);
        $role = PlatformRole::query()->where('slug', $roleSlug)->firstOrFail();
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

    private function platformRequest(PlatformUser $user, string $sessionId, bool $withStepUp = true)
    {
        // Flush the session first to prevent stale platform_step_up_at from leaking
        // between requests in the same test (withSession() merges, not replaces).
        $this->flushSession();

        $session = [
            'platform_mfa_verified_at' => now()->timestamp,
        ];

        if ($withStepUp) {
            $session['platform_step_up_at'] = now()->timestamp;
        }

        return $this->actingAs($user, 'platform')
            ->withSession($session)
            ->withCookie(config('session.platform.cookie'), $sessionId);
    }

    public function test_seeder_populates_modules_and_templates_idempotently(): void
    {
        $this->seed(DatabaseSeeder::class);

        $moduleCount = Module::count();
        $templateCount = IndustryTemplate::count();

        $this->assertGreaterThanOrEqual(25, $moduleCount);
        $this->assertGreaterThanOrEqual(7, $templateCount);

        // Run second time to ensure zero duplicates (idempotency check)
        $this->seed(DatabaseSeeder::class);

        $this->assertSame($moduleCount, Module::count(), 'Seeder is not idempotent for modules');
        $this->assertSame($templateCount, IndustryTemplate::count(), 'Seeder is not idempotent for templates');

        // Assert all new industry module slugs exist
        foreach (['gym', 'gym.classes', 'printing', 'printing.quotes', 'agrovet', 'agrovet.credit', 'garage', 'garage.estimates', 'fleet', 'fleet.inspections', 'parcel-delivery'] as $slug) {
            $this->assertTrue(Module::where('slug', $slug)->exists(), "Module slug '{$slug}' missing from seeder");
        }

        // Assert all new industry template slugs exist
        foreach (['gym-fitness', 'printing-branding', 'agrovet', 'auto-garage', 'car-hire-fleet', 'parcel-delivery'] as $slug) {
            $this->assertTrue(IndustryTemplate::where('slug', $slug)->exists(), "Template slug '{$slug}' missing from seeder");
        }

        $parcelTemplate = IndustryTemplate::where('slug', 'parcel-delivery')->firstOrFail();
        foreach (['parcel-delivery', 'mod-delivery', 'mod-multishop', 'mod-transfers', 'mod-customers'] as $slug) {
            $this->assertContains($slug, $parcelTemplate->default_modules);
        }
    }

    public function test_index_lists_templates_and_displays_empty_state_when_none_exist(): void
    {
        [$superAdmin, $sessionId] = $this->createPlatformUserWithRole('super-administrator');

        // When no templates exist
        $response = $this->platformRequest($superAdmin, $sessionId)
            ->get(route('platform.templates.index'));

        $response->assertOk();
        $response->assertSee('No industry templates defined');
        $response->assertSee('Create template');

        // Now seed catalogue
        $this->seed(ModuleCatalogueSeeder::class);

        $response = $this->platformRequest($superAdmin, $sessionId)
            ->get(route('platform.templates.index'));

        $response->assertOk();
        $response->assertSee('Retail Supermarket & General Store');
        $response->assertSee('Pharmacy & Healthcare');
        $response->assertSee('Restaurant, Bar & Hospitality');

        // Test search
        $searchResponse = $this->platformRequest($superAdmin, $sessionId)
            ->get(route('platform.templates.index', ['q' => 'pharmacy']));

        $searchResponse->assertOk();
        $searchResponse->assertSee('Pharmacy & Healthcare');
        $searchResponse->assertDontSee('Restaurant, Bar & Hospitality');
    }

    public function test_create_persists_template_with_modules_and_writes_audit(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$superAdmin, $sessionId] = $this->createPlatformUserWithRole('super-administrator');

        $response = $this->platformRequest($superAdmin, $sessionId)
            ->post(route('platform.templates.store'), [
                'name' => 'Boutique Fashion & Apparel',
                'slug' => 'fashion-boutique',
                'description' => 'Specialized apparel retail with variant sizes and colors.',
                'is_active' => '1',
                'default_modules' => ['mod-pos', 'mod-products', 'mod-inventory', 'mod-customers'],
            ]);

        $template = IndustryTemplate::where('slug', 'fashion-boutique')->firstOrFail();

        $response->assertRedirectToRoute('platform.templates.show', $template);

        $this->assertDatabaseHas('industry_templates', [
            'name' => 'Boutique Fashion & Apparel',
            'slug' => 'fashion-boutique',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.templates.create',
            'actor_id' => $superAdmin->id,
            'target_id' => (string) $template->id,
        ]);
    }

    public function test_create_fails_for_duplicate_slug_unknown_module_or_missing_dependencies(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$superAdmin, $sessionId] = $this->createPlatformUserWithRole('super-administrator');

        // 1. Missing dependencies without auto-add (mod-procurement needs mod-suppliers and mod-products)
        $response = $this->platformRequest($superAdmin, $sessionId)
            ->post(route('platform.templates.store'), [
                'name' => 'Test Broken Template',
                'slug' => 'test-broken',
                'default_modules' => ['mod-procurement'], // missing mod-suppliers
                'auto_add_dependencies' => '0',
            ]);

        $response->assertSessionHasErrors('default_modules');
        $this->assertDatabaseMissing('industry_templates', ['slug' => 'test-broken']);

        // 2. With auto_add_dependencies, it succeeds and includes dependencies
        $autoResponse = $this->platformRequest($superAdmin, $sessionId)
            ->post(route('platform.templates.store'), [
                'name' => 'Test Auto Resolved',
                'slug' => 'test-auto-resolved',
                'default_modules' => ['mod-procurement'],
                'auto_add_dependencies' => '1',
            ]);

        $autoTemplate = IndustryTemplate::where('slug', 'test-auto-resolved')->firstOrFail();
        $autoResponse->assertRedirectToRoute('platform.templates.show', $autoTemplate);

        // Core modules and mod-suppliers must have been added
        $this->assertContains('mod-suppliers', $autoTemplate->default_modules);
        $this->assertContains('mod-pos', $autoTemplate->default_modules);
        $this->assertContains('mod-products', $autoTemplate->default_modules);

        // 3. Duplicate slug fails
        $dupResponse = $this->platformRequest($superAdmin, $sessionId)
            ->post(route('platform.templates.store'), [
                'name' => 'Test Auto Resolved Duplicate',
                'slug' => 'test-auto-resolved',
                'default_modules' => ['mod-pos', 'mod-products'],
            ]);

        $dupResponse->assertSessionHasErrors('slug');
    }

    public function test_edit_updates_template_and_core_modules_cannot_be_removed(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$superAdmin, $sessionId] = $this->createPlatformUserWithRole('super-administrator');

        $template = IndustryTemplate::where('slug', 'retail')->firstOrFail();

        // Submit without explicit core modules in request
        $response = $this->platformRequest($superAdmin, $sessionId)
            ->put(route('platform.templates.update', $template), [
                'name' => 'Retail Supermarket Updated',
                'slug' => 'retail',
                'description' => 'Updated description for supermarket chain.',
                'is_active' => '1',
                'default_modules' => ['mod-customers', 'mod-mpesa'],
            ]);

        $response->assertRedirectToRoute('platform.templates.show', $template);

        $template->refresh();
        $this->assertSame('Retail Supermarket Updated', $template->name);

        // Core modules (mod-pos, mod-products, mod-inventory, mod-cash, mod-staff) must remain present
        $this->assertContains('mod-pos', $template->default_modules);
        $this->assertContains('mod-products', $template->default_modules);
        $this->assertContains('mod-inventory', $template->default_modules);
        $this->assertContains('mod-customers', $template->default_modules);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.templates.update',
            'actor_id' => $superAdmin->id,
            'target_id' => (string) $template->id,
        ]);
    }

    public function test_delete_is_blocked_when_tenant_uses_template_and_deactivate_works(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$superAdmin, $sessionId] = $this->createPlatformUserWithRole('super-administrator');

        $template = IndustryTemplate::where('slug', 'pharmacy')->firstOrFail();

        // Create tenant assigned to this template
        $tenant = Tenant::factory()->create([
            'industry' => 'pharmacy',
            'status' => 'active',
        ]);

        // Attempt deletion -> must be blocked
        $deleteResponse = $this->platformRequest($superAdmin, $sessionId)
            ->delete(route('platform.templates.destroy', $template));

        $deleteResponse->assertRedirectToRoute('platform.templates.show', $template);
        $deleteResponse->assertSessionHas('error');
        $this->assertDatabaseHas('industry_templates', ['id' => $template->id]);

        // Deactivation works instead
        $toggleResponse = $this->platformRequest($superAdmin, $sessionId)
            ->post(route('platform.templates.toggle-active', $template));

        $toggleResponse->assertRedirect();
        $template->refresh();
        $this->assertFalse($template->is_active);

        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.templates.status',
            'actor_id' => $superAdmin->id,
            'target_id' => (string) $template->id,
        ]);

        // Clean up tenant to allow delete
        $tenant->delete();

        $allowedDelete = $this->platformRequest($superAdmin, $sessionId)
            ->delete(route('platform.templates.destroy', $template));

        $allowedDelete->assertRedirectToRoute('platform.templates.index');
        $this->assertDatabaseMissing('industry_templates', ['id' => $template->id]);
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'platform.templates.delete',
            'actor_id' => $superAdmin->id,
        ]);
    }

    public function test_apply_enables_modules_in_dependency_order_skips_already_enabled_and_never_disables(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$superAdmin, $sessionId] = $this->createPlatformUserWithRole('super-administrator');

        $template = IndustryTemplate::where('slug', 'pharmacy')->firstOrFail();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        // Pre-enable a module that is in the template (mod-pos) and one that is NOT in the template (mod-loyalty with mod-customers)
        $pos = Module::where('slug', 'mod-pos')->firstOrFail();
        TenantModuleEntitlement::create([
            'tenant_id' => $tenant->id,
            'module_id' => $pos->id,
            'status' => 'enabled',
            'enabled_at' => now(),
        ]);

        $customers = Module::where('slug', 'mod-customers')->firstOrFail();
        TenantModuleEntitlement::create([
            'tenant_id' => $tenant->id,
            'module_id' => $customers->id,
            'status' => 'enabled',
            'enabled_at' => now(),
        ]);

        // Apply template via route
        $response = $this->platformRequest($superAdmin, $sessionId)
            ->post(route('platform.templates.apply', $template), [
                'tenant_id' => $tenant->id,
            ]);

        $response->assertRedirectToRoute('platform.templates.show', $template);

        // Check that pharmacy modules are now enabled
        $pharmacyMod = Module::where('slug', 'mod-pharmacy')->firstOrFail();
        $this->assertDatabaseHas('tenant_module_entitlements', [
            'tenant_id' => $tenant->id,
            'module_id' => $pharmacyMod->id,
            'status' => 'enabled',
        ]);

        // Check that pre-existing module mod-customers was NOT disabled
        $this->assertDatabaseHas('tenant_module_entitlements', [
            'tenant_id' => $tenant->id,
            'module_id' => $customers->id,
            'status' => 'enabled',
        ]);

        // Check audit log
        $this->assertDatabaseHas('platform_audit_log', [
            'action' => 'tenant.template.apply',
            'actor_id' => $superAdmin->id,
            'target_id' => (string) $tenant->id,
        ]);
    }

    public function test_preview_endpoint_returns_json_module_diff(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$superAdmin, $sessionId] = $this->createPlatformUserWithRole('super-administrator');

        $template = IndustryTemplate::where('slug', 'retail')->firstOrFail();
        $tenant = Tenant::factory()->create(['status' => 'active']);

        // Pre-enable mod-pos
        $pos = Module::where('slug', 'mod-pos')->firstOrFail();
        TenantModuleEntitlement::create([
            'tenant_id' => $tenant->id,
            'module_id' => $pos->id,
            'status' => 'enabled',
            'enabled_at' => now(),
        ]);

        $response = $this->platformRequest($superAdmin, $sessionId)
            ->get(route('platform.templates.preview', [$template, $tenant]));

        $response->assertOk();
        $response->assertJsonStructure([
            'tenant',
            'template',
            'already_enabled',
            'will_enable',
            'total_template_modules',
        ]);

        $data = $response->json();
        $this->assertContains('mod-pos', $data['already_enabled']);
        $this->assertContains('mod-inventory', $data['will_enable']);
    }

    public function test_permission_matrix_enforces_view_vs_manage_and_step_up(): void
    {
        $this->seed(ModuleCatalogueSeeder::class);
        [$supportUser, $supportSession] = $this->createPlatformUserWithRole('support-agent');
        [$auditorUser, $auditorSession] = $this->createPlatformUserWithRole('platform-auditor');
        [$superAdmin, $superAdminSession] = $this->createPlatformUserWithRole('super-administrator');

        $template = IndustryTemplate::where('slug', 'retail')->firstOrFail();

        // 1. Support agent cannot view templates (support-agent doesn't have platform.modules.view)
        $this->platformRequest($supportUser, $supportSession)
            ->get(route('platform.templates.index'))
            ->assertForbidden();

        // 2. Support agent cannot mutate templates
        $this->platformRequest($supportUser, $supportSession)
            ->post(route('platform.templates.store'), [
                'name' => 'Should Fail',
                'slug' => 'should-fail',
                'default_modules' => ['mod-pos'],
            ])
            ->assertForbidden();

        // 3. Platform auditor has no platform.modules.manage permission
        $this->platformRequest($auditorUser, $auditorSession)
            ->delete(route('platform.templates.destroy', $template))
            ->assertForbidden();

        // 4. Missing step-up authentication redirects/rejects even for super-administrator
        $this->platformRequest($superAdmin, $superAdminSession, withStepUp: false)
            ->delete(route('platform.templates.destroy', $template))
            ->assertRedirect(route('platform.step-up'));

        // 5. Test direct controller authorizer check even when route middleware is bypassed
        $controller = app(PlatformIndustryTemplateController::class);
        $directRequest = Request::create(route('platform.templates.destroy', $template), 'DELETE');
        $directRequest->setUserResolver(fn () => $auditorUser);

        try {
            $controller->destroy($directRequest, $template);
            $this->fail('Expected HttpException 403 when authorizer checks auditor without platform.modules.manage');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // Ensure database remains unchanged
        $this->assertDatabaseHas('industry_templates', ['id' => $template->id]);
        $this->assertDatabaseMissing('industry_templates', ['slug' => 'should-fail']);
    }
}
