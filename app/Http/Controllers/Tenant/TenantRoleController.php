<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\TenantActivityLog;
use App\Models\TenantPermission;
use App\Models\TenantRole;
use App\Services\TenantContext;
use App\Services\TenantRoleTemplateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TenantRoleController extends Controller
{
    public function index(TenantRoleTemplateService $templates): View
    {
        $tenant = $this->tenant();
        $templates->ensurePermissionCatalog();

        return view('tenant.roles.index', [
            'tenant' => $tenant,
            'roles' => TenantRole::query()->where('tenant_id', $tenant->id)
                ->with('permissions')->withCount('users')->orderBy('name')->get(),
        ]);
    }

    public function create(TenantRoleTemplateService $templates): View
    {
        $templates->ensurePermissionCatalog();

        return view('tenant.roles.form', [
            'role' => new TenantRole,
            'permissions' => $this->permissionOptions($templates),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->tenant();
        $templates = app(TenantRoleTemplateService::class);
        $templates->ensurePermissionCatalog();
        $validated = $this->validateRole($request, $templates);
        $role = DB::transaction(function () use ($validated, $tenant, $request): TenantRole {
            $slug = Str::slug($validated['name']);
            abort_if($slug === '' || $slug === 'owner', 422, 'This role name is reserved.');
            abort_if(TenantRole::query()->where('tenant_id', $tenant->id)->where('slug', $slug)->exists(), 422, 'A role with this name already exists.');

            $role = TenantRole::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
                'is_system' => false,
            ]);
            $role->permissions()->sync($validated['permission_ids'] ?? []);
            $this->recordRoleChange($request, 'tenant.role.created', $role, null, $this->snapshot($role));

            return $role;
        });

        return redirect()->route('tenant.roles.index')->with('success', "Role '{$role->name}' created.");
    }

    public function edit(TenantRole $role, TenantRoleTemplateService $templates): View
    {
        $this->assertTenantRole($role);
        abort_if($role->is_system, 403, 'Built-in roles cannot be edited.');
        $templates->ensurePermissionCatalog();

        return view('tenant.roles.form', [
            'role' => $role->load('permissions'),
            'permissions' => $this->permissionOptions($templates),
        ]);
    }

    public function update(Request $request, TenantRole $role, TenantRoleTemplateService $templates): RedirectResponse
    {
        $this->assertTenantRole($role);
        abort_if($role->is_system, 403, 'Built-in roles cannot be edited.');
        $templates->ensurePermissionCatalog();
        $validated = $this->validateRole($request, $templates);

        DB::transaction(function () use ($validated, $role, $request): void {
            $before = $this->snapshot($role);
            $slug = Str::slug($validated['name']);
            abort_if($slug === '' || $slug === 'owner', 422, 'This role name is reserved.');
            abort_if(TenantRole::query()->where('tenant_id', $role->tenant_id)->where('slug', $slug)->where('id', '!=', $role->id)->exists(), 422, 'A role with this name already exists.');

            $role->update([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
            ]);
            $role->permissions()->sync($validated['permission_ids'] ?? []);
            $this->recordRoleChange($request, 'tenant.role.updated', $role, $before, $this->snapshot($role->fresh()));
        });

        return redirect()->route('tenant.roles.index')->with('success', "Role '{$role->name}' updated.");
    }

    public function destroy(Request $request, TenantRole $role): RedirectResponse
    {
        $this->assertTenantRole($role);
        abort_if($role->is_system, 403, 'Built-in roles cannot be deleted.');
        abort_if($role->users()->exists(), 409, 'Reassign staff before deleting this role.');

        DB::transaction(function () use ($request, $role): void {
            $before = $this->snapshot($role);
            $role->delete();
            $this->recordRoleChange($request, 'tenant.role.deleted', $role, $before, null);
        });

        return redirect()->route('tenant.roles.index')->with('success', 'Role deleted.');
    }

    private function validateRole(Request $request, TenantRoleTemplateService $templates): array
    {
        $supportedIds = TenantPermission::query()
            ->whereIn('name', array_keys($templates->permissionDefinitions()))
            ->pluck('id')->all();

        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'permission_ids' => ['nullable', 'array'],
            'permission_ids.*' => ['integer', 'distinct', Rule::in($supportedIds)],
        ]);
    }

    private function permissionOptions(TenantRoleTemplateService $templates): \Illuminate\Support\Collection
    {
        return TenantPermission::query()
            ->whereIn('name', array_keys($templates->permissionDefinitions()))
            ->orderBy('group')->orderBy('name')->get()->groupBy('group');
    }

    private function tenant(): \App\Models\Tenant
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        return $tenant;
    }

    private function assertTenantRole(TenantRole $role): void
    {
        abort_unless((int) $role->tenant_id === (int) $this->tenant()->id, 404);
    }

    private function snapshot(TenantRole $role): array
    {
        return [
            'name' => $role->name,
            'slug' => $role->slug,
            'description' => $role->description,
            'permissions' => $role->permissions()->orderBy('name')->pluck('name')->all(),
        ];
    }

    private function recordRoleChange(Request $request, string $action, TenantRole $role, ?array $before, ?array $after): void
    {
        TenantActivityLog::query()->create([
            'tenant_id' => $role->tenant_id,
            'shop_id' => null,
            'user_id' => $request->user()->id,
            'action' => $action,
            'route_name' => $request->route()?->getName(),
            'subject_type' => TenantRole::class,
            'subject_id' => (string) $role->id,
            'subject_label' => $role->name,
            'occurred_at' => now(),
            'context' => ['before' => $before, 'after' => $after],
        ]);
    }
}
