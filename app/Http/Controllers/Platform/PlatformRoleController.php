<?php

namespace App\Http\Controllers\Platform;

use App\Http\Controllers\Controller;
use App\Models\PlatformApproval;
use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use App\Models\PlatformUser;
use App\Notifications\PlatformRoleChanged;
use App\Services\PlatformAuditRecorder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PlatformRoleController extends Controller
{
    public function index(): View
    {
        return view('platform.security.roles', [
            'roles' => PlatformRole::query()->with('permissions')->orderBy('name')->get(),
            'permissionsByArea' => PlatformPermission::query()->orderBy('area')->orderBy('action')->get()->groupBy('area'),
        ]);
    }

    public function store(Request $request, PlatformAuditRecorder $audit): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:platform_roles,name'],
            'description' => ['required', 'string', 'max:1000'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'distinct', 'exists:platform_permissions,id'],
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);
        $slug = Str::slug($validated['name']);
        $permissionIds = $validated['permission_ids'] ?? [];
        DB::transaction(function () use ($request, $validated, $slug, $permissionIds, $audit): void {
            abort_if(PlatformRole::query()->where('slug', $slug)->lockForUpdate()->exists(), 422, 'A role with this name already exists.');
            $role = PlatformRole::query()->create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'],
                'is_builtin' => false,
            ]);
            $role->permissions()->sync($permissionIds);
            $permissionNames = PlatformPermission::query()->whereIn('id', $permissionIds)->orderBy('name')->pluck('name')->all();
            $audit->record($request->user('platform')->id, 'platform.role.created', PlatformRole::class, (string) $role->id, [
                'after' => ['name' => $role->name, 'permissions' => $permissionNames],
                'reason' => $validated['reason'],
            ]);
        });

        return back()->with('status', 'Platform role created.');
    }

    public function update(Request $request, PlatformRole $platformRole, PlatformAuditRecorder $audit): RedirectResponse
    {
        abort_if($platformRole->is_builtin, 403, 'Built-in platform roles are protected.');
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120', 'unique:platform_roles,name,'.$platformRole->id],
            'description' => ['required', 'string', 'max:1000'],
            'permission_ids' => ['array'],
            'permission_ids.*' => ['integer', 'distinct', 'exists:platform_permissions,id'],
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);
        $permissionIds = $validated['permission_ids'] ?? [];
        $afterPermissions = PlatformPermission::query()->whereIn('id', $permissionIds)->orderBy('name')->pluck('name')->all();
        $slug = Str::slug($validated['name']);
        $result = DB::transaction(function () use ($request, $platformRole, $validated, $permissionIds, $afterPermissions, $slug, $audit): array {
            $role = PlatformRole::query()->whereKey($platformRole->id)->lockForUpdate()->firstOrFail();
            abort_if($role->is_builtin, 403, 'Built-in platform roles are protected.');
            abort_if(PlatformRole::query()->where('slug', $slug)->where('id', '!=', $role->id)->exists(), 422, 'A role with this name already exists.');

            $beforePermissions = $role->permissions()->orderBy('name')->pluck('name')->all();
            $beforeName = $role->name;
            $beforeDescription = $role->description;
            $role->update([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'],
            ]);
            $role->permissions()->sync($permissionIds);
            $audit->record($request->user('platform')->id, 'platform.role.updated', PlatformRole::class, (string) $role->id, [
                'before' => ['name' => $beforeName, 'permissions' => $beforePermissions],
                'after' => ['name' => $role->name, 'permissions' => $afterPermissions],
                'reason' => $validated['reason'],
            ]);

            if ($beforePermissions !== $afterPermissions) {
                $this->revokeRoleSessions($role);
            }

            return [
                'role' => $role,
                'users' => $role->users()->get(),
                'notify' => $beforeName !== $role->name
                    || $beforeDescription !== $role->description
                    || $beforePermissions !== $afterPermissions,
            ];
        });

        if ($result['notify']) {
            foreach ($result['users'] as $user) {
                $user->notify(new PlatformRoleChanged(
                    $result['role']->name,
                    'updated',
                    $validated['reason'],
                    $request->user('platform')->name,
                ));
            }
        }

        return back()->with('status', 'Platform role updated.');
    }

    public function destroy(Request $request, PlatformRole $platformRole, PlatformAuditRecorder $audit): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:8', 'max:1000']]);
        DB::transaction(function () use ($request, $platformRole, $validated, $audit): void {
            $role = PlatformRole::query()->whereKey($platformRole->id)->lockForUpdate()->firstOrFail();
            abort_if($role->is_builtin, 403, 'Built-in platform roles cannot be deleted.');
            abort_if($role->users()->exists(), 409, 'Remove the role from users before deleting it.');
            $id = $role->id;
            $name = $role->name;
            $role->delete();
            $audit->record($request->user('platform')->id, 'platform.role.deleted', PlatformRole::class, (string) $id, ['name' => $name, 'reason' => $validated['reason']]);
        });

        return back()->with('status', 'Platform role deleted.');
    }

    public function assign(Request $request, PlatformUser $platformUser, PlatformAuditRecorder $audit): RedirectResponse
    {
        abort_if($request->user('platform')->is($platformUser), 403, 'You cannot assign roles to your own account.');
        $validated = $request->validate([
            'platform_role_id' => ['required', 'integer', 'exists:platform_roles,id'],
            'reason' => ['required', 'string', 'min:8', 'max:1000'],
        ]);
        $role = PlatformRole::query()->findOrFail($validated['platform_role_id']);
        abort_if($platformUser->roles()->whereKey($role->id)->exists(), 409, 'The user already has this role.');

        $assignment = DB::transaction(function () use ($request, $platformUser, $role, $validated, $audit): array {
            $target = PlatformUser::query()->whereKey($platformUser->id)->lockForUpdate()->firstOrFail();
            $lockedRole = PlatformRole::query()->whereKey($role->id)->lockForUpdate()->firstOrFail();
            abort_if($target->roles()->whereKey($lockedRole->id)->exists(), 409, 'The user already has this role.');

            if ($lockedRole->slug === 'super-administrator') {
                $approval = PlatformApproval::query()->create([
                    'action_type' => 'platform.superadmin.grant',
                    'target_type' => PlatformUser::class,
                    'target_id' => (string) $target->id,
                    'requested_by' => $request->user('platform')->id,
                    'status' => 'pending',
                    'reason' => $validated['reason'],
                    'payload' => ['platform_role_id' => $lockedRole->id],
                    'expires_at' => now()->addDays(7),
                ]);
                $audit->record($request->user('platform')->id, 'platform.superadmin.grant_requested', PlatformUser::class, (string) $target->id, ['approval_id' => $approval->id, 'reason' => $validated['reason']]);

                return ['approval' => true, 'user' => $target, 'role' => $lockedRole];
            }

            $target->roles()->attach($lockedRole->id, ['assigned_by' => $request->user('platform')->id]);
            $this->revokeUserSessions($target);
            $audit->record($request->user('platform')->id, 'platform.user.role_assigned', PlatformUser::class, (string) $target->id, ['role' => $lockedRole->slug, 'reason' => $validated['reason']]);

            return ['approval' => false, 'user' => $target, 'role' => $lockedRole];
        });

        if ($assignment['approval']) {
            return redirect()->route('platform.approvals.index')->with('status', 'Super Administrator grant requires approval by another platform administrator.');
        }

        $assignment['user']->notify(new PlatformRoleChanged(
            $assignment['role']->name,
            'assigned to your account',
            $validated['reason'],
            $request->user('platform')->name,
        ));

        return back()->with('status', 'Role assigned. The affected user must sign in again.');
    }

    public function remove(Request $request, PlatformUser $platformUser, PlatformRole $platformRole, PlatformAuditRecorder $audit): RedirectResponse
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:8', 'max:1000']]);
        abort_if($request->user('platform')->is($platformUser), 422, 'You cannot remove your own platform role.');

        [$target, $role] = DB::transaction(function () use ($request, $platformUser, $platformRole, $validated, $audit): array {
            $target = PlatformUser::query()->whereKey($platformUser->id)->lockForUpdate()->firstOrFail();
            $role = PlatformRole::query()->whereKey($platformRole->id)->lockForUpdate()->firstOrFail();
            abort_unless($target->roles()->whereKey($role->id)->exists(), 404);

            if ($role->slug === 'super-administrator') {
                $activeSuperAdmins = $role->users()->where('platform_users.status', 'active')->count();
                abort_if($target->status === 'active' && $activeSuperAdmins <= 1, 409, 'The final active Super Administrator cannot be removed.');
            }

            $target->roles()->detach($role->id);
            $this->revokeUserSessions($target);
            $audit->record($request->user('platform')->id, 'platform.user.role_removed', PlatformUser::class, (string) $target->id, ['role' => $role->slug, 'reason' => $validated['reason']]);

            return [$target, $role];
        });

        $target->notify(new PlatformRoleChanged(
            $role->name,
            'removed from your account',
            $validated['reason'],
            $request->user('platform')->name,
        ));

        return back()->with('status', 'Role removed. The affected user must sign in again.');
    }

    private function revokeRoleSessions(PlatformRole $role): void
    {
        foreach ($role->users as $user) {
            $this->revokeUserSessions($user);
        }
    }

    private function revokeUserSessions(PlatformUser $user): void
    {
        $sessions = $user->sessions()->whereNull('revoked_at')->get();
        foreach ($sessions as $session) {
            DB::table('sessions')->where('id', $session->session_identifier)->delete();
        }
        $user->sessions()->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }
}
