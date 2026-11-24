<?php

namespace App\Services;

use App\Models\IndustryTemplate;
use App\Models\Module;
use App\Models\Tenant;
use App\Models\TenantModuleEntitlement;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ModuleService
{
    /**
     * Check if a module is currently enabled for a tenant.
     */
    public function isModuleEnabled(Tenant $tenant, string $moduleSlug): bool
    {
        return TenantModuleEntitlement::query()
            ->where('tenant_id', $tenant->id)
            ->whereHas('module', function ($q) use ($moduleSlug): void {
                $q->where('slug', $moduleSlug);
            })
            ->where('status', 'enabled')
            ->exists();
    }

    /**
     * Get all enabled module slugs for a tenant.
     *
     * @return Collection<int, string>
     */
    public function getEnabledModuleSlugs(Tenant $tenant): Collection
    {
        return TenantModuleEntitlement::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'enabled')
            ->with('module')
            ->get()
            ->pluck('module.slug');
    }

    /**
     * Enable a module for a tenant, validating that all prerequisite dependencies are enabled.
     */
    public function enableModule(Tenant $tenant, Module $module, ?string $notes = null): TenantModuleEntitlement
    {
        abort_unless($module->is_active, 422, "Cannot enable inactive module '{$module->name}'.");

        $enabledSlugs = $this->getEnabledModuleSlugs($tenant);

        // Check if all dependencies are satisfied
        $missingDependencies = [];
        foreach ($module->dependencies ?? [] as $depSlug) {
            if (! $enabledSlugs->contains($depSlug)) {
                $depModule = Module::where('slug', $depSlug)->first();
                $missingDependencies[] = $depModule ? $depModule->name : $depSlug;
            }
        }

        if (! empty($missingDependencies)) {
            $missingList = implode(', ', $missingDependencies);
            throw ValidationException::withMessages([
                'module' => "Cannot enable '{$module->name}' because prerequisite module(s) [{$missingList}] must be enabled first.",
            ]);
        }

        $entitlement = TenantModuleEntitlement::query()
            ->where('tenant_id', $tenant->id)
            ->where('module_id', $module->id)
            ->first();

        if ($entitlement) {
            $entitlement->update([
                'status' => 'enabled',
                'enabled_at' => now(),
                'disabled_at' => null,
                'notes' => $notes ?? $entitlement->notes,
            ]);
        } else {
            $entitlement = TenantModuleEntitlement::query()->create([
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
                'status' => 'enabled',
                'enabled_at' => now(),
                'notes' => $notes,
            ]);
        }

        return $entitlement;
    }

    /**
     * Disable a module for a tenant, validating that no other active module depends on it.
     */
    public function disableModule(Tenant $tenant, Module $module, ?string $notes = null): TenantModuleEntitlement
    {
        abort_if($module->is_core, 422, "Core module '{$module->name}' cannot be disabled.");

        // Check reverse dependencies: is any other currently enabled module depending on this one?
        $activeEntitlements = TenantModuleEntitlement::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'enabled')
            ->where('module_id', '!=', $module->id)
            ->with('module')
            ->get();

        $blockingModules = [];
        foreach ($activeEntitlements as $ent) {
            if (in_array($module->slug, $ent->module->dependencies ?? [], true)) {
                $blockingModules[] = $ent->module->name;
            }
        }

        if (! empty($blockingModules)) {
            $blockingList = implode(', ', $blockingModules);
            throw ValidationException::withMessages([
                'module' => "Cannot disable '{$module->name}' because active module(s) [{$blockingList}] depend on it.",
            ]);
        }

        $entitlement = TenantModuleEntitlement::query()
            ->where('tenant_id', $tenant->id)
            ->where('module_id', $module->id)
            ->firstOrFail();

        $entitlement->update([
            'status' => 'disabled',
            'disabled_at' => now(),
            'notes' => $notes ?? $entitlement->notes,
        ]);

        return $entitlement;
    }

    /**
     * Resolve module slugs in topological dependency order (prerequisites first).
     *
     * @param  array<int, string>  $moduleSlugs
     * @return array<int, string>
     */
    public function resolveDependencyOrder(array $moduleSlugs): array
    {
        $allModules = Module::query()->whereIn('slug', $moduleSlugs)->get()->keyBy('slug');
        $remaining = collect($moduleSlugs)->unique()->values();
        $ordered = [];
        $attempts = 0;

        while ($remaining->isNotEmpty() && $attempts < 20) {
            $attempts++;
            foreach ($remaining as $key => $slug) {
                $mod = $allModules->get($slug);
                if (! $mod) {
                    $remaining->forget($key);

                    continue;
                }

                $deps = collect($mod->dependencies ?? []);
                $depsSatisfied = $deps->every(fn ($dep) => in_array($dep, $ordered, true) || ! in_array($dep, $moduleSlugs, true));

                if ($depsSatisfied) {
                    $ordered[] = $slug;
                    $remaining->forget($key);
                }
            }
        }

        foreach ($remaining as $slug) {
            if (! in_array($slug, $ordered, true)) {
                $ordered[] = $slug;
            }
        }

        return $ordered;
    }

    /**
     * Find any missing prerequisite dependencies for the given set of module slugs.
     *
     * @param  array<int, string>  $moduleSlugs
     * @return array<string, array<int, string>>
     */
    public function getMissingDependencies(array $moduleSlugs): array
    {
        $allModules = Module::query()->whereIn('slug', $moduleSlugs)->get()->keyBy('slug');
        $missing = [];

        foreach ($moduleSlugs as $slug) {
            $mod = $allModules->get($slug);
            if ($mod) {
                foreach ($mod->dependencies ?? [] as $dep) {
                    if (! in_array($dep, $moduleSlugs, true)) {
                        $missing[$slug][] = $dep;
                    }
                }
            }
        }

        return $missing;
    }

    /**
     * Automatically resolve all missing dependencies and ensure core modules are included.
     *
     * @param  array<int, string>  $moduleSlugs
     * @return array<int, string>
     */
    public function resolveAllDependencies(array $moduleSlugs): array
    {
        $resolved = collect($moduleSlugs);
        $coreSlugs = Module::query()->where('is_core', true)->pluck('slug');
        $resolved = $resolved->merge($coreSlugs)->unique();

        $addedNew = true;
        $iteration = 0;

        while ($addedNew && $iteration < 15) {
            $iteration++;
            $addedNew = false;
            $currentModules = Module::query()->whereIn('slug', $resolved)->get();

            foreach ($currentModules as $mod) {
                foreach ($mod->dependencies ?? [] as $dep) {
                    if (! $resolved->contains($dep)) {
                        $resolved->push($dep);
                        $addedNew = true;
                    }
                }
            }
        }

        return $this->resolveDependencyOrder($resolved->unique()->values()->all());
    }

    /**
     * Apply an industry template to a tenant, enabling all default modules in safe topological order.
     *
     * @return array<int, string>
     */
    public function applyIndustryTemplate(Tenant $tenant, IndustryTemplate $template): array
    {
        $moduleSlugs = $template->default_modules ?? [];
        $enabled = [];

        // Order modules so dependencies are processed first
        $allModules = Module::query()->whereIn('slug', $moduleSlugs)->get()->keyBy('slug');

        // Simple multi-pass to resolve dependencies
        $remaining = collect($moduleSlugs);
        $attempts = 0;

        while ($remaining->isNotEmpty() && $attempts < 10) {
            $attempts++;
            foreach ($remaining as $key => $slug) {
                $mod = $allModules->get($slug);
                if (! $mod) {
                    $remaining->forget($key);

                    continue;
                }

                $deps = collect($mod->dependencies ?? []);
                $depsSatisfied = $deps->every(fn ($dep) => in_array($dep, $enabled, true) || $this->isModuleEnabled($tenant, $dep));

                if ($depsSatisfied) {
                    $this->enableModule($tenant, $mod, "Applied via {$template->name} industry template");
                    $enabled[] = $slug;
                    $remaining->forget($key);
                }
            }
        }

        return $enabled;
    }
}
