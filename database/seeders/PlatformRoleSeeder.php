<?php

namespace Database\Seeders;

use App\Models\PlatformPermission;
use App\Models\PlatformRole;
use Illuminate\Database\Seeder;

class PlatformRoleSeeder extends Seeder
{
    public function run(): void
    {
        $permissionMap = [
            'platform.dashboard.view' => ['dashboard', 'view'],
            'platform.tenants.view' => ['tenants', 'view'],
            'platform.tenants.create' => ['tenants', 'create'],
            'platform.tenants.update' => ['tenants', 'update'],
            'platform.tenants.suspend' => ['tenants', 'suspend'],
            'platform.tenants.reactivate' => ['tenants', 'reactivate'],
            'platform.provisioning.view' => ['provisioning', 'view'],
            'platform.provisioning.retry' => ['provisioning', 'retry'],
            'platform.provisioning.abort' => ['provisioning', 'abort'],
            'platform.billing.view' => ['billing', 'view'],
            'platform.billing.plans.manage' => ['billing', 'plans.manage'],
            'platform.billing.subscriptions.manage' => ['billing', 'subscriptions.manage'],
            'platform.modules.view' => ['modules', 'view'],
            'platform.modules.manage' => ['modules', 'manage'],
            'platform.security.view' => ['security', 'view'],
            'platform.security.users.view' => ['security.users', 'view'],
            'platform.security.users.invite' => ['security.users', 'invite'],
            'platform.security.users.status' => ['security.users', 'status'],
            'platform.security.users.mfa-reset.request' => ['security.users', 'mfa-reset.request'],
            'platform.security.users.password-reset' => ['security.users', 'password-reset'],
            'platform.security.users.roles.manage' => ['security.users', 'roles.manage'],
            'platform.security.roles.manage' => ['security', 'roles.manage'],
            'platform.security.sessions.revoke' => ['security', 'sessions.revoke'],
            'platform.security.approvals.review' => ['security', 'approvals.review'],
            'platform.audit.view' => ['audit', 'view'],
            'platform.audit.export' => ['audit', 'export'],
            'platform.support.view' => ['support', 'view'],
            'platform.compliance.view' => ['compliance', 'view'],
            'platform.infrastructure.view' => ['infrastructure', 'view'],
        ];

        foreach ($permissionMap as $name => [$area, $action]) {
            PlatformPermission::query()->updateOrCreate(['name' => $name], [
                'area' => $area,
                'action' => $action,
                'description' => ucfirst(str_replace('.', ' ', substr($name, 9))),
            ]);
        }

        $allPermissionIds = PlatformPermission::query()->pluck('id')->all();
        $roleDefinitions = [
            'super-administrator' => ['Super Administrator', 'Full platform control plane administration; tenant business data remains restricted.', $allPermissionIds],
            'platform-operations-administrator' => ['Platform Operations Administrator', 'Tenant lifecycle, provisioning and infrastructure operations.', ['platform.dashboard.view', 'platform.tenants.view', 'platform.tenants.suspend', 'platform.tenants.reactivate', 'platform.provisioning.view', 'platform.provisioning.retry', 'platform.provisioning.abort', 'platform.infrastructure.view']],
            'billing-administrator' => ['Billing Administrator', 'Plans, subscriptions and platform billing.', ['platform.dashboard.view', 'platform.tenants.view', 'platform.billing.view', 'platform.billing.plans.manage', 'platform.billing.subscriptions.manage']],
            'support-agent' => ['Support Agent', 'Tenant metadata and support workflows.', ['platform.dashboard.view', 'platform.tenants.view', 'platform.support.view']],
            'compliance-etims-officer' => ['Compliance / eTIMS Officer', 'Compliance metadata and eTIMS oversight.', ['platform.dashboard.view', 'platform.tenants.view', 'platform.compliance.view']],
            'platform-auditor' => ['Platform Auditor', 'Read-only audit and platform reports.', ['platform.dashboard.view', 'platform.tenants.view', 'platform.audit.view', 'platform.audit.export', 'platform.security.view', 'platform.billing.view']],
            'security-officer' => ['Security Officer', 'Security events, session revocation and account controls.', ['platform.dashboard.view', 'platform.security.view', 'platform.security.users.view', 'platform.security.users.status', 'platform.security.users.mfa-reset.request', 'platform.security.sessions.revoke', 'platform.security.approvals.review', 'platform.audit.view']],
        ];

        foreach ($roleDefinitions as $slug => [$name, $description, $permissions]) {
            $role = PlatformRole::query()->updateOrCreate(['slug' => $slug], [
                'name' => $name,
                'description' => $description,
                'is_builtin' => true,
            ]);

            $ids = is_array($permissions) && isset($permissions[0]) && is_int($permissions[0])
                ? $permissions
                : PlatformPermission::query()->whereIn('name', $permissions)->pluck('id')->all();

            $role->permissions()->sync($ids);
        }

        PlatformPermission::query()->where('name', 'platform.security.users.manage')->delete();
    }
}
