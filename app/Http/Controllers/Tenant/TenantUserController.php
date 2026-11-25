<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use App\Models\TenantRole;
use App\Models\User;
use App\Services\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class TenantUserController extends Controller
{
    public function index(Request $request): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $query = User::query()
            ->where('tenant_id', $tenant->id)
            ->with(['roles', 'shops']);

        if ($request->filled('search')) {
            $search = '%'.$request->input('search').'%';
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', $search)
                    ->orWhere('email', 'like', $search)
                    ->orWhere('phone', 'like', $search);
            });
        }

        if ($request->filled('role')) {
            $roleSlug = $request->input('role');
            $query->whereHas('roles', function ($q) use ($roleSlug): void {
                $q->where('slug', $roleSlug);
            });
        }

        $users = $query->orderBy('name')->paginate(20)->withQueryString();
        $roles = TenantRole::query()->where('tenant_id', $tenant->id)->orderBy('name')->get();
        $shops = Shop::query()->where('tenant_id', $tenant->id)->orderBy('name')->get();

        return view('tenant.users.index', [
            'tenant' => $tenant,
            'users' => $users,
            'roles' => $roles,
            'shops' => $shops,
        ]);
    }

    public function create(): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $roles = TenantRole::query()->where('tenant_id', $tenant->id)->orderBy('name')->get();
        $shops = Shop::query()->where('tenant_id', $tenant->id)->orderBy('name')->get();

        return view('tenant.users.create', [
            'tenant' => $tenant,
            'roles' => $roles,
            'shops' => $shops,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->where('tenant_id', $tenant->id),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['required', 'string', Password::min(8)],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', Rule::exists('tenant_roles', 'id')->where('tenant_id', $tenant->id)],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['integer', Rule::exists('shops', 'id')->where('tenant_id', $tenant->id)],
            'primary_shop_id' => ['nullable', 'integer', Rule::exists('shops', 'id')->where('tenant_id', $tenant->id)],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'email' => mb_strtolower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'status' => $validated['status'],
            'must_reset_password' => false,
            'email_verified_at' => now(),
        ]);

        $user->roles()->sync($validated['role_ids']);

        if (! empty($validated['shop_ids'])) {
            $shopSyncData = [];
            $primaryId = $validated['primary_shop_id'] ?? $validated['shop_ids'][0];
            foreach ($validated['shop_ids'] as $shopId) {
                $shopSyncData[$shopId] = ['is_primary' => (int) $shopId === (int) $primaryId];
            }
            $user->shops()->sync($shopSyncData);
        }

        return redirect()->route('tenant.users.index')
            ->with('success', 'Staff member created successfully.');
    }

    public function edit(User $user): View
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 404);

        $roles = TenantRole::query()->where('tenant_id', $tenant->id)->orderBy('name')->get();
        $shops = Shop::query()->where('tenant_id', $tenant->id)->orderBy('name')->get();

        return view('tenant.users.edit', [
            'tenant' => $tenant,
            'user' => $user->load(['roles', 'shops']),
            'roles' => $roles,
            'shops' => $shops,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $tenant = TenantContext::getTenant();
        abort_if($tenant === null, 403, 'Tenant context not established.');
        abort_unless((int) $user->tenant_id === (int) $tenant->id, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email:rfc',
                'max:255',
                Rule::unique('users', 'email')->where('tenant_id', $tenant->id)->ignore($user->id),
            ],
            'phone' => ['nullable', 'string', 'max:32'],
            'password' => ['nullable', 'string', Password::min(8)],
            'role_ids' => ['required', 'array', 'min:1'],
            'role_ids.*' => ['integer', Rule::exists('tenant_roles', 'id')->where('tenant_id', $tenant->id)],
            'shop_ids' => ['nullable', 'array'],
            'shop_ids.*' => ['integer', Rule::exists('shops', 'id')->where('tenant_id', $tenant->id)],
            'primary_shop_id' => ['nullable', 'integer', Rule::exists('shops', 'id')->where('tenant_id', $tenant->id)],
            'status' => ['required', 'string', Rule::in(['active', 'inactive'])],
        ]);

        $updateData = [
            'name' => $validated['name'],
            'email' => mb_strtolower($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->roles()->sync($validated['role_ids']);

        if (! empty($validated['shop_ids'])) {
            $shopSyncData = [];
            $primaryId = $validated['primary_shop_id'] ?? $validated['shop_ids'][0];
            foreach ($validated['shop_ids'] as $shopId) {
                $shopSyncData[$shopId] = ['is_primary' => (int) $shopId === (int) $primaryId];
            }
            $user->shops()->sync($shopSyncData);
        } else {
            $user->shops()->detach();
        }

        return redirect()->route('tenant.users.index')
            ->with('success', 'Staff member updated successfully.');
    }
}
