<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantRole;
use App\Services\TenantContext;
use App\Services\TenantRoleTemplateService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('tenants:seed-role-templates {tenant? : Tenant ID to add missing templates for} {--all : Add templates for every existing tenant}')]
#[Description('Add industry-specific staff role templates without changing users or existing role permissions.')]
class SeedTenantRoleTemplates extends Command
{
    public function handle(TenantRoleTemplateService $templates): int
    {
        $tenantId = $this->argument('tenant');
        if (! $tenantId && ! $this->option('all')) {
            $this->components->error('Pass a tenant ID or use --all.');

            return self::FAILURE;
        }

        $tenants = Tenant::withoutGlobalScopes()
            ->when($tenantId, fn ($query) => $query->whereKey($tenantId))
            ->orderBy('id')->get();
        if ($tenants->isEmpty()) {
            $this->components->error('No matching tenant was found.');

            return self::FAILURE;
        }

        foreach ($tenants as $tenant) {
            TenantContext::setTenant($tenant);
            try {
                DB::transaction(function () use ($tenant, $templates): void {
                    $existingIds = TenantRole::query()->where('tenant_id', $tenant->id)->pluck('id')->all();
                    $roles = $templates->createTemplateRoles($tenant);
                    $newRoles = array_values(array_filter($roles, fn (TenantRole $role) => ! in_array($role->id, $existingIds, true)));
                    $templates->assignDefaultsToNewRoles($tenant, $newRoles);
                });
            } finally {
                TenantContext::clear();
            }

            $this->components->info("Added missing role templates for tenant #{$tenant->id} ({$tenant->name}). Existing users and role grants were left intact.");
        }

        return self::SUCCESS;
    }
}
