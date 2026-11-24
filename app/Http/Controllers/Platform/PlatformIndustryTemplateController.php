<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\ApplyIndustryTemplateRequest;
use App\Http\Requests\Platform\StoreIndustryTemplateRequest;
use App\Http\Requests\Platform\UpdateIndustryTemplateRequest;
use App\Models\IndustryTemplate;
use App\Models\Module;
use App\Models\Tenant;
use App\Services\ModuleService;
use App\Services\PlatformAuditRecorder;
use App\Services\PlatformPermissionAuthorizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PlatformIndustryTemplateController extends Controller
{
    public function __construct(
        protected ModuleService $moduleService,
        protected PlatformAuditRecorder $audit,
        protected PlatformPermissionAuthorizer $authorizer
    ) {}

    /**
     * Display a listing of industry templates.
     */
    public function index(Request $request): View
    {
        $this->authorizer->ensure($request->user('platform'), 'platform.modules.view');

        $query = IndustryTemplate::query()->withCount('tenants');

        if ($request->filled('q')) {
            $q = trim((string) $request->input('q'));
            $query->where(function ($sub) use ($q): void {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('slug', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%");
            });
        }

        $templates = $query->orderBy('name')->get();

        return view('platform.templates.index', [
            'templates' => $templates,
            'searchQuery' => $request->input('q', ''),
        ]);
    }

    /**
     * Show the form for creating a new template.
     */
    public function create(Request $request): View
    {
        $this->authorizer->ensure($request->user('platform'), 'platform.modules.manage');

        $modules = Module::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get()->groupBy('category');
        $coreModules = Module::query()->where('is_core', true)->pluck('slug')->all();
        $moduleDependencies = Module::query()->where('is_active', true)->pluck('dependencies', 'slug')->all();

        return view('platform.templates.create', [
            'moduleCategories' => $modules,
            'coreModules' => $coreModules,
            'moduleDependencies' => $moduleDependencies,
        ]);
    }

    /**
     * Store a newly created template in storage.
     */
    public function store(StoreIndustryTemplateRequest $request): RedirectResponse
    {
        $this->authorizer->ensure($request->user('platform'), 'platform.modules.manage');

        $coreSlugs = Module::query()->where('is_core', true)->pluck('slug')->all();
        $inputModules = array_values(array_unique(array_merge($request->input('default_modules', []), $coreSlugs)));

        // Server-side dependency check
        $missing = $this->moduleService->getMissingDependencies($inputModules);
        if (! empty($missing) && ! $request->boolean('auto_add_dependencies')) {
            $errorMessages = [];
            foreach ($missing as $modSlug => $missingDeps) {
                $errorMessages[] = "Module '{$modSlug}' requires prerequisite module(s): ".implode(', ', $missingDeps);
            }

            throw ValidationException::withMessages([
                'default_modules' => implode('. ', $errorMessages),
            ]);
        }

        // Resolve all dependencies transitively and ensure core modules are present
        $resolvedModules = $this->moduleService->resolveAllDependencies($inputModules);

        $template = DB::transaction(function () use ($request, $resolvedModules): IndustryTemplate {
            $template = IndustryTemplate::create([
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
                'description' => $request->input('description'),
                'is_active' => $request->boolean('is_active', true),
                'default_modules' => $resolvedModules,
                'tax_rates' => $request->input('tax_rates', [16.0, 0.0]),
                'default_roles' => $request->input('default_roles', ['Owner', 'Cashier']),
            ]);

            $this->audit->record(
                actorId: $request->user('platform')?->id,
                action: 'platform.templates.create',
                targetType: IndustryTemplate::class,
                targetId: (string) $template->id,
                details: [
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'default_modules' => $template->default_modules,
                    'is_active' => $template->is_active,
                ],
            );

            return $template;
        });

        return redirect()->route('platform.templates.show', $template)
            ->with('notice', "Industry template '{$template->name}' created successfully with ".count($resolvedModules).' modules.');
    }

    /**
     * Display the specified template.
     */
    public function show(Request $request, IndustryTemplate $template): View
    {
        $this->authorizer->ensure($request->user('platform'), 'platform.modules.view');

        $orderedSlugs = $this->moduleService->resolveDependencyOrder($template->default_modules ?? []);
        $allModules = Module::query()->whereIn('slug', $orderedSlugs)->get()->keyBy('slug');
        $orderedModules = collect($orderedSlugs)->map(fn ($slug) => $allModules->get($slug))->filter();

        $tenants = $template->tenants()->latest()->take(50)->get();
        $allTenants = Tenant::query()->orderBy('name')->get();

        return view('platform.templates.show', [
            'template' => $template,
            'orderedModules' => $orderedModules,
            'tenants' => $tenants,
            'allTenants' => $allTenants,
        ]);
    }

    /**
     * Show the form for editing the template.
     */
    public function edit(Request $request, IndustryTemplate $template): View
    {
        $this->authorizer->ensure($request->user('platform'), 'platform.modules.manage');

        $modules = Module::query()->where('is_active', true)->orderBy('category')->orderBy('name')->get()->groupBy('category');
        $coreModules = Module::query()->where('is_core', true)->pluck('slug')->all();
        $moduleDependencies = Module::query()->where('is_active', true)->pluck('dependencies', 'slug')->all();

        return view('platform.templates.edit', [
            'template' => $template,
            'moduleCategories' => $modules,
            'coreModules' => $coreModules,
            'moduleDependencies' => $moduleDependencies,
        ]);
    }

    /**
     * Update the specified template in storage.
     */
    public function update(UpdateIndustryTemplateRequest $request, IndustryTemplate $template): RedirectResponse
    {
        $this->authorizer->ensure($request->user('platform'), 'platform.modules.manage');

        $coreSlugs = Module::query()->where('is_core', true)->pluck('slug')->all();
        $inputModules = array_values(array_unique(array_merge($request->input('default_modules', []), $coreSlugs)));

        // Server-side dependency check
        $missing = $this->moduleService->getMissingDependencies($inputModules);
        if (! empty($missing) && ! $request->boolean('auto_add_dependencies')) {
            $errorMessages = [];
            foreach ($missing as $modSlug => $missingDeps) {
                $errorMessages[] = "Module '{$modSlug}' requires prerequisite module(s): ".implode(', ', $missingDeps);
            }

            throw ValidationException::withMessages([
                'default_modules' => implode('. ', $errorMessages),
            ]);
        }

        $resolvedModules = $this->moduleService->resolveAllDependencies($inputModules);

        DB::transaction(function () use ($request, $template, $resolvedModules): void {
            $template->update([
                'name' => $request->input('name'),
                'slug' => $request->input('slug'),
                'description' => $request->input('description'),
                'is_active' => $request->boolean('is_active', true),
                'default_modules' => $resolvedModules,
            ]);

            $this->audit->record(
                actorId: $request->user('platform')?->id,
                action: 'platform.templates.update',
                targetType: IndustryTemplate::class,
                targetId: (string) $template->id,
                details: [
                    'name' => $template->name,
                    'slug' => $template->slug,
                    'default_modules' => $template->default_modules,
                    'is_active' => $template->is_active,
                ],
            );
        });

        return redirect()->route('platform.templates.show', $template)
            ->with('notice', "Industry template '{$template->name}' updated successfully.");
    }

    /**
     * Remove the specified template from storage (or block if tenants exist).
     */
    public function destroy(Request $request, IndustryTemplate $template): RedirectResponse
    {
        $this->authorizer->ensure($request->user('platform'), 'platform.modules.manage');

        $tenantCount = $template->tenants()->count();
        if ($tenantCount > 0) {
            return redirect()->route('platform.templates.show', $template)
                ->with('error', "Cannot delete template '{$template->name}' because {$tenantCount} tenant(s) were provisioned from it. You can deactivate it instead.");
        }

        DB::transaction(function () use ($request, $template): void {
            $templateId = (string) $template->id;
            $name = $template->name;
            $slug = $template->slug;

            $template->delete();

            $this->audit->record(
                actorId: $request->user('platform')?->id,
                action: 'platform.templates.delete',
                targetType: IndustryTemplate::class,
                targetId: $templateId,
                details: [
                    'name' => $name,
                    'slug' => $slug,
                ],
            );
        });

        return redirect()->route('platform.templates.index')
            ->with('notice', "Industry template '{$template->name}' deleted.");
    }

    /**
     * Toggle active/inactive status of the template.
     */
    public function toggleActive(Request $request, IndustryTemplate $template): RedirectResponse
    {
        $this->authorizer->ensure($request->user('platform'), 'platform.modules.manage');

        DB::transaction(function () use ($request, $template): void {
            $template->update(['is_active' => ! $template->is_active]);

            $this->audit->record(
                actorId: $request->user('platform')?->id,
                action: 'platform.templates.status',
                targetType: IndustryTemplate::class,
                targetId: (string) $template->id,
                details: [
                    'name' => $template->name,
                    'is_active' => $template->is_active,
                ],
            );
        });

        $status = $template->is_active ? 'activated' : 'deactivated';

        return back()->with('notice', "Template '{$template->name}' has been {$status}.");
    }

    /**
     * Preview module application to a tenant.
     */
    public function preview(Request $request, IndustryTemplate $template, Tenant $tenant): JsonResponse
    {
        $this->authorizer->ensure($request->user('platform'), 'platform.modules.view');

        $templateModules = collect($template->default_modules ?? []);
        $enabledSlugs = $this->moduleService->getEnabledModuleSlugs($tenant);

        $alreadyEnabled = $templateModules->intersect($enabledSlugs)->values();
        $willEnable = $templateModules->diff($enabledSlugs)->values();
        $orderedWillEnable = $this->moduleService->resolveDependencyOrder($willEnable->all());

        return response()->json([
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'template' => [
                'id' => $template->id,
                'name' => $template->name,
            ],
            'already_enabled' => $alreadyEnabled,
            'will_enable' => $orderedWillEnable,
            'total_template_modules' => $templateModules->count(),
        ]);
    }

    /**
     * Apply template to a tenant from the template show page.
     */
    public function applyToTenant(ApplyIndustryTemplateRequest $request, IndustryTemplate $template): RedirectResponse
    {
        $this->authorizer->ensure($request->user('platform'), 'platform.modules.manage');

        $validated = $request->validate([
            'tenant_id' => ['required', 'integer', 'exists:tenants,id'],
        ]);

        $tenant = Tenant::findOrFail($validated['tenant_id']);

        $enabledSlugs = DB::transaction(function () use ($request, $tenant, $template): array {
            $enabled = $this->moduleService->applyIndustryTemplate($tenant, $template);

            $this->audit->record(
                actorId: $request->user('platform')?->id,
                action: 'tenant.template.apply',
                targetType: Tenant::class,
                targetId: (string) $tenant->id,
                details: [
                    'template_slug' => $template->slug,
                    'enabled_modules' => $enabled,
                ],
            );

            return $enabled;
        });

        return redirect()->route('platform.templates.show', $template)
            ->with('notice', "Applied {$template->name} blueprint to tenant '{$tenant->name}' (".count($enabledSlugs).' modules enabled).');
    }

    /**
     * Apply template to a tenant from the tenant show page.
     */
    public function apply(Request $request, Tenant $tenant): RedirectResponse
    {
        $this->authorizer->ensure($request->user('platform'), 'platform.modules.manage');

        $validated = $request->validate([
            'template_id' => ['required', 'integer', 'exists:industry_templates,id'],
        ]);

        $template = IndustryTemplate::query()->findOrFail($validated['template_id']);

        $enabledSlugs = DB::transaction(function () use ($request, $tenant, $template): array {
            $enabled = $this->moduleService->applyIndustryTemplate($tenant, $template);

            $this->audit->record(
                actorId: $request->user('platform')?->id,
                action: 'tenant.template.apply',
                targetType: Tenant::class,
                targetId: (string) $tenant->id,
                details: [
                    'template_slug' => $template->slug,
                    'enabled_modules' => $enabled,
                ],
            );

            return $enabled;
        });

        return redirect()->route('platform.tenants.show', $tenant)
            ->with('notice', "Applied {$template->name} blueprint (".count($enabledSlugs).' modules enabled).');
    }
}
